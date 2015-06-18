<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;
use MPF\REST\Service;

class InvalidService extends \MPF\REST\Service\Exception
{
    public $httpcode = Service::HTTPCODE_NOT_FOUND;
    public function __construct($service)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidService', array('Replace' => array('service' => $service))));
    }

}
