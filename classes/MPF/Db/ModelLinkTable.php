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
    public $knownFields;

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

    public function __construct($knownFields, \MPF\Db\Field $targetField, $databaseName, $tableName) {
        $this->knownFields = $knownFields;
        $this->targetField = $targetField;
        $this->table = $tableName;
        $this->database = $databaseName;
    }
}