<?php

namespace MPF\Db;

use MPF\PhpDoc;
use MPF\Config;
use MPF\Logger;
use MPF\Status;

class Page
{

    /**
     *
     * @var integer
     */
    public $number;

    /**
     *
     * @var integer
     */
    public $amount;

    /**
     *
     * @var integer
     */
    public $total;

    public function __construct($number, $amount)
    {
        $this->number = $number;
        $this->amount = $amount;
    }

}
