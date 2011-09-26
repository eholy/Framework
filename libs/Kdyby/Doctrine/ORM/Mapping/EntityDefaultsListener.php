<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Doctrine\ORM\Mapping;

use Doctrine;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Kdyby;
use Nette;
use Nette\Reflection\ClassType;



/**
 * @author Filip Procházka
 */
class EntityDefaultsListener extends Nette\Object implements Doctrine\Common\EventSubscriber
{

	/**
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			Events::loadClassMetadata,
		);
	}



	/**
	 * @param LoadClassMetadataEventArgs $args
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $args)
	{
		$meta = $args->getClassMetadata();
		if ($meta->isMappedSuperclass) {
			return;
		}

		if (!$meta->customRepositoryClassName) {
			$meta->setCustomRepositoryClass('Kdyby\Doctrine\ORM\Dao');
		}

		$refl = new ClassType($meta->customRepositoryClassName);
		if (!$refl->implementsInterface('Kdyby\Doctrine\IDao')) {
			throw new Nette\InvalidStateException("Your repository class for entity '" . $meta->name . "' should extend 'Kdyby\\Doctrine\\ORM\\Dao'.");
		}
	}

}