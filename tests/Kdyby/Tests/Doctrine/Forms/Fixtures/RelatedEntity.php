<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Doctrine\Forms\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @ORM\Entity()
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class RelatedEntity extends SharedFieldsEntity
{

	/**
	 * @ORM\Column(type="string")
	 */
	public $name;

	/**
	 * @ORM\ManyToOne(targetEntity="RootEntity", inversedBy="children", cascade={"persist"})
	 * @var \Kdyby\Tests\Doctrine\Forms\Fixtures\RootEntity
	 */
	public $daddy;

	/**
	 * @ORM\ManyToMany(targetEntity="RootEntity", mappedBy="buddies", cascade={"persist"})
	 * @var \Kdyby\Tests\Doctrine\Forms\Fixtures\RootEntity[]
	 */
	public $buddies;



	/**
	 * @param string $name
	 * @param \Kdyby\Tests\Doctrine\Forms\Fixtures\RootEntity $daddy
	 */
	public function __construct($name = NULL, RootEntity $daddy = NULL)
	{
		$this->name = $name;
		$this->daddy = $daddy;
		$this->buddies = new ArrayCollection();
	}

}
