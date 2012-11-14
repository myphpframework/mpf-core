<?php

namespace MPF\Db\Exception;

use MPF\Text;

class InvalidQuery extends \Exception {

    /**
     *
     * @var \MPF\Db\Result
     */
    public $result;

    public function __construct(\MPF\Db\Result $result) {
        $this->result = $result;
        parent::__construct(Text::byXml('mpf_exception')->get('dbInvalidQuery', array('Replace' => array('query' => $result->query, 'error' => $result->getError()))));
    }

}