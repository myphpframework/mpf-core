<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Session;

require_once(__DIR__.'/../bootstrap.php');

class SessionTest extends PHPUnit_Framework_TestCase
{
    public function testGetLocale()
    {
        $locale = Session::getLocale();
        $this->assertInstanceOf('MPF\Locale', $locale);
    }

    public function testGetLocaleFromCookie()
    {
        $_COOKIE['mpf_locale'] = 'en_CA';
        $locale = Session::getLocale();
        $this->assertInstanceOf('MPF\Locale', $locale);
    }
}