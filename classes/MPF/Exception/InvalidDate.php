<?php
namespace MPF\Exception;

class InvalidDate extends \Exception
{
    public function  __construct($dateName, $userInput)
    {
        // TODO: Need multi-language text system for exception. To Be Replace here... Rethink how to pass the date name.. the textid for multi-language text?
        parent::__construct('The '. $dateName .' "'.$userInput.'" is invalid as it could not be parsed by the php function strtotime');
    }
}