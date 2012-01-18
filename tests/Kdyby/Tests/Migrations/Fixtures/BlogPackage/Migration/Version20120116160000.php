<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Migrations\Fixtures\BlogPackage\Migration;

use Doctrine\DBAL\Schema\Schema;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class Version20120116160000 extends Kdyby\Migrations\AbstractMigration
{

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $schema
	 */
	public function up(Schema $schema)
	{
		$this->addSql("UPDATE articles SET content='cars are way more cool!' WHERE title='cars'");
	}



	/**
	 * @param \Doctrine\DBAL\Schema\Schema $schema
	 */
	public function down(Schema $schema)
	{
		$this->addSql("UPDATE articles SET content='cars are fun' WHERE title='cars'");
	}

}
