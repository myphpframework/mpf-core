<?php

namespace MPF\Config\Exception;

use MPF\Text;

class EnvironmentNotFound extends \Exception
{

    public function __construct()
    {
        parent::__construct(Text::byXml('mpf_exception')->get('configUnknownEnvironment', array('Replace' => array('mpf_env' => \MPF\Env::getType()))));
    }

}
