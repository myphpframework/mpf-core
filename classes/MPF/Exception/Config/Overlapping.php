<?php
namespace MPF\Exception\Config;
use MPF\Text;

class Overlapping extends \Exception
{
    public function  __construct($config1)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('configOverlapping', array('Replace' => array('config1' => $config1))));
    }
}