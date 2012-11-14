<?php

namespace MPF\Db;

/**
 * Object for all the result of database queries
 */
class Result {

  /**
   * For some engine we need the resource to be able to fetch data
   *
   * @var resource
   */
  private $resource = null;
  private $errorMessage = null;
  private $connection = null;
  /**
   *
   * @var \MPF\Db\Layer
   */
  private $dbLayer = null;
  public $query = '';
  public $rowsTotal = 0;
  public $rowsAffected = 0;

  /**
   * SQL Query for this result set
   *
   * @param string $sql
   */
  public function __construct($query, Connection $connection, Layer $dbLayer) {
    $this->connection = $connection;
    $this->dbLayer = $dbLayer;
    $this->query = $query;
  }

  /**
   * Returns the connection the Result was generated with
   *
   * @return Connection
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Defines if the query ran successfully or not
   *
   * @return bool
   */
  public function failed($queryFailed=null) {
    if ($this->errorMessage === null) {
      return false;
    }
    return true;
  }

  /**
   * Fetchs the db entries for the db result
   *
   * @return Entry
   */
  public function fetch() {
    if (!($this->dbLayer instanceof Layer)) {
      // TODO: throw%?
    }

    return $this->dbLayer->fetch($this);
  }

  /**
   * Frees the result
   *
   */
  public function free() {
    if ($this->dbLayer instanceof Layer) {
      $this->dbLayer->freeResult($this);
    }

    $this->reset();
  }

  /**
   * Error message that occurred.
   * Can only be set to once.
   *
   * @param string $msg
   */
  public function setError($msg) {
    if ($this->errorMessage === null) {
      $this->errorMessage = $msg;
    }
  }

  /**
   * Sets the resource, this is option. Some engine requires it to fetch data
   *
   * @param mixed $resource
   */
  public function setResource($resource) {
    if ($this->resource === null) {
      $this->resource = $resource;
    }
  }

  /**
   * Returns the error message if there was one
   *
   * @return string
   */
  public function getError() {
    return $this->errorMessage;
  }

  /**
   * Returns the resource that was set for this Result
   *
   * @return resource
   */
  public function getResource() {
    return $this->resource;
  }

  private function reset() {
    $this->resource = null;
    $this->errorMessage = null;
    $this->connection = null;
    $this->dbLayer = null;

    $this->query = '';
    $this->rowsTotal = 0;
    $this->rowsAffected = 0;
  }

}