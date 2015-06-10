<?php

namespace MPF\REST\Service\Exception;

use MPF\Text;

class InvalidService extends \MPF\REST\Service\Exception
{

    public function __construct($service)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('serviceInvalidService', array('Replace' => array('service' => $service))));
    }

}
