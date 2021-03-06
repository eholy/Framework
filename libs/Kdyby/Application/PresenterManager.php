<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Application;

use Kdyby;
use Kdyby\Packages\PackageManager;
use Nette;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PresenterManager extends Nette\Application\PresenterFactory implements Nette\Application\IPresenterFactory
{

	/** @var \Nette\DI\Container */
	private $container;

	/** @var \Kdyby\Packages\PackageManager */
	private $packageManager;



	/**
	 * @param \Kdyby\Packages\PackageManager $packageManager
	 * @param \Nette\DI\Container $container
	 * @param string $appDir
	 */
	public function __construct(PackageManager $packageManager, Nette\DI\Container $container, $appDir)
	{
		parent::__construct($appDir, $container);

		$this->container = $container;
		$this->packageManager = $packageManager;
	}



	/**
	 * @param  string  presenter name
	 * @return string  class name
	 * @throws \Kdyby\Application\InvalidPresenterException
	 */
	public function getPresenterClass(& $name)
	{
		if (!is_string($name) || !Strings::match($name, "#^[a-zA-Z\x7f-\xff][a-zA-Z0-9\x7f-\xff:]*$#")) {
			throw InvalidPresenterException::invalidName($name);
		}

		if (!Strings::match($name, '~^[^:]+Package:[^:]+~i')) {
			return parent::getPresenterClass($name);
		}

		$serviceName = $this->formatServiceNameFromPresenter($name);
		if (method_exists($this->container, $method = 'create' . ucfirst($serviceName))) {
			$factoryRefl = $this->container->getReflection()->getMethod($method);
			if ($returnType  = $factoryRefl->getAnnotation('return')) {
				return $returnType; // todo: verify
			}

		} elseif ($this->container->hasService($serviceName)) {
			$reflection = new ClassType($this->container->getService($serviceName));
			return $reflection->getName();
		}

		list($package, $shortName) = explode(':', $name, 2);
		$package = $this->packageManager->getPackage($package);

		$class = $this->formatClassFromPresenter($shortName, $package);
		if (!class_exists($class)) {
			throw InvalidPresenterException::missing($shortName, $class);
		}

		$reflection = new ClassType($class);
		$class = $reflection->getName();

		if (!$reflection->implementsInterface('Nette\Application\IPresenter')) {
			throw InvalidPresenterException::doesNotImplementInterface($name, $class);
		}

		if ($reflection->isAbstract()) {
			throw InvalidPresenterException::isAbstract($name, $class);
		}

		// canonicalize presenter name
		if ($name !== $realName = $this->formatPresenterFromClass($class)) {
			if ($this->caseSensitive) {
				throw InvalidPresenterException::caseSensitive($name, $realName);

			} else {
				$name = $realName;
			}
		}

		return $class;
	}



	/**
	 * Finds presenter service in DI Container, or creates new object
	 * @param string $name
	 * @return \Nette\Application\IPresenter
	 */
	public function createPresenter($name)
	{
		/** @var \Nette\Application\UI\Presenter $presenter */
		$serviceName = $this->formatServiceNameFromPresenter($name);
		if (method_exists($this->container, $method = Nette\DI\Container::getMethodName($serviceName, FALSE))) {
			$presenter = $this->container->{$method}();

		} elseif ($this->container->hasService($serviceName)) {
			$presenter = $this->container->getService($serviceName);

		} else {
			$class = $this->getPresenterClass($name);
			$presenter = $this->container->createInstance($class);
		}

		if (method_exists($presenter, 'setTemplateConfigurator') && $this->container->hasService('kdyby.templateConfigurator')) {
			$presenter->setTemplateConfigurator($this->container->kdyby->templateConfigurator);
		}

		if (method_exists($presenter, 'setContext')) {
			$presenter->setContext($this->container);
		}

		foreach (array_reverse(get_class_methods($presenter)) as $method) {
			if (substr($method, 0, 6) === 'inject') {
				$this->container->callMethod(array($presenter, $method));
			}
		}

		if ($presenter instanceof Nette\Application\UI\Presenter && $presenter->invalidLinkMode === NULL) {
			$presenter->invalidLinkMode = $this->container->parameters['debugMode']
				? UI\Presenter::INVALID_LINK_WARNING
				: UI\Presenter::INVALID_LINK_SILENT;
		}

		return $presenter;
	}



	/**
	 * @param string $presenterClass
	 * @return \Kdyby\Packages\Package
	 */
	public function getPresenterPackage($presenterClass)
	{
		foreach ($this->packageManager->getPackages() as $package) {
			if (Strings::startsWith($presenterClass, $package->getNamespace())) {
				return $package;
			}
		}

		throw new Kdyby\InvalidArgumentException("Presenter $presenterClass does not belong to any active package.");
	}



	/**
	 * Formats service name from it's presenter name
	 *
	 * 'Bar:Foo:FooBar' => 'bar_foo_fooBarPresenter'
	 *
	 * @param string $presenter
	 * @return string
	 */
	public function formatServiceNameFromPresenter($presenter)
	{
		return Strings::replace($presenter, '/(^|:)+(.)/', function ($match) {
			return (':' === $match[1] ? '.' : '') . strtolower($match[2]);
		}) . 'Presenter';
	}



	/**
	 * Formats presenter name from it's service name
	 *
	 * 'bar_foo_fooBarPresenter' => 'Bar:Foo:FooBar'
	 *
	 * @param string $name
	 * @return string
	 */
	public function formatPresenterFromServiceName($name)
	{
		return Strings::replace(substr($name, 0, -9), '/(^|\\.)+(.)/', function ($match) {
			return ('.' === $match[1] ? ':' : '') . strtoupper($match[2]);
		});
	}



	/**
	 * Formats presenter class to it's name
	 *
	 * 'Kdyby\BarPackage\Presenter\FooFooPresenter' => 'Bar:FooFoo'
	 * 'Kdyby\BarPackage\Presenter\FooModule\FooBarPresenter' => 'Bar:Foo:FooBar'
	 *
	 * @param string $class
	 * @return string
	 */
	public function formatPresenterFromClass($class)
	{
		$package = $this->getPresenterPackage($class);
		$presenter = substr($class, strlen($package->getNamespace() . '\\Presenter\\'));
		return $package->getName() . ':' . $this->unformatPresenterClass($presenter);
	}



	/**
	 * @param string $presenter
	 * @param \Kdyby\Packages\Package $package
	 */
	public function formatClassFromPresenter($presenter, Kdyby\Packages\Package $package)
	{
		return $package->getNamespace() . '\\Presenter\\' . $this->formatPresenterClass($presenter);
	}

}
