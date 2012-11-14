<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Db;

require_once(__DIR__.'/../../../bootstrap.php');
require_once(__DIR__.'/MySQLiTest.php');

class Db_Layer_SQLiteTest extends Db_Layer_MySQLiTest
{
    public function setUp()
    {
        $this->dbType = Db::TYPE_SQLITE;
    }
}