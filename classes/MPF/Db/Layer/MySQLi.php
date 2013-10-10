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

    /**
     * Gets the total entries for the table
     * @param type $table
     * @return integer
     */
    public function getTotal($table) {
        $mysqli = $this->getFirstAvailableConnection();
        $result = $this->query('SELECT count(*) total FROM `'.$table.'`');
        $count = $result->fetch();
        $result->free();
        return (int)$count['total'];
    }

    public function transactionStart() {
        $mysqli = $this->getFirstAvailableConnection();
        $mysqli->transactions++;

        if ($mysqli->transactions == 1) {
            $result = $this->query('START TRANSACTION;');
            $result->free();
        }
    }

    public function transactionCommit() {
        $mysqli = $this->getFirstAvailableConnection();
        $mysqli->transactions--;

        // only commit if there is no transactions pending
        if ($mysqli->transactions <= 0) {
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
     * @param \MPF\Db\Field $fields
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\ModelResult
     */
    public function queryModelField(\MPF\Db\Field $field, $fields, \MPF\Db\Page $page=null) {
        $entriesFound = $this->searchCacheByModelField($field, $page);
        if (!empty($entriesFound)) {
            return new \MPF\Db\ModelCacheResult($entriesFound, $field->getClass());
        }

        $result = $this->query($this->getSelectByField($field, $fields), true, $page);

        $modelResult = new \MPF\Db\ModelResult($result, $field->getClass());
        $modelResult->on('fetch', array($this, 'cacheDbEntry', $field->getTable()));
        return $modelResult;
    }

    /**
     * Fetches a model from the database
     *
     * @param \MPF\Db\Field $queryField
     * @param \MPF\Db\Field $fields
     * @param bool $count
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\ModelResult
     */
    protected function getSelectByField(\MPF\Db\Field $queryField, $fields, $count=false, \MPF\Db\Page $page=null) {
        $select = "SELECT count(*) count ";
        if (!$count) {
            $select = "SELECT * ";
        }

        $from = ' FROM `' . $queryField->getTable() . '` ';
        $where = 'WHERE `' . $queryField->getTable() . '`.`' . $queryField->getName() . '` ' . $queryField->getOperator() . ' ' . $this->formatQueryValue($queryField);

        // find the primary key
        $primaryKeys = array();
        foreach ($fields as $field) {
            if ($field->isPrimaryKey()) {
                $primaryKeys[] = $field;
            }
        }

        // we inner join only if we have a primary key
        if (count($primaryKeys) == 1) {
            // only fetch foreign keys of they are onetoone relationship
            $innerjoins = array();
            foreach ($fields as $field) {
                if ($field->isForeign() && $field->getRelationship() == 'onetoone') {
                    $innerjoins[ $field->getTable() ] = ' INNER JOIN `'.$field->getTable().'` ON `' . $primaryKeys[0]->getTable() . '`.'.$primaryKeys[0]->getName().'=`' . $field->getTable() . '`.'.$field->getLinkFieldName().' ';
                }
            }
        }

        $limit = '';
        if ($page) {
            $offset = ($page->number == 1 ? 0 : ($page->number-1) * $page->amount);
            $limit = ' LIMIT '.$offset.', '.$page->amount;

            // we must get the total amount of results for the page object
            $result = $this->query('SELECT count(*) count '. $from . substr($where, 0, -3), true);
            $entry = $result->fetch();
            $page->total = $entry['count'];
            $result->free();
        }

        return $select . $from . implode(' ',  $innerjoins) . $where . $limit;
    }

    /**
     * Fetches models from the database via a link table
     *
     * @param \MPF\Db\ModelLinkTable $linkTable
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\ModelResult
     */
    public function queryModelLinkTable(\MPF\Db\ModelLinkTable $linkTable, \MPF\Db\Page $page=null) {
        #$entriesFound = $this->searchCacheByLinkTable($linkTable);
        #if (!empty($entriesFound)) {
        #    return new \MPF\Db\ModelCacheResult($entriesFound, $field->getClass());
        #}

        $result = $this->query($this->getSelectByModelLinkTable($linkTable, false, $page));

        $modelResult = new \MPF\Db\ModelResult($result, $linkTable->targetField->getClass());
        $modelResult->on('fetch', array($this, 'cacheDbEntry', $linkTable->targetField->getTable()));
        return $modelResult;
    }

    protected function getSelectByModelLinkTable(\MPF\Db\ModelLinkTable $linkTable, $count=false, \MPF\Db\Page $page=null) {
        $linkTableName = '`'.$linkTable->table.'`';

        $innerjoin = '';
        $from = " FROM $linkTableName ";
        $select = "SELECT count(*) count ";
        if (!$count) {
            $targetTableName = '`'.$linkTable->targetField->getTable().'`';
            $targetFieldForeignName = '`'.$linkTable->targetField->getLinkFieldName().'`';
            $targetFieldName = '`'.$linkTable->targetField->getName().'`';
            $select = "SELECT * ";
            $innerjoin = "INNER JOIN $targetTableName ON $linkTableName.$targetFieldForeignName=$targetTableName.$targetFieldName ";
        }

        $where = "WHERE ";
        foreach ($linkTable->knownFields as $knownField) {
            $where .= ' '.$linkTableName.'.`'.$knownField->getLinkFieldName().'` = '.$this->formatQueryValue($knownField).' AND';
        }

        $limit = '';
        if ($page) {
            $offset = ($page->number == 1 ? 0 : ($page->number-1) * $page->amount);
            $limit = ' LIMIT '.$offset.', '.$page->amount;

            // we must get the total amount of results for the page object
            $result = $this->query('SELECT count(*) count '. $from . $innerjoin . substr($where, 0, -3), true);
            $entry = $result->fetch();
            $page->total = $entry['count'];
            $result->free();
        }

        return $sql = $select . $from . $innerjoin . substr($where, 0, -3) . $limit;
    }

    /**
     * Returns the amount of rows the query should be giving
     *
     * @param \MPF\Db\Field $field
     * @param \MPF\Db\Page $page
     * @return int
     */
    public function resultCountByField(\MPF\Db\Field $field, \MPF\Db\Page $page=null) {
        $result = $this->query($this->getSelectByField($field, true, $page), true);
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
            $result->free();
        } catch (InvalidQuery $e) {
            if (preg_match('/duplicate/i', $e->result->getError())) {
                $exception = new DuplicateEntry($e->result, $model->getTable());
                Logger::Log('Db/Layer/MySQLi', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                throw $exception;
            }

            throw $e;
        }
    }

    public function deleteLinkTable(\MPF\Db\ModelLinkTable $linktable) {
        $sql = 'DELETE FROM `'.$linktable->table.'` WHERE ';
        foreach ($linktable->knownFields as $field) {
            $sql .= '`'.$field->getLinkFieldName().'` = '.$this->formatQueryValue($field).' AND';
        }
        $sql = substr($sql, 0, -4);
        $result = $this->query($sql);
        $result->free();
    }

    public function saveLinkTable(\MPF\Db\ModelLinkTable $linktable) {
        $sql = 'REPLACE INTO `'.$linktable->table.'` VALUES(';
        foreach ($linktable->knownFields as $field) {
            $sql .= $this->formatQueryValue($field).',';
        }
        $sql = substr($sql, 0, -1).')';
        $result = $this->query($sql);
        $result->free();
    }

    public function saveAllLinkTables($linktables) {
        if (!is_array($linktables) || empty($linktables)) {
            return;
        }

        $sql = 'REPLACE INTO `'.$linktables[0]->table.'` VALUES ';
        $values = '';
        foreach ($linktables as $linktable) {
            if (!($linktable instanceof \MPF\Db\ModelLinkTable)) {
                continue;
            }

            $values .= '(';
            foreach ($linktable->knownFields as $field) {
                $values .= $this->formatQueryValue($field).',';
            }
            $values = substr($values, 0, -1).'),';
        }

        $result = $this->query($sql.substr($values, 0, -1));
        $result->free();
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
            $hasPrimaryKeys = false;
            foreach ($fields as $field) {
                if ($field->isForeign()) {
                    continue;
                }

                if ($field->isPrimaryKey()) {
                    $hasPrimaryKeys = true;
                }

                $fieldValues[$field->getName()] = $field->getValue();
                $queryValues[$field->getName()] = $this->formatQueryValue($field);
            }

            try {
                $operation = 'INSERT';
                // if it has no primary keys (link tables?) we use a REPLACE
                if (!$hasPrimaryKeys) {
                    $operation = 'REPLACE';
                }
                $result = $this->query($operation.' INTO `' . $model->getTable() . '` (`' . implode('`,`', array_keys($queryValues)) . '`) VALUES (' . implode(',', array_values($queryValues)) . ');');
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
            if ($hasPrimaryKeys) {
                $fieldValues['id'] = $result->getConnection()->resource->insert_id;
            }
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

                if ($field->getOnUpdateValue()) {
                    $field->setValue($field->getOnUpdateValue());
                }

                if ($field->isPrimaryKey()) {
                    $where = '`'. $field->getName() .'`='. $this->formatQueryValue($field) .'';
                } else if (!$field->isReadonly()) {
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
