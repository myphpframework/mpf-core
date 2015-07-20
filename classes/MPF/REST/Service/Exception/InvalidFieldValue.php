<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;
use MPF\REST\Service;

class InvalidFieldValue extends \MPF\REST\Service\Exception
{
    public $httpcode = Service::HTTPCODE_BAD_REQUEST;
    public $invalidFields = array();
    public function __construct($field, $values)
    {
        $this->invalidFields = $fields;
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidFieldValue', array('Replace' => array('field' => $field, 'choices' => implode('","', $values)))));
    }

}
