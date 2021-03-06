<?php

namespace MPF\Db;

use MPF\Log\Category;

\MPF\ENV::bootstrap(\MPF\ENV::DATABASE);

/**
 * Layer of abstraction that every db layer needs to extends
 */
abstract class Layer extends \MPF\Base implements Layer\Intheface
{

    protected static $modelCache = array();

    public abstract function transactionStart();

    public abstract function transactionCommit();

    public abstract function transactionRollback();

    /**
     * Fetches a model from the database
     *
     * @param \MPF\Db\Field[] $fields
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\Result
     */
    public abstract function queryModelFields($fields, \MPF\Db\Page $page = null);

    /**
     * Fetches models from the database via a link table
     *
     * @param \MPF\Db\ModelLinkTable $linkTable
     * @param \MPF\Db\Page $page
     * @return \MPF\Db\ModelResult
     */
    public abstract function queryModelLinkTable(\MPF\Db\ModelLinkTable $linkTable, \MPF\Db\Page $page = null);

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

    public function __construct($connections)
    {
        $this->connections = $connections;
    }

    /**
     * Execute a query without fetching the rows if any.
     *
     * @throws Exception\Db\InvalidConnectionType
     * @throws Exception\Db\InvalidQuery
     * @param string $query
     * @return Result
     */
    public function query($query, $isReadOnly = false)
    {
        $query = $this->sanitizeQuery($query);
        $this->getLogger()->info($query, array(
            'category' => Category::FRAMEWORK | Category::DATABASE,
            'className' => 'Db/Layer',
        ));
        $result = new Result($query, $this->getFirstAvailableConnection($isReadOnly), $this);
        return $this->executeQuery($result);
    }

    /**
     * Caches the dbEntry in memory
     *
     * @param \MPF\Db\Entry $dbEntry
     * @param string $tableName
     */
    public function cacheDbEntry(\MPF\Db\Entry $dbEntry, $tableName)
    {
        if (!array_key_exists($tableName, self::$modelCache)) {
            self::$modelCache[$tableName] = array();
        }

        // if we reached the "limit" of items in the cache we clean the old entries up
        if (count(self::$modelCache[$tableName]) > 10000) {
            array_splice(self::$modelCache[$tableName], 0, 5000);
        }

        // if we have an oldMd5 we need to remove it from the cache
        if ($dbEntry->oldMd5) {
            unset(self::$modelCache[$tableName][$dbEntry->oldMd5]);
        }
        self::$modelCache[$tableName][$dbEntry->getMD5()] = $dbEntry;
    }

    /**
     * Searches the database entry to match the given database field
     *
     * @param \MPF\Db\Field[] $fields
     * @return \MPF\Db\Entry
     */
    protected function searchCacheByModelFields($fields, \MPF\Db\Page $page = null)
    {
        $table = $fields[0]->getTable();
        if (!array_key_exists($table, self::$modelCache)) {
            return array();
        }

        // for now cache search is still only if there is only 1 field
        if (count($fields) > 1 || count($fields) == 0) {
            return array();
        }
        
        $field = $fields[0];
        
        // we dont search if its a foreign field
        // TODO: we could potentially search for foreign fields since its per table cache...
        if ($field->isForeign()) {
            return array();
        }

        $entriesFound = array();
        foreach (self::$modelCache[$table] as $dbEntry) {
            // TODO: Since introduction of the page system in the ORM there is a problem with the cache where we dont filter de cached entries by it and they are not "ordered by"
            if ($field->matches($dbEntry[$field->getName()])) {
                $entriesFound[] = $dbEntry;
            }
        }

        // we only check the count if its NOT a primary key. Or if its a primary key with a special operator (Not an equal)
        if (!$field->isPrimaryKey() || ($field->isPrimaryKey() && $field->hasOperator())) {
            // if we dont have the same count as the database we return nothing
            $count = $this->resultCountByFields($fields, $page);
            $countCache = count($entriesFound);
            
            $this->getLogger()->info('Searching \MPF\Db\Entry cache, result: cache({countCache})  db({count})', array(
                'category' => Category::FRAMEWORK | Category::DATABASE,
                'className' => 'Db/Layer',
                'countCache' => $countCache,
                'count' => $count
                
            ));
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
    protected function getConnectionResource()
    {
        foreach ($this->connections as $id => $connection) {
            if (($connection->isInfoValid() && $connection->isConnected()) || (!$connection->isConnected() && $connection->connect())) {
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
    protected function getFirstAvailableConnection($isReadOnly = false)
    {
        $potentialClones = array();

        // Find the first connection that is not connected and returns it
        foreach ($this->connections as $id => $connection) { /* @var $connection \MPF\Db\Connection */
            if ($connection->isInfoValid()) {
                // if the connection is being use by a fetch
                if ($connection->isConnected() && !$connection->isInUse()) {
                    $this->getLogger()->info('Connection #{connectionId} is available, using it for next query', array(
                        'category' => Category::FRAMEWORK | Category::DATABASE,
                        'className' => 'Db/Layer',
                        'connectionId' => $connection->getId()
                    ));
                    return $connection;
                }
                // Only connect if we arent already connected
                elseif (!$connection->isConnected() && $connection->connect()) {
                    $this->getLogger()->info('Connection #{connectionId} just connected and is available, using it for next query', array(
                        'category' => Category::FRAMEWORK | Category::DATABASE,
                        'className' => 'Db/Layer',
                        'connectionId' => $connection->getId()
                    ));
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
                    $this->getLogger()->info('Cloning connection #{connectionId}  to #{id} and using it for next query', array(
                        'category' => Category::FRAMEWORK | Category::DATABASE,
                        'className' => 'Db/Layer',
                        'connectionId' => $connection->getId(),
                        'id' => $id
                    ));
                    return $this->connections[$id];
                }
            }
        }


        // TODO: Need custom multi-lang exception here
        $exception = new \Exception('No connection available!');
        $this->getLogger()->warning($exception->getMessage(), array(
            'category' => Category::FRAMEWORK | Category::DATABASE,
            'className' => 'Db/Layer',
            'exception' => $exception
        ));
        throw $exception;
    }

}
