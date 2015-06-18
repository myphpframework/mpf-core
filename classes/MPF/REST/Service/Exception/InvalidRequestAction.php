<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;
use MPF\REST\Service;

class InvalidRequestAction extends \MPF\REST\Service\Exception
{
    public $httpcode = Service::HTTPCODE_BAD_REQUEST;
    public function __construct($action)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidRequestAction', array('Replace' => array('action' => $action))));
    }

}
