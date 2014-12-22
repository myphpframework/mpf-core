<?php

namespace MPF\Db;

use MPF\PhpDoc;
use MPF\Config;
use MPF\Log\Category;
use MPF\Status;

abstract class ModelStatus extends Model
{

    /**
     * Null for lazy load
     * @var \MPF\Status[]
     */
    protected $statuses = null;
    protected $statusCount = 0;
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
    public function setStatus(\MPF\Status $status)
    {
        $statusField = $this->getStatusField();
        $status->table = $statusField->getTable();
        $status->database = $statusField->getDatabase();

        if ($this->statuses === null) {
            $this->loadStatuses();
        }

        $this->statuses[] = $status;
    }

    /**
     * Returns the current status
     *
     * @return \MPF\Status
     */
    public function getCurrentStatus()
    {
        $this->loadStatuses();
        return end($this->statuses);
    }

    /**
     * Returns the statuses
     *
     * @return \MPF\Status[]
     */
    public function getStatuses()
    {
        $this->loadStatuses();
        return $this->statuses;
    }

    public function save()
    {
        $dbLayer = \MPF\Db::byName($this->getDatabase());
        $dbLayer->transactionStart();

        try {
            parent::save();
            $this->loadStatuses();

            // if there is no statuses we add the default one
            if (empty($this->statuses)) {
                $this->setStatus($this->getDefaultStatus());
            }

            // if we didnt alter the statuses we dont need to do anything
            if ($this->statusCount != count($this->statuses)) {
                // we save all the new status that are not saved
                foreach ($this->statuses as $status) {
                    // only save the status if its a new one
                    if ($status->isNew()) {
                        $this->getLogger()->info('Adding new status({status}) for {modelClass}({modelId})', array(
                            'category' => Category::FRAMEWORK | Category::DATABASE, 
                            'className' => 'Db/ModelStatus',
                            'status' => $status->getValue(),
                            'modelClass' => get_class($this),
                            'modelId' => $this->getId()
                        ));
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

    final private function loadStatuses()
    {
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
            while ($status = $result->fetch()) {
                $this->statuses[] = $status;
            }

            $this->statusCount = count($this->statuses);
            $result->free();
        }
    }

    /**
     *
     * @return \MPF\Db\Field
     */
    final protected function getStatusField()
    {
        $statusFields = array();
        foreach (self::$phpdoc[$this->className]['properties'] as $name => $property) {
            if (empty($property) || !isSet($property[PhpDoc::PROPERTY_MODEL])) {
                continue;
            }

            if ($property[PhpDoc::PROPERTY_MODEL] != 'MPF\Status') {
                continue;
            }

            if (property_exists($this, $name) && $property['declaringClass'] == $this->className) {
                $statusFields[] = new \MPF\Db\Field(self::$phpdoc[$this->className]['class'], $name, $this->$name, $property);
            }
        }

        if (empty($statusFields) || count($statusFields) > 1) {
            $exception = new Exception\ModelMissingStatuses();

            $this->getLogger()->emergency($exception, array(
                'category' => Category::FRAMEWORK | Category::DATABASE, 
                'className' => 'Db/ModelStatus',
                'exception' => $exception
            ));
            throw $exception;
        }

        return $statusFields[0];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['statuses'] = array();
        foreach ($this->getStatuses() as $status) {
            $array['statuses'][] = $status->toArray();
        }
        return $array;
    }

}
