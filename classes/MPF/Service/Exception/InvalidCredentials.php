<?php

namespace MPF\Service\Exception;

use MPF\Text;

class InvalidCredentials extends \Exception {

    public function __construct($method) {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidCredentials'));
    }

}