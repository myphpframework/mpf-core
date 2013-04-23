<?php

namespace MPF;

/**
 * @object MPF\Status
 */
class Status extends \MPF\Db\Model {

    /**
     * @primaryKey
     * @readonly
     * @type integer unsigned
     * @private
     */
    protected $id;

    /**
     * @readonly
     * @type integer unsigned
     * @private
     */
    protected $foreignId;

    /**
     * @readonly
     * @type timestamp
     * @default now
     */
    protected $date;

    /**
     * @readonly
     * @type integer 4
     */
    protected $status;

    /**
     * This field cannot be altered once live
     *
     * @readonly
     * @foreignTable user
     * @type integer unsigned
     * @default 0
     */
    protected $byWhoId;

    /**
     * Foreign model
     *
     * @var \MPF\Db\Model
     */
    protected $foreignModel;

    /**
     *
     * @param $id
     * @return \MPF\User\Status
     */
    public static function queryById($id) {
        $result = self::byField(self::generateField('id', $id));

        if ($result->rowsTotal == 0) {
          $result->free();
          return null;
        }

        $status = $result->fetch();
        $result->free();
        return $status;
    }

    /**
     *
     * @param \MPF\Db\ModelStatus $model
     * @param integer $status
     * @param integer $byWhoId
     * @return \MPF\Status
     */
    public static function create(\MPF\Db\ModelStatus $model, $status, $byWhoId) {
        $newStatus = new Status();
        $newStatus->date = date('Y-m-d H:i:s');
        $newStatus->status = $status;
        $newStatus->foreignModel = $model;

        $newStatus->byWhoId = $byWhoId;
        return $newStatus;
    }

    /**
     * @return MPF\Date
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @return integer
     */
    public function getForeignId() {
        return $this->foreignId;
    }

    /**
     * @return \MPF\User
     */
    public function getByWho() {
        return $this->byWhoId;
    }

    /**
     *
     * @return integer
     */
    public function getValue() {
        return $this->status;
    }

    public function save() {
        if (!$this->isNew()) {
            return;
        }

        // we adjust the foreign id before saving
        $this->foreignId = $this->foreignModel->getId();
        parent::save();
    }

}