<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Migrations;

use Doctrine;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 *
 * @Orm:Entity()
 * @Orm:Table(name="db_packages", uniqueConstraints={
 * 	@Orm:UniqueConstraint(columns={"name"})
 * })
 */
class PackageVersion extends Kdyby\Doctrine\Entities\IdentifiedEntity
{
	const STATUS_PRESENT = 'present';
	const STATUS_INSTALLED = 'installed';

	/**
	 * @Orm:Column(type="string")
	 * @var string
	 */
	private $name;

	/**
	 * @Orm:Column(type="string")
	 * @var string
	 */
	private $className;

	/**
	 * @Orm:Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $migrationVersion = 0;

	/**
	 * @Orm:Column(type="datetime", nullable=TRUE)
	 * @var \DateTime
	 */
	private $lastUpdate;

	/**
	 * @Orm:OneToMany(targetEntity="MigrationLog", mappedBy="package", cascade={"persist"})
	 * @var \Kdyby\Migrations\MigrationLog[]
	 */
	private $log;

	/**
	 * @Orm:Column(type="string")
	 * @var string
	 */
	private $status = self::STATUS_PRESENT;



	/**
	 * @param \Kdyby\Packages\Package $package
	 */
	public function __construct(Kdyby\Packages\Package $package)
	{
		$this->name = $package->getName();
		$this->className = get_class($package);
		$this->lastUpdate = new \DateTime;
		$this->log = new Doctrine\Common\Collections\ArrayCollection();
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return string
	 */
	public function getClassName()
	{
		return $this->className;
	}



	/**
	 * @return string
	 */
	public function getMigrationVersion()
	{
		return $this->migrationVersion;
	}



	/**
	 * @return \DateTime
	 */
	public function getLastUpdate()
	{
		return $this->lastUpdate;
	}



	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}



	/**
	 * @param string $status
	 */
	public function setStatus($status)
	{
		$constant = 'static::STATUS_' . strtoupper($status);
		if (!defined($constant)) {
			throw new Kdyby\InvalidArgumentException('Invalid PackageVersion status "' . $status . '" was given.');
		}

		$this->status = constant($constant);
	}



	/**
	 * @return \Kdyby\Migrations\History
	 */
	public function createHistory()
	{
		return new History($this, $this->migrationVersion);
	}



	/**
	 * @param \Kdyby\Migrations\Version $version
	 *
	 * @throws \Kdyby\Migrations\MigrationException
	 */
	public function setVersion(Version $version = NULL)
	{
		if ($version === NULL) {
			$this->migrationVersion = 0;
			$this->lastUpdate = new \DateTime();
			return;
		}

		if ($version->getVersion() === $this->migrationVersion) {
			return;
		}

		if ($version->getHistory()->getPackage() !== $this) {
			$packageClass = $version->getHistory()->getPackage()->getClassName();
			throw new MigrationException('Package of given version ' . get_class($version) . ' is not "' . $this->className . '", "' . $packageClass . '" given.');
		}

		$this->log[] = new MigrationLog($this, $version);
		$this->migrationVersion = (int)$version->getVersion();
		$this->lastUpdate = new \DateTime();
	}



	/**
	 * @return \Kdyby\Migrations\MigrationLog[]
	 */
	public function getMigrationsLog()
	{
		return $this->log->toArray();
	}

}