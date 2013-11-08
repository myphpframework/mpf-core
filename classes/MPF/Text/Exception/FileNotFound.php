<?php

namespace MPF\Text\Exception;

use MPF\Session;
use MPF\Text;

class FileNotFound extends \Exception {

    public function __construct($filename, $paths) {
        parent::__construct("The text file '$filename' could not be found in: " . implode(',', $paths));
    }
}