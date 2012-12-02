<?php

namespace MPF\Db;

use MPF\PhpDoc;
use MPF\Config;
use MPF\Logger;
use MPF\Status;

abstract class ModelStatus extends Model {

    /**
     * Null for lazy load
     * @var \MPF\Status[]
     */
    protected $statuses = null;
    protected $allowedChanges = array();

    /**
     * Returns the default status
     *
     * @return \MPF\Status
     */
    abstract protected function getDefaultStatus();

    /**
     * Sets a new status for the object
     *
     * @param \MPF\Status $status
     */
    public function setStatus(\MPF\Status $status) {
        $statusField = $this->getStatusField();
        $status->table = $statusField->getTable();
        $status->database = $statusField->getDatabase();

        if ($this->statuses === null) {
            $this->statuses = array();
        }

        $this->statuses[] = $status;
    }

    /**
     * Returns the current status
     *
     * @return \MPF\Status
     */
    public function getCurrentStatus() {
        $this->loadStatuses();
        return end($this->statuses);
    }

    /**
     * Returns the statuses
     *
     * @return \MPF\Status[]
     */
    public function getStatuses() {
        $this->loadStatuses();
        return $this->statuses;
    }

    public function save() {
        $dbLayer = \MPF\Db::byName($this->getDatabaseName());
        $dbLayer->transactionStart();

        try {
            parent::save();

            // if we didnt alter the statuses we dont need to do anything
            if (is_array($this->statuses)) {

                // if there is no statuses we add the default one
                if (empty($this->statuses)) {
                    $this->setStatus($this->getDefaultStatus());
                }

                // we save all the new status that are not saved
                foreach ($this->statuses as $status) {
                    // only save the status if its a new one
                    if ($status->isNew()) {
                        $status->save();
                    }
                }
            }
        } catch (\Exception $e) {
            $dbLayer->transactionRollback();
            throw $e;
        }

        $dbLayer->transactionCommit();
    }

    final private function loadStatuses() {
        // if there is no status we try to load em
        if ($this->statuses === null) {
            $this->statuses = array();
            $statusField = $this->getStatusField();
            $fields = $this->getPrimaryFields();
            $field = Status::generateField('foreignId', $fields[0]->getValue(), array(
                PhpDoc::CLASS_TABLE => $statusField->getTable(),
                PhpDoc::CLASS_DATABASE => $statusField->getDatabase(),
            ));
            $result = Status::byField($field);
            while($status = $result->fetch()) {
                $this->statuses[] = $status;
            }
            $result->free();
        }
    }

    /**
     *
     * @return \MPF\Db\Field
     */
    final protected function getStatusField() {
        foreach (self::$phpdoc[$this->className]['properties'] as $name => $property) {
            if (empty($property) || !isSet($property[ PhpDoc::PROPERTY_MODEL ]) || !isSet($property[ PhpDoc::PROPERTY_TABLE ]) ) {
                continue;
            }

            if (property_exists($this, $name) && $property['declaringClass'] == $this->className) {
                $statusFields[] = new \MPF\Db\Field(self::$phpdoc[$this->className]['class'], $name, $this->$name, $property);
            }
        }

        if (empty($statusFields) || count($statusFields) > 1) {
            $exception = new ModelMissingPhpDoc($this->className, 'status');
            Logger::Log('Db/ModelStatus', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        return $statusFields[0];
    }

}