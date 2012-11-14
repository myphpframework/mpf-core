<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Db;

require_once(__DIR__.'/../../../bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/Db/Exception/UnsupportedType.php');
require_once(PATH_FRAMEWORK.'classes/Bootstrap/Intheface.php');
require_once(PATH_FRAMEWORK.'classes/Bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/Bootstrap/Database.php');

require_once(PATH_FRAMEWORK.'classes/Db/Connection.php');
require_once(PATH_FRAMEWORK.'classes/Db/Layer/Intheface.php');

require_once(PATH_FRAMEWORK.'classes/Db/Exception/InvalidQuery.php');

require_once(PATH_FRAMEWORK.'classes/Db/Layer/MySQLi.php');
require_once(PATH_FRAMEWORK.'classes/Db/Connection/MySQLi.php');

require_once(PATH_FRAMEWORK.'classes/Db/Layer/PostgreSQL.php');
require_once(PATH_FRAMEWORK.'classes/Db/Connection/PostgreSQL.php');

require_once(PATH_FRAMEWORK.'classes/Db/Layer/SQLite.php');
require_once(PATH_FRAMEWORK.'classes/Db/Connection/SQLite.php');

ENV::bootstrap(ENV::DATABASE, array('filename'=>'dbTest'));

class Db_Layer_MySQLiTest extends PHPUnit_Framework_TestCase
{
    protected $dbType = null;
    public function setUp()
    {
        $this->dbType = Db::TYPE_MYSQLI;
    }

    public function testQuery_returnResult()
    {
        $db = Db::byName('test', $this->dbType);
        $result = $db->query('SELECT 1 as test');
        $this->assertInstanceOf('MPF\Db\Result', $result, 'The function Query must always return an object \MPF\Db\Result');
        $result->free();
    }

    public function testBadQuery_returnResultFailed()
    {
        $this->setExpectedException('MPF\Db\Exception\InvalidQuery');
        $db = Db::byName('test', $this->dbType);
        
        $result = $db->query('dfdfghdg dfgdg sdfghdfgh');
        $this->fail('The object query should have thrown the Exception_Db_InvalidQuery if the query is invalid.');
        $result->free();
    }

    public function testFetch_returnEntry()
    {
        $db = Db::byName('test', $this->dbType);

        $loop = 0;
        $maxLoop = 10;
        $result = $db->query('SELECT 1 as test');
        while($entry = $result->fetch())
        {
            $this->assertInstanceOf('MPF\Db\Entry', $entry, 'The function fetch must always return an object \MPF\Db\Entry');

            $loop++;
            if ($loop > $maxLoop)
            {
                $this->fail('The max loop as been reached, infinite loop?');
            }
        }
        $result->free();
    }

    public function testRowsTotal_returnInteger()
    {
        $db = Db::byName('test', $this->dbType);
        $result = $db->query('SELECT 1 as test');
        $this->assertTrue((is_int($result->rowsTotal) && $result->rowsTotal == 1), 'The property roawsTotal of the object \MPF\Db\Result must always be an integer');
        $result->free();
    }

    public function testRowsAffected_returnInteger()
    {
        $db = Db::byName('test', $this->dbType);

        $result = $db->query('UPDATE test SET field3 = "Unittesting AffectedRows rand:'.rand(0, 10000).'" WHERE field1 = 1');
        $this->assertTrue((is_int($result->rowsAffected) && $result->rowsAffected != 0), 'The property rowsAffected of the object \MPF\Db\Result must always be an integer');
        $result->free();
    }

    public function testFetchMultiple_returnNoAvailableConnection()
    {
        $db = Db::byName('test', $this->dbType);

        $loop = 0;
        $maxLoop = 10;
        $result = $db->query('SELECT 1 as test');
        while($entry = $result->fetch())
        {
            $result2 = $db->query('SELECT 2 as test');
            while($entry2 = $result2->fetch())
            {
                $this->assertEquals(1, (int)$entry['test'], 'The result of the second query1 should be 1 not "'.$entry['test'].'".');
            }

            $loop++;
            if ($loop > $maxLoop)
            {
                $this->fail('The max loop as been reached, infinite loop?');
            }
            $result2->free();
        }
        $result->free();
    }

}