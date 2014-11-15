<?php

namespace MPF\Db\Exception;

use MPF\Text;

class DuplicateEntry extends \Exception
{

    public function __construct(\MPF\Db\Result $result, $table)
    {
        preg_match_all("/'(.*?)'/", $result->getError(), $matches);
        parent::__construct(Text::byXml('mpf_exception')->get('dbDuplicateEntry', array('Replace' =>
                    array(
                        'value' => $matches[1][0],
                        'key' => $matches[1][1],
                        'database' => $result->getConnection()->database,
                        'table' => $table,
                    )
        )));
    }

}
