<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Application;

use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class BadRequestException extends Nette\Application\BadRequestException
{


	/**
	 * @return BadRequestException
	 */
	public static function notAllowed()
	{
		return new static("You're not allowed to see this page.", 403);
	}



	/**
	 * @return BadRequestException
	 */
	public static function nonExisting()
	{
		return new static("This page does not really exist.", 404);
	}

}
