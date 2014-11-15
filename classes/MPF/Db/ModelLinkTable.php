<?php

namespace MPF\Db;

use MPF\PhpDoc;
use MPF\Config;
use MPF\Logger;
use MPF\Status;

class ModelLinkTable
{

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

    /**
     * Saves all the provided link table entries
     * @param \MPF\Db\ModelLinkTable[] $entries
     */
    public static function saveAll($entries)
    {
        if (!empty($entries)) {
            $dbLayer = \MPF\Db::byName($entries[0]->database);
            $dbLayer->saveAllLinkTables($entries);
        }
    }

    public function __construct($knownFields, \MPF\Db\Field $targetField = null, $databaseName, $tableName)
    {
        $this->knownFields = $knownFields;
        $this->targetField = $targetField;
        $this->table = $tableName;
        $this->database = $databaseName;
    }

    public function save()
    {
        $dbLayer = \MPF\Db::byName($this->database);
        $dbLayer->saveLinkTable($this);
    }

    public function delete()
    {
        $dbLayer = \MPF\Db::byName($this->database);
        $dbLayer->deleteLinkTable($this);
    }

}
