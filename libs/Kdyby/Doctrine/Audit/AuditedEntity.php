<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Doctrine\Audit;

use Doctrine\Common\Annotations\Annotation;
use Kdyby;
use Nette;



/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class AuditedEntity extends Annotation
{

	/**
	 * @var array<string>
	 */
	public $related = array();

}
