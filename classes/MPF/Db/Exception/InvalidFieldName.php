<?php
namespace MPF\Db\Exception;
use MPF\Text;

class InvalidFieldName extends \Exception
{
    public function  __construct($fieldName, $model)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidFieldName', array('Replace'=>array('fieldName'=>$fieldName, 'model'=>$model))));
    }
}