<?php
namespace MPF\Exception\Bootstrap;
use MPF\Text;

class UnsupportedType extends \Exception
{
    public function  __construct($type)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('bootstrapUnsupportedType', array('Replace' => array('type' => $type))));
    }
}