<?php

namespace MPF\Db\Exception;

use MPF\Text;

class TooManyPrimaryKeys extends \Exception {

    /**
     *
     * @var \MPF\Db\Result
     */
    public $result;

    public function __construct() {
        $this->result = $result;
        parent::__construct(Text::byXml('mpf_exception')->get('dbModelTooManyPrimaryKeys'));
    }

}