<?php

namespace MPF\Exception;

use \MPF\Text;

class FolderNotWritable extends \Exception
{

    public function __construct($foldername)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('folderNotWritable', array('Replace' => array('foldername' => $foldername))));
    }

}
