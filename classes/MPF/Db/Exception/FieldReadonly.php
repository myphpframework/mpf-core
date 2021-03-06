<?php

namespace MPF\Db\Exception;

use MPF\Text;

class FieldReadonly extends \Exception
{

    public function __construct($fieldName, $model)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbFieldReadonly', array('Replace' => array('fieldName' => $fieldName, 'model' => $model))));
    }

}
