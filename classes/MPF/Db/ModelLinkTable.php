<?php

namespace MPF\Db;

use MPF\PhpDoc;
use MPF\Config;
use MPF\Logger;
use MPF\Status;

class ModelLinkTable {
    /**
     *
     * @var \MPF\Db\Field
     */
    public $knownField;

    /**
     *
     * @var \MPF\Db\Field
     */
    public $targetField;

    /**
     *
     * @var string
     */
    public $table;

    /**
     *
     * @var string
     */
    public $database;

    public function __construct(\MPF\Db\Field $knownField, \MPF\Db\Field $targetField, $databaseName, $tableName) {
        $this->knownField = $knownField;
        $this->targetField = $targetField;
        $this->table = $tableName;
        $this->database = $databaseName;
    }
}