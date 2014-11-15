<?php

namespace MPF\Db\Exception;

use MPF\Text;

class InvalidHashAlgo extends \Exception
{

    public function __construct($algo)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidHashAlgo', array('Replace' =>
                    array(
                        'algo' => $algo,
                        'algoList' => implode('", "', hash_algos()))
        )));
    }

}
