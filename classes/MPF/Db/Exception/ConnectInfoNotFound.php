<?php
namespace MPF\Db\Exception;
use MPF\Text;

class ConnectInfoNotFound extends \Exception
{
    public function  __construct($name, $dbType, $accessType)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbConnectInfoNotFound', array('Replace'=>array('name' => $name, 'dbType' => $dbType, 'accessType' => $accessType))));
    }
}