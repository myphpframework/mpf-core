<?php

use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Session;

require_once(__DIR__ . '/../bootstrap.php');

ENV::bootstrap(ENV::DATABASE, 'dbTestMySQL');
ENV::bootstrap(ENV::SESSION);

class SessionTest extends PHPUnit_Framework_TestCase {

    public function testGetLocale() {
        $locale = Session::getLocale();
        $this->assertInstanceOf('MPF\Locale', $locale);
    }

    public function testGetLocaleFromCookie() {
        $_COOKIE['mpf_locale'] = 'en_CA';
        $locale = Session::getLocale();
        $this->assertInstanceOf('MPF\Locale', $locale);
    }

    public function testUserSession() {
        $user = Session::getUser();
        $this->assertNull($user);
    }

    public function testGetSessionVar() {
        $_SESSION['test'] = 'test';
        $test = Session::get('test');
        $this->assertEquals('test', $test, 'Session variable test should have content "test"');

        Session::destroy();
        $test = Session::get('test');
        $this->assertNotEquals('test', $test, 'Session variable test should NOT have content "test"');
    }
}