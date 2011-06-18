<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Testing\Validation\Constraints;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka
 */
class FileExistsTest extends Kdyby\Testing\Test
{

	/** @var Kdyby\Validation\Constraints\FileExists */
	private $constraint;



	public function setUp()
	{
		$this->constraint = new Kdyby\Validation\Constraints\FileExists();
	}



	public function testEvaluate()
	{
		$this->assertTrue($this->constraint->evaluate(__FILE__));
	}

}