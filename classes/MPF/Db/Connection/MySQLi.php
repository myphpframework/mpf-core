<?php

namespace MPF\Db\Connection;

class MySQLi extends \MPF\Db\Connection {

    /**
     * The resource or object for the connection
     *
     * @var mysqli
     */
    public $resource = null;
    public $transactions = 0;

    /**
     * Tries to connect to the database server thru the dbLayer
     */
    public function connect() {
        if ($this->isInfoValid() && !$this->isConnected()) {
            $resource = @new \mysqli($this->host, $this->login, $this->getPassword(), $this->database, $this->port);
            if (mysqli_connect_errno() === 0) {
                $this->setResource($resource);
                return true;
            }
        }
        elseif ($this->isConnected()) {
            return true;
        }

        return null;
    }

    /**
     * Closes the connection with the database server.
     *
     */
    public function disconnect() {
        if ($this->resource instanceof \mysqli) {
            $this->resource->close();
        }
    }

    /**
     * Determines if we have everything we need
     *
     * @return bool
     */
    public function isInfoValid() {
        if ($this->engine != '') {
            return ($this->host != ''
                    && $this->port != 0
                    && $this->database != ''
                    && $this->login != ''
                    && $this->getPassword() != '');
        }
        return false;
    }

}
