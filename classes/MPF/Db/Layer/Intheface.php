<?php

namespace MPF\Db\Layer;

use \MPF\Db\Model;
use \MPF\Db\Result;

interface Intheface {

  /**
   * Executes the fetch for the given result.
   *
   * DEV: Needs to put the connection in use if we start fetching records.
   *
   * @throws Exception_Db_InvalidConnectionType
   * @throws Exception_Db_InvalidResultResourceType
   * @param \MPF\Db\Result $result
   * @return \MPF\Db\Result
   */
  public function fetch(Result $result);

  /**
   * Fetchs models by a partial model
   *
   * @param \MyPhpFrameowkr\Db\Model $model
   * @param string $condition
   * @return \MPF\Db\Model
   */
  //public function fetchModels(Model $model, $condition='AND');

  /**
   * Frees the result,
   *
   * Needs to free the connection if we are done fetching records.
   *
   * @param \MPF\Db\Result $result
   */
  public function freeResult(Result $result);

  /**
   * Save the model in the database and returns the model with the primary key set
   * if it was an insert
   *
   * @param \MPF\Db\Model $model
   * @return \MPF\Db\Model
   */
  public function saveModel(Model $model);

  /**
   * Returns the total entries for the table
   */
  public function getTotal($table);
}