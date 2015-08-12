<?php

namespace MPF\Text\Exception;

use MPF\Text;

class IdNotFound extends \Exception
{

    public function __construct($id, $ids)
    {
        $ids = implode(",", $ids) . "\n";
        parent::__construct('The text id "'.$id.'" was not found. Available IDs are: '.$ids);
    }

}
