<?php

namespace MPF\Exception;

class InvalidDateRange extends \Exception
{

    public function __construct($startDate, $endDate)
    {
        // TODO: Need multi-language text system for exception. To Be Replace here... Rethink how to pass the date name.. the textid for multi-language text?
        parent::__construct('The date range is invalid, the start date "' . $startDate . '" should be smaller than the end date "' . $endDate . '".');
    }

}
