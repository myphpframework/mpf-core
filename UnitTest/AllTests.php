<?php

require_once('bootstrap.php');
require_once('classes/ConfigTest.php');
require_once('classes/ENVTest.php');
require_once('classes/LocaleTest.php');
require_once('classes/SessionTest.php');
require_once('classes/TemplateTest.php');
require_once('classes/TextTest.php');

//require_once('classes/Db/ResultSetTest.php');
require_once('classes/Db/Layer/MySQLiTest.php');
//require_once('classes/Db/Layer/PostgreSQLTest.php');
//require_once('classes/Db/Layer/SQLiteTest.php');
require_once('classes/Db/ModelTest.php');

class Framework_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTestSuite('ConfigTest');
        $suite->addTestSuite('ENVTest');
        $suite->addTestSuite('SessionTest');
        $suite->addTestSuite('TemplateTest');
        $suite->addTestSuite('TextTest');
        $suite->addTestSuite('LocaleTest');

        $suite->addTestSuite('Db_Layer_MySQLiTest');
        //$suite->addTestSuite('Db_Layer_PostgreSQLTest');
        //$suite->addTestSuite('Db_Layer_SQLiteTest');
        $suite->addTestSuite('ModelTest');

        return $suite;
    }
}
