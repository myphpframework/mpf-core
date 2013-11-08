<?php
namespace MPF\Config\Exception;

class FileNotFound extends \Exception
{
    public function  __construct($fileName,$paths)
    {
        // TODO: Need multi-language text system for exception. To Be Replace here...
        parent::__construct('The config "'. $fileName .'" could not be found in:'."\n".implode("\n", $paths));
    }
}