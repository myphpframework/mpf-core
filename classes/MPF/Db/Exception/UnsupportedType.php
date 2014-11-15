<?php

namespace MPF\Db\Exception;

use MPF\Text;

class UnsupportedType extends \Exception
{

    public function __construct($type)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbUnsupportType', array('Replace' => array('type' => $type))));
    }

}
