<?php

namespace MPF\Db\Connection;

class PostgreSQL extends \MPF\Db\Connection
{

    /**
     * The resource or object for the connection
     *
     * @var Resource
     */
    public $resource = null;

    /**
     * Tries to connect to the database server thru the dbLayer
     */
    public function connect()
    {
        if ($this->isInfoValid() && !$this->isConnected()) {
            $connectString = 'host=' . $this->host . ' port=' . $this->port . ' user=' . $this->login . ' password=' . $this->getPassword() . ' dbname=' . $this->database . ' connect_timeout=5';
            $resource = pg_connect($connectString);
            if (is_resource($resource)) {
                $this->setResource($resource);
                return true;
            }
        } elseif ($this->isConnected()) {
            return true;
        }

        return null;
    }

    /**
     * Closes the connection with the database server.
     */
    public function disconnect()
    {
        if ($this->resource) {
            pg_close($this->resource);
        }
    }

    /**
     * Determines if we have everything we need
     *
     * @return bool
     */
    public function isInfoValid()
    {
        if ($this->engine != '') {
            return ($this->host != '' && $this->port != 0 && $this->database != '' && $this->login != '' && $this->getPassword() != '');
        }
        return false;
    }

}
