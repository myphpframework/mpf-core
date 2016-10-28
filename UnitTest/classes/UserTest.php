<?php

use MPF\ENV;
use MPF\User;
require_once(__DIR__.'/../bootstrap.php');

try {
    ENV::bootstrap(ENV::DATABASE, 'default');
} catch (Exception $e) {}

require_once(PATH_MPF_CORE.'/classes/MPF/User.php');

class UserTest extends PHPUnit_Framework_TestCase {

    public function testUserSYSTEM() {
        $user = User::SYSTEM();
        $this->assertInstanceOf('MPF\User', $user, 'The function SYSTEM() should be returning an instance of the system user which should always be present.');

        $this->assertTrue(count($user->getStatuses()) == 0, 'The system user should not have any statuses.');
    }

    public function testUnknownUsers() {
        $user = User::byUsername('unknown');
        $this->assertNull($user, 'A user with the username "unknown" should NOT exists.');

        $user = User::byId(-1);
        $this->assertNull($user, 'A user with the id "-1" should NOT exists.');
    }

    public function testNewUser() {
        $db = \MPF\Db::byName('test');
        $result = $db->query('DELETE FROM `user` WHERE username LIKE "test";');
        $result->free();

        $newUser = User::create('test');
        $newUser->setPassword('test');
        $newUser->save();

        $user = User::byUsername('test');
        $this->assertInstanceOf('MPF\User', $user, 'A user with the username "test" should have been created.');
        $this->assertTrue(date('Y-m-d') == substr($user->getCreationDate(), 0, 10), 'The creation of the new user should be the one of today.');
        $this->assertTrue(User::STATUS_NOTAPPROVED == $user->getCurrentStatus()->getValue(), 'The status after a new user creation should be 50 (User::STATUS_NOTAPPROVED).');
        $this->assertTrue("test" == $user->getUsername(), 'The username of the new user should be "test".');

        $this->assertTrue($user->verifyPassword('test'), 'The password of the new user should be "test".');
        $this->assertFalse($user->verifyPassword('bob'), 'The password of the new user should NOT be "bob".');
        $this->assertFalse('test' == $user->getPassword(), 'The function getPassword should NOT return the password in clear text!');
        $this->assertTrue(date('Y-m-d') == substr($user->getLastLogin(), 0, 10), 'The last login of the new user should be the one of today.');

        $this->assertTrue(count($user->getGroups()) == 0, 'A new user should not be in any groups by default.');

        $this->assertFalse($user->addGroup(User\Group::ADMIN()), 'Unless a user in the admin group is logged in the user group ADMIN cant be assigned to a user.');

        $userById = User::byId($user->getId());
        $this->assertInstanceOf('MPF\User', $userById, 'A user with the username "test" should have been created.');

        $userByUsername = User::byUsername('test');
        $this->assertInstanceOf('MPF\User', $userByUsername, 'A user with the username "test" should have been created.');

    }
}