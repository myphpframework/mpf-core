<?php

namespace MPF\Db;

use \MPF\Logger;

/**
 * Object for all the result of database queries
 */
class ModelResult extends \MPF\EventEmitter
{

    public $timestamp = 0;
    public $query = '';
    public $rowsTotal = 0;
    public $rowsAffected = 0;
    protected $className = '';
    protected $result = null;

    public function __construct(Result $result, $className)
    {
        $this->result = $result;
        $this->query = $result->query;
        $this->rowsTotal = $result->rowsTotal;
        $this->rowsAffected = $result->rowsAffected;
        $this->className = $className;
    }

    public function free()
    {
        $this->result->free();
    }

    /**
     * Retrieves the models
     *
     * @return \MPF\Db\Model
     */
    public function fetch()
    {
        $dbEntry = $this->result->fetch();
        if ($dbEntry) {
            $className = $this->className;
            $this->emit('fetch', $dbEntry);
            return $className::fromDbEntry($dbEntry);
        }
        return null;
    }

}
