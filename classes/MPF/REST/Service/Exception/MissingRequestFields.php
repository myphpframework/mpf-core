<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;
use MPF\REST\Service;

class MissingRequestFields extends \MPF\REST\Service\Exception
{
    public $httpcode = Service::HTTPCODE_BAD_REQUEST;
    public $invalidFields = array();
    public function __construct($fields)
    {
        $this->invalidFields = $fields;
        parent::__construct(Text::byXml('mpf_exception')->get('serviceMissingRequestFields', array('Replace' => array('fields' => implode('","', $fields)))));
    }

}
