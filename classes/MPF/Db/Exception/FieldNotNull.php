<?php
namespace MPF\Db\Exception;
use MPF\Text;

class FieldNotNull extends \Exception
{
    public function  __construct($fieldName, $model)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbFieldNotNull', array('Replace'=>array('fieldName'=>$fieldName, 'model'=>$model))));
    }
}