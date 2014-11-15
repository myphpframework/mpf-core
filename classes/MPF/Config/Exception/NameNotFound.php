<?php

namespace MPF\Config\Exception;

class NameNotFound extends \Exception
{

    public function __construct($name, $filename)
    {
        // TODO: Need multi-language text system for exception. To Be Replace here...
        parent::__construct('The config name "' . $name . '" could not be found in the config faile "' . $filename . '".');
    }

}
