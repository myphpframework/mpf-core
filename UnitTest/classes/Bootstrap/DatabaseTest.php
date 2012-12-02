<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Bootstrap\Database;

require_once(__DIR__.'/../../bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/InvalidConfig.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/UnsupportedType.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Bootstrap/Intheface.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Bootstrap/Database.php');

class Bootstrap_DatabaseTest extends PHPUnit_Framework_TestCase
{

    public function testInit_InvalidConfig()
    {
        $this->setExpectedException('MPF\Db\Exception\InvalidConfig');
        $bootstrap = new Database();
        $bootstrap->init(array('filename'=>'dbTestMissingNames'));
        $this->fail('If the config file as anomalies in it, like missing information, the init function is supposed to throw the Exception_Db_InvalidConfig.');
    }

    public function testInit_UnsupportedEngine()
    {
        $this->setExpectedException('MPF\Db\Exception\UnsupportedType');
        $bootstrap = new Database();
        $bootstrap->init(array('filename'=>'dbTestUnsupportedDbType'));
        $this->fail('If the config file as a db engine that is not supported its supposed to throw the Exception_Db_UnsupportedType.');
    }
}