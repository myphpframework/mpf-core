<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;
use MPF\REST\Service;

class InvalidCredentials extends \MPF\REST\Service\Exception
{
    public $httpcode = Service::HTTPCODE_UNAUTHORIZED;
    public function __construct()
    {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidCredentials'));
    }

}
