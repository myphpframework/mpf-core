<?php
namespace MPF\Db\Exception;
use MPF\Text;

class InvalidAccessType extends \Exception
{
    public function  __construct($accessType)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidAccessType', array('Replace'=>array('accessType'=>$accessType))));
    }
}