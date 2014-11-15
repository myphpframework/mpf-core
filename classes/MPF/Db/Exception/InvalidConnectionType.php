<?php

namespace MPF\Db\Exception;

use MPF\Text;

class InvalidConnectionType extends \Exception
{

    public function __construct(\MPF\Db\Connection $connection, $expected)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidConnectionType', array('Replace' => array('expected' => $expected, 'actual' => get_class($connection)))));
    }

}
