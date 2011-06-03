<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Forms\Mapping\FieldTypes;

use Kdyby;
use Kdyby\Forms\Mapping;
use Nette;



/**
 * @author Filip Procházka
 */
class CallbackType extends Nette\Object implements Mapping\IFieldType
{

	/**
	 * @param array $value
	 * @param array $current
	 * @return array
	 */
	public function load($value, $current)
	{
		return callback($value);
	}



	/**
	 * @param array $value
	 * @return array
	 */
	public function save($value)
	{
		return (string)$value;
	}

}