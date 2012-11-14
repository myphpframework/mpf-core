<?php
namespace MPF\Db\Connection;

class SQLite extends \MPF\Db\Connection
{

    /**
     * The resource or object for the connection
     *
     * @var \SQLite3
     */
    public $resource = null;

    /**
     * Tries to connect to the database server thru the dbLayer
     */
    public function connect()
    {
        if ($this->isInfoValid() && !$this->isConnected())
        {
            $resource = new \SQLite3($this->host.$this->database);
            if ($resource)
            {
                $this->setResource($resource);
                return true;
            }
        }
        elseif ($this->isConnected())
        {
            return true;
        }

        return false;
    }

    public function disconnect()
    {
        if ($this->resource instanceof \SQLite3)
        {
            $this->resource->close();
        }
    }

    /**
     * Determines if we have everything we need
     *
     * @return bool
     */
    public function isInfoValid()
    {
        if ($this->engine != '')
        {
            return ($this->host != '' && $this->database != '');
        }
        return false;
    }

}
