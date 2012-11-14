<?php

namespace MPF\Service\Exception;

use MPF\Text;

class InvalidRequestAction extends \Exception {

    public function __construct($action) {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidRequestAction', array('Replace' => array('action' => $action))));
    }

}