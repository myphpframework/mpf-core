<?php

namespace MPF\Db;

use \MPF\Logger;

/**
 * Object for all the result of model cache search
 */
class ModelCacheResult extends \MPF\Db\ModelResult {

    /**
     *
     * @var \MPF\Db\Entry[]
     */
    protected $entries = null;

    /**
     *
     * @param \MPF\Db\Entry[] $dbEntries
     * @param string $className
     */
    public function __construct($dbEntries, $className) {
        $this->entries = $dbEntries;
        $this->rowsTotal = count($dbEntries);
        $this->rowsAffected = 0;
        $this->className = $className;
    }

    public function free() {
        $this->entries = null;
    }

    /**
     * Retrieves the models
     *
     * @return \MPF\Db\Model
     */
    public function fetch() {
        static $i = 0;

        if ($i >= count($this->entries)) {
            $i = 0;
        }

        if (isSet($this->entries[$i])) {
            $className = $this->className;
            $entry = $className::fromDbEntry($this->entries[$i]);
            $i++;
            return $entry;
        }

        return null;
    }

}