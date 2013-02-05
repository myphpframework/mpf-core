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
        $mysqli = $this->getFirstAvailableConnection();
        $mysqli->transactions++;

        $result = $this->query('START TRANSACTION;');
        $result->free();
    }

    public function transactionCommit() {
        $mysqli = $this->getFirstAvailableConnection();
        $mysqli->transactions--;

        // only commit if there is no transactions pending
        if ($mysqli->transactions == 0) {
            // if we have but one request to rollback we do so for everything
            if ($mysqli->rollbacks > 0) {
                $result = $this->query('ROLLBACK;');
                $result->free();
            } else {
                $result = $this->query('COMMIT;');
                $result->free();
            }
        }
    }

    public function transactionRollback() {
        $mysqli = $this->getFirstAvailableConnection();
        $mysqli->transactions--;

        if ($mysqli->transactions == 0) {
            $result = $this->query('ROLLBACK;');
            $result->free();
        } else {
            $mysqli->rollbacks++;
        }
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
        #$modelResult->on('fetch', array($this, 'cacheDbEntry', $field->getTable()));
        return $modelResult;
    }

    protected function getSelectByField(\MPF\Db\Field $field, $count=false) {
        $select = "SELECT count(*) count ";
        if (!$count) {
            $select = "SELECT * ";
        }

        $where = 'WHERE `' . $field->getName() . '` ' . $field->getOperator() . ' ' . $this->formatQueryValue($field);
        $sql = $select .' FROM `' . $field->getTable() . '` ' . $where;
        return $sql;
    }

    /**
     * Fetches models from the database via a link table
     *
     * @param \MPF\Db\ModelLinkTable $linkTable
     * @return \MPF\Db\ModelResult
     */
    public function queryModelLinkTable(\MPF\Db\ModelLinkTable $linkTable) {
        #$entriesFound = $this->searchCacheByLinkTable($linkTable);
        #if (!empty($entriesFound)) {
        #    return new \MPF\Db\ModelCacheResult($entriesFound, $field->getClass());
        #}

        $result = $this->query($this->getSelectByModelLinkTable($linkTable));

        $modelResult = new \MPF\Db\ModelResult($result, $linkTable->targetField->getClass());
        $modelResult->on('fetch', array($this, 'cacheDbEntry', $linkTable->targetField->getTable()));
        return $modelResult;
    }

    protected function getSelectByModelLinkTable(\MPF\Db\ModelLinkTable $linkTable, $count=false) {
        $knownFieldName = '`'.$linkTable->knownField->getLinkFieldName().'`';
        $linkTableName = '`'.$linkTable->table.'`';

        $innerjoin = '';
        $from = " FROM $linkTableName ";
        $select = "SELECT count(*) count ";
        if (!$count) {
            $targetTableName = '`'.$linkTable->targetField->getTable().'`';
            $targetFieldForeignName = '`'.$linkTable->targetField->getLinkFieldName().'`';
            $targetFieldName = '`'.$linkTable->targetField->getName().'`';
            $select = "SELECT $targetTableName.* ";
            $innerjoin = "INNER JOIN $targetTableName ON $linkTableName.$targetFieldForeignName=$targetTableName.$targetFieldName ";
        }

        $where = "WHERE $linkTableName.$knownFieldName " . $linkTable->knownField->getOperator() . " " . $this->formatQueryValue($linkTable->knownField);
        return $select . $from . $innerjoin . $where;
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

    /**
     * Deletes a model from the database
     *
     * @param \MPF\Db\Model $model
     */
    public function deleteModel(\MPF\Db\Model $model) {
        try {
            $where = '';
            $fallback_where = array();
            foreach ($model->getFields() as $field) {
                if ($field->isPrimaryKey()) {
                    $where = ' `'. $field->getName() .'`='. $this->formatQueryValue($field) .' ';
                }
                $fallback_where[] = ' `'. $field->getName() .'`='. $this->formatQueryValue($field) .' ';
            }

            if (!$where) {
                $where = implode(' AND ', $fallback_where);
            }

            $result = $this->query('DELETE FROM `'. $model->getTable() .'` WHERE '. $where .';');

        } catch (InvalidQuery $e) {
            if (preg_match('/duplicate/i', $e->result->getError())) {
                $exception = new DuplicateEntry($e->result, $model->getTable());
                Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                throw $exception;
            }

            throw $e;
        }
    }

    /**
     * Saves a model to the database
     *
     * @throws \MPF\Db\Exception\DuplicateEntry
     * @param \MPF\Db\Model $model
     */
    public function saveModel(\MPF\Db\Model $model) {
        $fields = $model->getFields();

        if ($model->isNew()) {
            $fieldValues = array();
            $queryValues = array();
            foreach ($fields as $field) {
                if ($field->isForeign()) {
                    continue;
                }

                $fieldValues[$field->getName()] = $field->getValue();
                $queryValues[$field->getName()] = $this->formatQueryValue($field);
            }

            try {
                $result = $this->query('INSERT INTO `' . $model->getTable() . '` (`' . implode('`,`', array_keys($queryValues)) . '`) VALUES (' . implode(',', array_values($queryValues)) . ');');

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
                    $where = '`'. $field->getName() .'`='. $this->formatQueryValue($field) .'';
                } else {
                    $sql .= '`' . $field->getName() . '`=' . $this->formatQueryValue($field) . ',';
                }
            }

            $sql = substr($sql, 0, -1);
            $sql .= ' WHERE '. $where.' ';

            $result = $this->query($sql);
            $result->free();

            $this->cacheDbEntry($model->getDbEntry(), $model->getTable());
        }
    }

    private function formatQueryValue(\MPF\Db\Field $field) {
        $mysqli = $this->getConnectionResource();
        if ($field->getValue() === null) {
            $value = 'NULL';
        } else {
            switch (strtolower($field->getType())) {
                default:
                case 'timestamp':
                case 'datetime':
                case 'date':
                case 'varchar':
                    $value = '"'.$mysqli->real_escape_string($field->getValue()).'"';
                    break;
                case 'float':
                case 'int':
                case 'integer':
                    $value = $mysqli->real_escape_string($field->getValue());
                    break;
            }
        }
        return $value;
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
