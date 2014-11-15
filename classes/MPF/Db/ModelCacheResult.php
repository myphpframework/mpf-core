<?php

namespace MPF\Db;

use \MPF\Logger;

/**
 * Object for all the result of model cache search
 */
class ModelCacheResult extends \MPF\Db\ModelResult
{

    /**
     *
     * @var \MPF\Db\Entry[]
     */
    protected $entries = null;
    protected $cursor = 0;

    /**
     *
     * @param \MPF\Db\Entry[] $dbEntries
     * @param string $className
     */
    public function __construct($dbEntries, $className)
    {
        $this->entries = $dbEntries;
        $this->rowsTotal = count($dbEntries);
        $this->rowsAffected = 0;
        $this->className = $className;
        $this->cursor = 0;
    }

    public function free()
    {
        $this->entries = null;
        $this->cursor = 0;
    }

    /**
     * Retrieves the models
     *
     * @return \MPF\Db\Model
     */
    public function fetch()
    {
        if (isSet($this->entries[$this->cursor])) {
            $className = $this->className;
            $entry = $className::fromDbEntry($this->entries[$this->cursor]);
            $this->cursor++;
            return $entry;
        }

        return null;
    }

}
