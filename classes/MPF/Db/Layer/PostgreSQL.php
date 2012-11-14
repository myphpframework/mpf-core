<?php

namespace MPF\Db\Layer;

use MPF\Db\Exception\InvalidConnectionType;
use MPF\Db\Exception\InvalidResultResourceType;
use MPF\Db\Exception\InvalidQuery;
use MPF\Db\Result;
use MPF\Db\Entry;

class PostgreSQL extends \MPF\Db\Layer {

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
        if (!($connection instanceof \MPF\Db\Connection\PostgreSQL)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\PostgreSQL');
            Logger::Log('Db/Layer/PostgreSQL', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $resource = $result->getResource();
        if (!is_resource($resource)) {
            $exception = new InvalidResultResourceType($resource, 'resource');
            Logger::Log('Db/Layer/PostgreSQL', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        $entry = pg_fetch_array($resource, NULL, PGSQL_ASSOC);
        if (!$entry) {
            return false;
        }

        return new Entry($entry);
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
        if (!($connection instanceof \MPF\Db\Connection\PostgreSQL)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\MySQLi');
            Logger::Log('Db/Layer/PostgreSQL', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exeption;
        }

        $resource = $result->getResource();
        if (is_resource($resource)) {
            @pg_free_result($resource);
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
        // String assignation are in single quotes in PostgreSQL
        $assignationRegexp = '/=[\s]{0,}"(.*?)"/is';
        if (preg_match_all($assignationRegexp, $query, $matchs)) {
            $query = preg_replace($assignationRegexp, "='$1'", $query);
        }

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
        if (!($connection instanceof \MPF\Db\Connection\PostgreSQL)) {
            $exception = new InvalidConnectionType($connection, 'MPF\Db\Connection\PostgreSQL');
            Logger::Log('Db/Layer/PostgreSQL', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        try {
            $postgreResult = pg_query($connection->resource, $result->query);
        } catch (\Exception $e) {
            $result->setError($e->getMessage());
            $exception = new InvalidQuery($result);
            Logger::Log('Db/Layer/PostgreSQL', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            throw $exception;
        }

        if (is_bool($postgreResult)) {
            if ($postgreResult) {
                // This is NOT a SELECT, SHOW, DESCRIBE or EXPLAIN ???????????
            }
            // Other kind of error???
            else {

            }
        } elseif (is_resource($postgreResult)) {
            $result->setResource($postgreResult);
            $result->rowsTotal = $this->getRowsTotal($result);
            $result->rowsAffected = $this->getRowsAffected($result);
        }

        return $result;
    }

    /**
     * Fetchs the total entry count the query wielded
     *
     * @return integer
     */
    protected function getRowsTotal(Result $result) {
        $count = pg_num_rows($result->getResource());
        if ($count == -1) {
            $count = 0;
        }
        return (int) $count;
    }

    /**
     * Fetchs the total entry count the query wielded
     *
     * @return integer
     */
    protected function getRowsAffected(Result $result) {
        return (int) pg_affected_rows($result->getResource());
    }

}
