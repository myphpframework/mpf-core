<?php

namespace MPF\Db;

use MPF\Logger;

/**
 * Layer of abstraction that every db layer needs to extends
 */
abstract class Layer implements Layer\Intheface {
    protected static $modelCache = array();

    public abstract function transactionStart();
    public abstract function transactionCommit();
    public abstract function transactionRollback();

    /**
     * Fetches a model from the database
     *
     * @param \MPF\Db\Field $field
     * @param \MPF\Db\Field $fields
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\Result
     */
    public abstract function queryModelField(\MPF\Db\Field $field, $fields, \MPF\Db\Page $page=null);

    /**
     * Fetches models from the database via a link table
     *
     * @param \MPF\Db\ModelLinkTable $linkTable
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\ModelResult
     */
    public abstract function queryModelLinkTable(\MPF\Db\ModelLinkTable $linkTable, \MPF\Db\Page $page=null);

    /**
     * Saves a model to the database
     *
     * @throws \MPF\Db\Exception\DuplicateEntry
     * @param \MPF\Db\Model $model
     */
    public abstract function saveModel(\MPF\Db\Model $model);

    /**
     * Deletes a model from the database
     *
     * @param \MPF\Db\Model $model
     */
    public abstract function deleteModel(\MPF\Db\Model $model);

    /**
     * This function does what it can to make the query valid for its engine.
     *
     * @throws Exception\Db\InvalidQuery
     * @param string $query
     * @return string
     */
    protected abstract function sanitizeQuery($query);

    /**
     * Executes the query and modify the object Result in accordingly
     * and returns it.
     *
     * DEV: This function must set the property following properties
     * rowsAffected
     * rowsTotal
     *
     * Of the object Result as needed. It also need to set the Resource
     * for the Result if need be.
     *
     * @throws Exception\Db\InvalidConnectionType
     * @throws Exception\Db\InvalidQuery
     * @param Result $result
     * @return Result
     */
    protected abstract function executeQuery(Result $result);

    /**
     * The total rows the query wielded
     *
     * @return integer
     */
    protected abstract function getRowsTotal(Result $result);

    /**
     * The number of rows that were affected by the query wielded
     *
     * @return integer
     */
    protected abstract function getRowsAffected(Result $result);

    /**
     * Contains the connections for the current db layer
     *
     * @var \MPF\Db\Connection[]
     */
    private $connections = array();

    public function __construct(Connection $connection) {
        $connection->setId(0);
        $this->connections[] = $connection;
    }

    /**
     * Execute a query without fetching the rows if any.
     *
     * @throws Exception\Db\InvalidConnectionType
     * @throws Exception\Db\InvalidQuery
     * @param string $query
     * @return Result
     */
    public function query($query, $isReadOnly=false) {
        $query = $this->sanitizeQuery($query);
        $result = new Result($query, $this->getFirstAvailableConnection($isReadOnly), $this);
        Logger::Log('Db/Layer', $query, Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
        return $this->executeQuery($result);
    }

    /**
     * Caches the dbEntry in memory
     *
     * @param \MPF\Db\Entry $dbEntry
     * @param string $tableName
     */
    public function cacheDbEntry(\MPF\Db\Entry $dbEntry, $tableName) {
        if (!array_key_exists($tableName, self::$modelCache)) {
            self::$modelCache[ $tableName ] = array();
        }

        // if we reached the "limit" of items in the cache we clean the old entries up
        if (count(self::$modelCache[ $tableName ]) > 10000) {
            array_splice(self::$modelCache[ $tableName ], 0, 5000);
        }

        // if we have an oldMd5 we need to remove it from the cache
        if ($dbEntry->oldMd5) {
            unset(self::$modelCache[ $tableName ][ $dbEntry->oldMd5 ]);
        }
        self::$modelCache[ $tableName ][ $dbEntry->getMD5() ] = $dbEntry;
    }

    /**
     * Searches the database entry to match the given database field
     *
     * @param \MPF\Db\Field $field
     * @return \MPF\Db\Entry
     */
    protected function searchCacheByModelField(\MPF\Db\Field $field, \MPF\Db\Page $page=null) {
        if (!array_key_exists($field->getTable(), self::$modelCache)) {
            return array();
        }

        // we dont search if its a foreign field
        // TODO: we could potentially search for foreign fields since its per table cache...
        if ($field->isForeign()) {
            return array();
        }

        $entriesFound = array();
        foreach (self::$modelCache[ $field->getTable() ] as $dbEntry) {
             // TODO: Since introduction of the page system in the ORM there is a problem with the cache where we dont filter de cached entries by it and they are not "ordered by"
             if ($field->matches($dbEntry[ $field->getName() ])) {
                $entriesFound[] = $dbEntry;
            }
        }

        // we only check the count if its NOT a primary key. Or if its a primary key with a special operator (Not an equal)
        if (!$field->isPrimaryKey() || ($field->isPrimaryKey() && $field->hasOperator())) {
            // if we dont have the same count as the database we return nothing
            $count = $this->resultCountByField($field, $page);
            $countCache = count($entriesFound);
            Logger::Log('Db/Layer', 'Searching \MPF\Db\Entry cache, result: cache('.$countCache.')  db('.$count.')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
            if ($count != $countCache) {
                return array();
            }
        }

        return $entriesFound;
    }

    /**
     * Returns the resource of a connection
     *
     * @return resource
     */
    protected function getConnectionResource() {
        foreach ($this->connections as $id => $connection) {
            if (($connection->isInfoValid() && $connection->isConnected())
            || (!$connection->isConnected() && $connection->connect())) {
                return $connection->resource;
            }
        }
        return null;
    }

    /**
     * Finds the first valid unconnected
     *
     * @throws Exception
     * @param boolean $isReadOnly
     * @return Connection
     */
    protected function getFirstAvailableConnection($isReadOnly=false) {
        $potentialClones = array();

        // Find the first connection that is not connected and returns it
        foreach ($this->connections as $id => $connection) { /* @var $connection \MPF\Db\Connection */
            if ($connection->isInfoValid()) {
                // if the connection is being use by a fetch
                if ($connection->isConnected() && !$connection->isInUse()) {
                    Logger::Log('Db/Layer', 'Connection #'.$connection->getId().' is available, using it for next query', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                    return $connection;
                }
                // Only connect if we arent already connected
                elseif (!$connection->isConnected() && $connection->connect()) {
                    Logger::Log('Db/Layer', 'Connection #'.$connection->getId().' just connected and is available, using it for next query', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                    return $connection;
                } elseif ($connection->isConnected()) {
                    $potentialClones[] = $connection;
                }
            }
        }

        // if we havent found an available connection but we have one that is already connected we clone it and make a new Connection.
        if (!empty($potentialClones)) {
            foreach ($potentialClones as $potential) {
                $newConnection = clone $potential;
                if ($newConnection->connect()) {
                    $this->connections[] = $newConnection;
                    $id = end(array_keys($this->connections));
                    $newConnection->setId($id);
                    Logger::Log('Db/Layer', 'Cloning connection #'. $potential->getId() .' to #'. $id .' and using it for next query', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
                    return $this->connections[$id];
                }
            }
        }


        // TODO: Need custom multi-lang exception here
        $exception = new \Exception('No connection available!');
        Logger::Log('Db/Layer', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_DATABASE);
        throw $exception;
    }

}