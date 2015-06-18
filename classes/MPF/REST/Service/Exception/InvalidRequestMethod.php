<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;
use MPF\REST\Service;

class InvalidRequestMethod extends \MPF\REST\Service\Exception
{
    public $httpcode = Service::HTTPCODE_METHOD_NOT_ALLOWED;
    public function __construct($method)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidRequestMethod', array('Replace' => array('method' => $method))));
    }

}
