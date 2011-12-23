<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Tests\Constraint;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class ObjectWithEventMock extends Nette\Object
{

	/** @var array */
	public $onEvent = array();



	public function foo()
	{
	}



	public static function staticFoo()
	{
	}



	public function __invoke()
	{
		return TRUE;
	}

}