<?php

namespace MPF\Db\Layer;

use MPF\Db\Exception\InvalidConnectionType;
use MPF\Db\Exception\InvalidResultResourceType;
use MPF\Db\Exception\InvalidQuery;
use MPF\Db\Result;
use MPF\Db\Entry;

class SQLite extends \MPF\Db\Layer {

    /**
     * Executes the fetch for the given result
     *
     * @throws InvalidConnectionType
     * @throws InvalidResultResourceType
     * @return Result
     */
    public function fetch(Result $result) {
        $connection = $result->getConnection();
        $connection->setInUse(true);
        if (!($connection instanceof \MPF\Db\Connection\SQLite)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\SQLite');
            Logger::Log('Db/Layer/SQLite', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $resource = $result->getResource();
        if (!($resource instanceof \SQLite3Result)) {
            $exception = new InvalidResultResourceType($resource, 'SQLite3Result');
            Logger::Log('Db/Layer/SQLite', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $entry = $resource->fetchArray(SQLITE3_ASSOC);
        if (!$entry) {
            return false;
        }

        return new Entry($entry);
    }

    public function getTotal($table) {
    }

    /**
     * Fetches a model from the database
     *
     * @param \MPF\Db\Field $field
     * @return \MPF\Db\Result
     */
    public function queryModelField(\MPF\Db\Field $field) {
    }

    public function fetchModels(\MPF\Db\Model $model, $condition='AND') {

    }

    public function saveModel(\MPF\Db\Model $model) {
    }

    /**
     * Frees the result
     *
     * @param Result $result
     */
    public function freeResult(Result $result) {
        $connection = $result->getConnection();
        if (!($connection instanceof \MPF\Db\Connection\SQLite)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\SQLite');
            Logger::Log('Db/Layer/SQLite', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $resource = $result->getResource();
        if (($resource instanceof \SQLite3Result)) {
            $resource->finalize();
        }

        $connection->setInUse(false);
    }

    /**
     * This function does what it can to make the query valid for its engine.
     *
     * @throws InvalidQuery
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
     * @throws InvalidConnectionType
     * @throws InvalidQuery
     * @param Result $result
     * @return Result
     */
    protected function executeQuery(Result $result) {
        $connection = $result->getConnection();
        if (!($connection instanceof \MPF\Db\Connection\SQLite)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\SQLite');
            Logger::Log('Db/Layer/SQLite', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        try {
            if (preg_match('/DELETE|INSERT|UPDATE/i', $result->query)) {
                $sqlite3Result = $connection->resource->exec($result->query);
            } else {
                $sqlite3Result = $connection->resource->query($result->query);
            }
        } catch (\Exception $e) {
            $result->setError($e->getMessage());
            $exception = new InvalidQuery($result);
            Logger::Log('Db/Layer/SQLite', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        if (is_bool($sqlite3Result) && $sqlite3Result) {
            // This is NOT a SELECT, SHOW, DESCRIBE or EXPLAIN
            $result->rowsAffected = $this->getRowsAffected($result);
        } elseif ($sqlite3Result instanceof \SQLite3Result) {
            $result->setResource($sqlite3Result);
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
        return (int) $result->getResource()->numColumns();
    }

    /**
     * Fetchs the total entry count the query wielded
     *
     * @return integer
     */
    protected function getRowsAffected(Result $result) {
        $connection = $result->getConnection();
        return (int) $connection->resource->changes();
    }

}
