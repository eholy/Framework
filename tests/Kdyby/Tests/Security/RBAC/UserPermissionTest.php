<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Tests\Security\RBAC;

use Kdyby;
use Kdyby\Persistence\IDao;
use Kdyby\Security\RBAC as ACL;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class UserPermissionTest extends Kdyby\Tests\OrmTestCase
{

	public function setUp()
	{
		$this->createOrmSandbox(array(
			'Kdyby\Security\RBAC\BasePermission',
			'Kdyby\Security\RBAC\RolePermission',
			'Kdyby\Security\RBAC\UserPermission',
		));
	}



	/**
	 * @group database
	 */
	public function testPersisting()
	{
		$action = new ACL\Action("read");
		$resource = new ACL\Resource("article");
		$privilege = new ACL\Privilege($resource, $action);

		$division = new ACL\Division("blog");
		$division->addPrivilege($privilege);

		$role = new ACL\Role("reader", $division);
		$role->createPermission($privilege);

		$identity = new Kdyby\Security\Identity("HosipLan", "Nette", "hosiplan@gmail.com");
		$identity->addRole($role);
		$permission = $identity->overridePermission($role, $privilege)->setAllowed(FALSE);

		$this->getDao($identity)->save($identity, IDao::NO_FLUSH);
		$this->getDao($permission)->save($permission, IDao::NO_FLUSH);
		$this->getDao($division)->save($division);

		$this->assertEntityCount(1, 'Kdyby\Security\RBAC\Action');
		$this->assertEntityCount(1, 'Kdyby\Security\RBAC\Resource');
		$this->assertEntityCount(1, 'Kdyby\Security\RBAC\Privilege');
		$this->assertEntityCount(1, 'Kdyby\Security\RBAC\Division');
		$this->assertEntityCount(1, 'Kdyby\Security\RBAC\Role');
		$this->assertEntityCount(1, 'Kdyby\Security\Identity');
		$this->assertEntityCount(2, 'Kdyby\Security\RBAC\BasePermission');
	}

}
