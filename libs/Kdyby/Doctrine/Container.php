<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Doctrine;

use Doctrine;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type as DoctrineTypes;
use Kdyby;
use Nette;
use Nette\DI;



/**
 * @author Patrik Votoček
 * @author Filip Procházka
 *
 * @property-read Nette\DI\Container $context
 * @property-read Cache $cache
 * @property-read Diagnostics\Panel $logger
 * @property-read Doctrine\ORM\Configuration $configurator
 * @property-read Doctrine\ORM\Mapping\Driver\AnnotationDriver $annotationDriver
 * @property-read Doctrine\DBAL\Event\Listeners\MysqlSessionInit $mysqlSessionInitListener
 * @property-read EventManager $eventManager
 * @property-read EntityManager $entityManager
 */
class Container extends Kdyby\DI\Container
{

	/** @var array */
	private static $types = array(
		'callback' => '\Kdyby\Doctrine\Types\Callback',
		'password' => '\Kdyby\Doctrine\Types\Password'
	);



	/**
	 * Registers doctrine types
	 */
	public function __construct(DI\Container $context)
	{
		$this->addService('context', $context);

		foreach (self::$types as $name => $className) {
			if (!DoctrineTypes::hasType($name)) {
				DoctrineTypes::addType($name, $className);
			}
		}
	}



	/**
	 * @return Cache
	 */
	protected function createServiceCache()
	{
		return new Cache($this->context->cacheStorage);
	}



	/**
	 * @return Diagnostics\Panel
	 */
	protected function createServiceLogger()
	{
		return Diagnostics\Panel::register();
	}



	/**
	 * @return Doctrine\ORM\Mapping\Driver\AnnotationDriver
	 */
	protected function createServiceAnnotationDriver()
	{
		$reader = new Doctrine\Common\Annotations\AnnotationReader();
		$reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
		// $reader->setAnnotationNamespaceAlias('Kdyby\Doctrine\Mapping\\', 'Kdyby');

		$dirs = $this->getParam('entityDirs', $this->context->getParam('entityDirs', array(APP_DIR, KDYBY_DIR)));
		return new Kdyby\Doctrine\Mapping\Driver\AnnotationDriver($reader, (array)$dirs);
	}



	/**
	 * @return Doctrine\ORM\Configuration
	 */
	protected function createServiceConfiguration()
	{
		$config = new Doctrine\ORM\Configuration;

		// Cache
		$config->setMetadataCacheImpl($this->hasService('metadataCache') ? $this->metadataCache : $this->cache);
		$config->setQueryCacheImpl($this->hasService('queryCache') ? $this->queryCache : $this->cache);

		// Metadata
		$config->setClassMetadataFactoryName('\Kdyby\Doctrine\Mapping\ClassMetadataFactory');
		$config->setMetadataDriverImpl($this->annotationDriver);

		// Proxies
		$proxiesDirDefault = $this->context->getParam('proxiesDir', $this->context->expand("%tempDir%/proxies"));
		$config->setProxyDir($this->getParam('proxiesDir', $proxiesDirDefault));
		$config->setProxyNamespace($this->getParam('proxyNamespace', 'Kdyby\Domain\Proxies'));
		if ($this->context->getParam('productionMode')) {
			$config->setAutoGenerateProxyClasses(FALSE);

		} else {
			$config->setAutoGenerateProxyClasses(TRUE);
			$config->setSQLLogger($this->logger);
		}

		return $config;
	}



	/**
	 * @return Doctrine\DBAL\Event\Listeners\MysqlSessionInit
	 */
	protected function createServiceMysqlSessionInitListener()
	{
		$database = $this->context->getParam('database', array());
		return new Doctrine\DBAL\Event\Listeners\MysqlSessionInit($database['charset']);
	}



	/**
	 * @return EventManager
	 */
	protected function createServiceEventManager()
	{
		$evm = new EventManager;
		foreach ($this->getParam('listeners', array()) as $listener) {
			$evm->addEventSubscriber($this->getService($listener));
		}

		$evm->addEventSubscriber(new Kdyby\Media\Listeners\Mediable($this->context));
		return $evm;
	}



	/**
	 * @return EntityManager
	 */
	protected function createServiceEntityManager()
	{
		$database = $this->context->getParam('database', array());

		if (key_exists('driver', $database) && $database['driver'] == "pdo_mysql" && key_exists('charset', $database)) {
			$this->eventManager->addEventSubscriber($this->mysqlSessionInitListener);
		}

		$this->freeze();
		return EntityManager::create((array)$database, $this->configuration, $this->eventManager);
	}



	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}



	/**
	 * @param string $entityName
	 * @return Kdyby\Model\EntityRepository
	 */
	public function getRepository($entityName)
	{
		return $this->getEntityManager()->getRepository($entityName);
	}

}