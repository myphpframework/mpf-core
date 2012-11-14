<?php
namespace MPF\Exception;

class InvalidXml extends \Exception
{
    public function  __construct($fileName)
    {
        // TODO: Need multi-language text system for exception. To Be Replace here...
        parent::__construct('The xml "'. $fileName .'" could not be parsed by simple_xml function.');
    }
}