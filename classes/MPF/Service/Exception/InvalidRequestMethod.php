<?php

namespace MPF\Service\Exception;

use MPF\Text;

class InvalidRequestMethod extends \Exception {

    public function __construct($method) {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidRequestMethod', array('Replace' => array('method' => $method))));
    }

}