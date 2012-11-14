<?php

namespace MPF\Service\Exception;

use MPF\Text;

class MissingRequestFields extends \Exception {

    public function __construct($fields) {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceMissingRequestFields', array('Replace' => array('fields' => implode('","', $fields)))));
    }

}