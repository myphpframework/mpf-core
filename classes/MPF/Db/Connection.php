<?php

namespace MPF\Db;

abstract class Connection implements Connection\Intheface
{

    public $engine = '';
    public $host = '';
    public $port = 0;
    public $database = '';
    public $login = '';
    private $password = '';
    private $id = null;
    private $inUse = false;
    private $readWrite = 'r';

    public function __construct()
    {
        
    }

    /**
     * Id can only be set one and should be set in the constructor Layer
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * Identify if the connection as executed a query and
     * is pending in retreiving the records.
     *
     * @param bool $inUse
     */
    public function setInUse($inUse)
    {
        $this->inUse = $inUse;
    }

    /**
     * Returns the Id of the Connection which correspond to the key
     * of the array Layer::$connections
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the connection resource
     *
     * @param mixed $resource
     */
    protected function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Sets the public properties
     *
     * @param string $engine
     * @param string $host
     * @param int $port
     * @param string $database
     * @param string $login
     * @param string $password
     * @param string $readWrite
     */
    public function setInfo($engine, $host, $port, $database, $login, $password, $readWrite = "r")
    {
        $this->engine = $engine;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->login = $login;
        $this->password = $password;
        $this->readWrite = strtolower($readWrite);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the Id of the Connection which correspond to the key
     * of the array Layer::$connections
     *
     * @return bool
     */
    public function isInUse()
    {
        return $this->inUse;
    }

    /**
     * Is the connection currently connected
     *
     * @return bool
     */
    public function isConnected()
    {
        return ($this->resource !== null);
    }

    public function canRead()
    {
        return (strpos($this->readWrite, 'r') === false ? false : true);
    }

    public function canWrite()
    {
        return (strpos($this->readWrite, 'w') === false ? false : true);
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function __clone()
    {
        $this->id = 0;
        $this->resource = null;
    }

}
