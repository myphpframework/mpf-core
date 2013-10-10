<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;

class InvalidRequestAction extends \MPF\REST\Service\Exception {

    public function __construct($action) {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidRequestAction', array('Replace' => array('action' => $action))));
    }

}