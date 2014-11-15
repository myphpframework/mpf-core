<?php

namespace MPF\Exception;

use \MPF\Text;

class FileNotWritable extends \Exception
{

    public function __construct($filename)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('fileNotWritable', array('Replace' => array('filename' => $filename))));
    }

}
