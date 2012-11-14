<?php

namespace MPF\Db\Layer;

use MPF\Db\Exception\InvalidConnectionType;
use MPF\Db\Exception\InvalidResultResourceType;
use MPF\Db\Exception\InvalidQuery;
use MPF\Db\Exception\DuplicateEntry;
use MPF\Db\Result;
use MPF\Db\Entry;
use MPF\Logger;

class MySQLi extends \MPF\Db\Layer {

    /**
     * Executes the fetch for the given result
     *
     * @throws InvalidConnectionType
     * @throws InvalidResultResourceType
     * @param Result $result
     * @return Result
     */
    public function fetch(Result $result) {
        $connection = $result->getConnection();
        $connection->setInUse(true);
        if (!($connection instanceof \MPF\Db\Connection\MySQLi)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\MySQLi');
            Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $resource = $result->getResource();
        if (!($resource instanceof \mysqli_result)) {
            $exception = new InvalidResultResourceType($resource, 'mysqli_result');
            Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $entry = $resource->fetch_assoc();
        if (!$entry) {
            return null;
        }

        return new Entry($entry);
    }

    public function transactionStart() {
        $result = $this->query('START TRANSACTION;');
        $result->free();
    }

    public function transactionCommit() {
        $result = $this->query('COMMIT;');
        $result->free();
    }

    public function transactionRollback() {
        $result = $this->query('ROLLBACK;');
        $result->free();
    }

    /**
     * Fetches a model from the database
     *
     * @param \MPF\Db\Field $field
     * @return \MPF\Db\ModelResult
     */
    public function queryModelField(\MPF\Db\Field $field) {
        $entriesFound = $this->searchCacheByModelField($field);
        if (!empty($entriesFound)) {
            return new \MPF\Db\ModelCacheResult($entriesFound, $field->getClass());
        }

        $result = $this->query($this->getSelectByField($field), true);

        $modelResult = new \MPF\Db\ModelResult($result, $field->getClass());
        $modelResult->on('fetch', array($this, 'cacheDbEntry', $field->getTable()));
        return $modelResult;
    }

    /**
     * Returns the amount of rows the query should be giving
     *
     * @param \MPF\Db\Field $field
     * @return int
     */
    public function resultCountByField(\MPF\Db\Field $field) {
        $result = $this->query($this->getSelectByField($field, true), true);
        $dbEntry = $result->fetch();
        $result->free();
        return (int)$dbEntry['count'];
    }

    protected function getSelectByField(\MPF\Db\Field $field, $count=false) {
        $mysqli = $this->getConnectionResource();

        if ($count) {
            $select = "SELECT count(*) count ";
        } else {
            $select = "SELECT * ";
        }

        $where = 'WHERE `' . $field->getName() . '` ' . $field->getOperator() . ' "' . $mysqli->real_escape_string($field->getValue()) . '"';
        $sql = $select .' FROM `' . $field->getTable() . '` ' . $where;
        return $sql;
    }

    /**
     * Saves a model to the database
     *
     * @param \MPF\Db\Model $model
     */
    public function saveModel(\MPF\Db\Model $model) {
        $mysqli = $this->getConnectionResource();
        $fields = $model->getFields();

        if ($model->isNew()) {
            $fieldValues = array();
            foreach ($fields as $field) {
                if ($field->isForeign()) {
                    continue;
                }

                $value = $field->getValue();
                // if we have no value but a default value, we use it
                if (null === $value && $field->getDefaultValue()) {
                    $value = $field->getDefaultValue();
                }
                $fieldValues[$field->getName()] = $mysqli->real_escape_string($value);
            }

            try {
                $result = $this->query('INSERT INTO `' . $model->getTable() . '` (`' . implode('`,`', array_keys($fieldValues)) . '`) VALUES ("' . implode('","', array_values($fieldValues)) . '");');
            } catch (InvalidQuery $e) {
                if (preg_match('/duplicate/i', $e->result->getError())) {
                    $exception = new DuplicateEntry($e->result, $model->getTable());
                    Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                    throw $exception;
                }

                throw $e;
            }

            // TODO: How to set primary key(s?) properly, knowing that the primary key needs to be an autoincrement in order for all this to work... model phpdoc?
            // TODO: what if the primary field name is not id?
            $fieldValues['id'] = $result->getConnection()->resource->insert_id;
            $dbEntry = new Entry($fieldValues);
            $result->free();

            $this->cacheDbEntry($dbEntry, $model->getTable());
            $model->updatefromDbEntry($dbEntry);
        } else {
            $sql = 'UPDATE `' . $model->getTable() . '` SET ';
            $where = '';
            foreach ($fields as $field) {
                if ($field->isForeign()) {
                    continue;
                }

                if ($field->isPrimaryKey()) {
                    $where = '`'. $field->getName() .'`="'. $field->getValue() .'"';
                } else {
                    $sql .= '`' . $field->getName() . '`="' . $mysqli->real_escape_string($field->getValue()) . '",';
                }
            }

            $sql = substr($sql, 0, -1);
            $sql .= ' WHERE '. $where.' ';

            $result = $this->query($sql);
            $result->free();

            $this->cacheDbEntry($model->getDbEntry(), $model->getTable());
        }
    }

    /**
     * Frees the result
     *
     * @param Result $result
     */
    public function freeResult(Result $result) {
        $connection = $result->getConnection();
        if (!($connection instanceof \MPF\Db\Connection\MySQLi)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\MySQLi');
            Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $resource = $result->getResource();
        if (($resource instanceof \mysqli_result)) {
            $resource->free();
        }

        $connection->setInUse(false);
        Logger::Log('Db/Layer', 'Connection #'.$result->getConnection()->getId().' has been freed', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
    }

    /**
     * This function does what it can to make the query valid for its engine.
     *
     * @throws Exception\Db\InvalidQuery
     * @param string $query
     * @return string
     */
    protected function sanitizeQuery($query) {
        return $query;
    }

    /**
     * Executes the query and modify the object Result in concequences
     * and returns it
     *
     * @throws Exception\Db\InvalidConnectionType
     * @throws Exception\Db\InvalidQuery
     * @throws Exception\Db\DuplicateEntry
     * @param Result $result
     * @return Result
     */
    protected function executeQuery(Result $result) {
        $connection = $result->getConnection();
        if (!($connection instanceof \MPF\Db\Connection\MySQLi)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\MySQLi');
            Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $mysqliResult = $connection->resource->query($result->query);
        if (is_bool($mysqliResult)) {
            if (!$mysqliResult) {
                $result->setError($connection->resource->error);
                $exception = new InvalidQuery($result);
                Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                throw $exception;
            } else { // This is NOT a SELECT, SHOW, DESCRIBE or EXPLAIN
                $result->rowsAffected = $this->getRowsAffected($result);
            }
        } elseif ($mysqliResult instanceof \mysqli_result) {
            $result->setResource($mysqliResult);
            $result->rowsTotal = $this->getRowsTotal($result);
        }

        return $result;
    }

    /**
     * Fetchs the total entry count the query wielded
     *
     * @return integer
     */
    protected function getRowsTotal(Result $result) {
        return (int) $result->getResource()->num_rows;
    }

    /**
     * Fetchs the total entry count the query wielded
     *
     * @return integer
     */
    protected function getRowsAffected(Result $result) {
        return (int) $result->getConnection()->resource->affected_rows;
    }

}
