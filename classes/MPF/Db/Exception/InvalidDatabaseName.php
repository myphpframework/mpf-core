<?php

namespace MPF\Db\Exception;

use MPF\Text;

class InvalidDatabaseName extends \Exception
{

    public function __construct($name)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidDatabaseName', array('Replace' => array('name' => $name))));
    }

}
