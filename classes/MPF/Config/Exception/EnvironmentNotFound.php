<?php

namespace MPF\Config\Exception;

use MPF\Text;

class EnvironmentNotFound extends \Exception
{

    public function __construct($type, $options)
    {
        parent::__construct('Enviroment "' . $type . '" does not exist in the config file! Must be either: '.implode(",", $options));
}

}
