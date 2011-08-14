<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\DI;

use Doctrine\DBAL\Tools\Console\Command as DbalCommand;
use Doctrine\ORM\Tools\Console\Command as OrmCommand;
use Doctrine\CouchDB\Tools\Console\Command as CouchDBCommand;
use Doctrine\ODM\CouchDB\Tools\Console\Command as OdmCommand;
use Kdyby;
use Kdyby\Application\ModuleCascadeRegistry;
use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\UI\Presenter;
use Symfony\Component\Console;



/**
 * @author Patrik Votoček
 * @author Filip Procházka
 *
 * @property-read Container $container
 */
class Configurator extends Nette\Configurator
{

	/** @var array */
	public $onAfterLoadConfig = array();



	/**
	 * @param string $containerClass
	 */
	public function __construct($containerClass = 'Kdyby\Application\Container')
	{
		parent::__construct($containerClass);

		$baseUrl = rtrim($this->container->httpRequest->getUrl()->getBaseUrl(), '/');
		$this->container->params['baseUrl'] = $baseUrl;
		$this->container->params['basePath'] = preg_replace('#https?://[^/]+#A', '', $baseUrl);
		$this->container->params['kdybyFrameworkDir'] = realpath(KDYBY_FRAMEWORK_DIR);

		$this->onAfterLoadConfig[] = callback($this, 'loadDbSettings');
		$this->onAfterLoadConfig[] = callback($this, 'setupDebugger');
	}



	/**
	 * @param Container $container
	 */
	public function setupDebugger(Container $container)
	{
		$parameters = (array)$container->getParam('debugger', array());
		foreach ($parameters as $property => $value) {
			Nette\Utils\LimitedScope::evaluate(
				'<?php Nette\Diagnostics\Debugger::$' . $property .' = $value; ?>',
				array('value' => $value));
		}
	}



	/**
	 * Loads configuration from file and process it.
	 * @return Container
	 */
	public function loadConfig($file, $section = NULL)
	{
		parent::loadConfig($file, $section);
		$this->onAfterLoadConfig($this->container);
		return $this->container;
	}



	/**
	 * Registers CMS service and container parameters
	 * @returns Configurator
	 */
	public function registerCMS()
	{
		if (!defined('KDYBY_CMS_DIR')) {
			throw new Nette\InvalidStateException("Kdyby CMS is not present in installation.");
		}

		// make sure loader will take care
		Kdyby\Loaders\SplClassLoader::getInstance()
			->addNamespace('Kdyby', KDYBY_CMS_DIR);

		// CMS dir as expandable parameter
		$this->container->params['kdybyCmsDir'] = realpath(KDYBY_CMS_DIR);

		// register CMS container as service
		$this->container->addService('cms', new Kdyby\CMS\Container($this->container));

		// register callback for finalising this registration
		$this->onAfterLoadConfig[] = callback($this, 'finalizeCmsRegisteration');

		return $this; // fluent interface
	}



	/**
	 * Register services
	 */
	public function finalizeCmsRegisteration()
	{
		// CMS modules: Backend and others
		$this->container->moduleRegistry->add('Kdyby\Modules', KDYBY_CMS_DIR . '/Modules');
	}



	/**
	 * Loads settings from database
	 */
	public function loadDbSettings()
	{
		$this->container->settings->loadAll($this->container);
	}



	/**
	 * @param Nette\DI\Container $container
	 * @param array $options
	 * @return Kdyby\Application\Application
	 */
	public static function createServiceApplication(Nette\DI\Container $container, array $options = NULL)
	{
		$context = new Container;
		$context->addService('httpRequest', $container->httpRequest);
		$context->addService('httpResponse', $container->httpResponse);
		$context->addService('session', $container->session);
		$context->addService('presenterFactory', $container->presenterFactory);
		$context->addService('router', $container->router);
		$context->lazyCopy('console', $container);

		Presenter::$invalidLinkMode = $container->getParam('productionMode', TRUE)
			? Presenter::INVALID_LINK_SILENT : Presenter::INVALID_LINK_WARNING;

		$application = new Kdyby\Application\Application($context);
		$application->catchExceptions = $container->getParam('productionMode', TRUE);
		$application->errorPresenter = 'Error';

		return $application;
	}



	/**
	 * @param Container $container
	 * @return Kdyby\Doctrine\ORM\Container
	 */
	public static function createServiceSqldb(Container $container)
	{
		return new Kdyby\Doctrine\ORM\Container($container, $container->getParam('sqldb', array()));
	}



	/**
	 * @param Container $container
	 * @return Kdyby\Doctrine\ODM\Container
	 */
	public static function createServiceCouchdb(Container $container)
	{
		return new Kdyby\Doctrine\ODM\Container($container, $container->getParam('couchdb', array()));
	}



	/**
	 * @return Kdyby\DI\Settings
	 */
	public static function createServiceSettings(Container $container)
	{
		return new Kdyby\DI\Settings($container->sqldb->getRepository('Kdyby\DI\Setting'), $container->cacheStorage);
	}



	/**
	 * @param Nette\DI\Container $container
	 * @return Nette\Application\IPresenterFactory
	 */
	public static function createServicePresenterFactory(Nette\DI\Container $container)
	{
		return new Kdyby\Application\PresenterFactory($container->moduleRegistry, $container);
	}



	/**
	 * @param Container $container
	 * @return Kdyby\Templates\ITemplateFactory
	 */
	public static function createServiceTemplateFactory(Container $container)
	{
		return new Kdyby\Templates\TemplateFactory($container->latteEngine);
	}



	/**
	 * @param Nette\DI\Container $container
	 * @return Kdyby\Caching\FileStorage
	 */
	public static function createServiceCacheStorage(Nette\DI\Container $container)
	{
		if (!isset($container->params['tempDir'])) {
			throw new Nette\InvalidStateException("Service cacheStorage requires that parameter 'tempDir' contains path to temporary directory.");
		}
		$dir = $container->expand('%tempDir%/cache');
		umask(0000);
		@mkdir($dir, 0777); // @ - directory may exists
		return new Kdyby\Caching\FileStorage($dir, $container->cacheJournal);
	}



	/**
	 * @param Nette\DI\Container $container
	 * @return Kdyby\Loaders\RobotLoader
	 */
	public static function createServiceRobotLoader(Nette\DI\Container $container, array $options = NULL)
	{
		$loader = new Kdyby\Loaders\RobotLoader;
		$loader->autoRebuild = isset($options['autoRebuild']) ? $options['autoRebuild'] : !$container->params['productionMode'];
		$loader->setCacheStorage($container->cacheStorage);
		if (isset($options['directory'])) {
			$loader->addDirectory($options['directory']);
		} else {
			foreach (array('appDir', 'libsDir') as $var) {
				if (isset($container->params[$var])) {
					$loader->addDirectory($container->params[$var]);
				}
			}
		}
		$loader->register();
		return $loader;
	}



	/**
	 * @param Container $container
	 * @return Nette\Latte\Engine
	 */
	public static function createServiceLatteEngine(Container $container)
	{
		$engine = new Nette\Latte\Engine;

		foreach ($container->getParam('macros', array()) as $macroSet) {
			call_user_func(callback($macroSet), $engine->parser);
		}

		return $engine;
	}



	/**
	 * @param Nette\DI\Container $container
	 * @return Nette\Application\Routers\RouteList
	 */
	public static function createServiceRouter(Nette\DI\Container $container)
	{
		$router = new Nette\Application\Routers\RouteList;

		$router[] = $backend = new Nette\Application\Routers\RouteList('Backend');

			$backend[] = new Route('admin/[sign/in]', array(
				'presenter' => 'Sign',
				'action' => 'in',
			));

			$backend[] = new Route('admin/<presenter>[/<action>]', array(
				'action' => 'default',
			));

		foreach ($container->installWizard->getInstallers() as $installer) {
			$installer->installRoutes($router);
		}

		return $router;
	}



	/**
	 * @param Container $container
	 * @return Kdyby\Security\Authenticator
	 */
	public static function createServiceAuthenticator(Container $container)
	{
		return new Kdyby\Security\Authenticator($container->users);
	}



	/**
	 * @param Nette\DI\Container $container
	 * @return Kdyby\Http\User
	 */
	public static function createServiceUser(Nette\DI\Container $container)
	{
		$context = new Container;
		// copies services from $container and preserves lazy loading
		$context->lazyCopy('authenticator', $container);
		$context->lazyCopy('authorizator', $container);
		$context->lazyCopy('sqldb', $container);
		$context->addService('session', $container->session);

		return new Kdyby\Security\User($context);
	}

}