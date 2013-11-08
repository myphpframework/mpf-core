<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Locale;
use MPF\Language;
use MPF\Country;

require_once(__DIR__.'/../bootstrap.php');

class LocaleTest extends PHPUnit_Framework_TestCase
{
    public function testGetCode()
    {
        $locale = new Locale('en_CA');
        $code = $locale->getCode();
        $this->assertTrue(!empty($code), 'The return of getCode() should not be an empty string');
    }

    public function testToString()
    {
        try
        {
            $locale = new Locale('en_CA');
            $tmp = 'toString:'.$locale;
        }
        catch (Exception $e)
        {
            $this->fail('The object Locale should have a __toString');
        }
    }

    public function testToString_returnEmpty()
    {
        $locale = new Locale('en_CA');
        $return = $locale->__toString();
        $this->assertTrue(!empty($return), 'The return of __toString should not be an empty string');
    }

}