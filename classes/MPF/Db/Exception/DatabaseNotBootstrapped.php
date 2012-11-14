<?php

namespace MPF\Db\Exception;

use MPF\Text;

class DatabaseNotBootstrapped extends \Exception {
   public function __construct() {
        parent::__construct(Text::byXml('mpf_exception')->get('dbDatabaseNotBootstrapped'));
    }

}