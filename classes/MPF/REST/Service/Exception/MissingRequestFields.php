<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;

class MissingRequestFields extends \MPF\REST\Service\Exception {

    public function __construct($fields) {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceMissingRequestFields', array('Replace' => array('fields' => implode('","', $fields)))));
    }

}