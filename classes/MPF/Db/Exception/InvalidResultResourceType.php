<?php

namespace MPF\Db\Exception;

use MPF\Text;

class InvalidResultResourceType extends \Exception
{

    public function __construct($resource, $expected)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidResultResourceType', array('Replace' => array('expected' => $expected, 'actual' => print_r($resource, true)))));
    }

}
