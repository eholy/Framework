<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\EventDispatcher;

use Kdyby;
use Kdyby\EventDispatcher\EventArgs;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class EventArgsTest extends Kdyby\Tests\TestCase
{

	public function testImplementsDoctrineEventArgs()
	{
		$args = new EventArgsMock();
		$this->assertInstanceOf('Doctrine\Common\EventArgs', $args);
	}

}