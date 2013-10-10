<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;

class InvalidCredentials extends \MPF\REST\Service\Exception {

    public function __construct() {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidCredentials'));
    }

}