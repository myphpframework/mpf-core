<?php
namespace MPF\Db\Exception;
use MPF\Text;

class InvalidConfig extends \Exception
{
    public function  __construct($filename)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidConfig', array('Replace'=>array('filename'=>$filename))));
    }
}