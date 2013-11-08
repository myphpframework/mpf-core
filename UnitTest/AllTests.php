<?php

require_once('bootstrap.php');
require_once('classes/UserTest.php');

require_once('classes/ConfigTest.php');
require_once('classes/ENVTest.php');
require_once('classes/LocaleTest.php');
require_once('classes/SessionTest.php');
require_once('classes/TemplateTest.php');
require_once('classes/TextTest.php');

require_once('classes/Db/ResultSetTest.php');
require_once('classes/Db/Layer/MySQLiTest.php');
require_once('classes/Db/Layer/PostgreSQLTest.php');
require_once('classes/Db/Layer/SQLiteTest.php');
require_once('classes/Db/ModelTest.php');
require_once('classes/Db/ModelStatusTest.php');
require_once('classes/Db/EntryTest.php');

require_once('classes/Bootstrap/SessionTest.php');
require_once('classes/Bootstrap/TemplateTest.php');
require_once('classes/Bootstrap/DatabaseTest.php');

class Framework_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();

        $suite->addTestSuite('UserTest');

        $suite->addTestSuite('ConfigTest');
        $suite->addTestSuite('SessionTest');
        $suite->addTestSuite('TemplateTest');
        $suite->addTestSuite('LocaleTest');
        $suite->addTestSuite('TextTest');

        $suite->addTestSuite('Db_Layer_MySQLiTest');
        $suite->addTestSuite('Db_Layer_PostgreSQLTest');
        $suite->addTestSuite('Db_Layer_SQLiteTest');
        $suite->addTestSuite('ModelTest');
        $suite->addTestSuite('ModelStatusTest');
        $suite->addTestSuite('EntryTest');

        $suite->addTestSuite('Bootstrap_SessionTest');
        $suite->addTestSuite('Bootstrap_TemplateTest');
        $suite->addTestSuite('Bootstrap_DatabaseTest');

        $suite->addTestSuite('ENVTest');
        return $suite;
    }
}
