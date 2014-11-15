<?php

namespace MPF;

use \MPF\Status;

/**
 * Represent a table structure in the database
 *
 * @object \MPF\User
 * @table user
 */
class Bucket extends \MPF\Db\ModelStatus
{

    /**
     * Unique ID of the bucket
     *
     * @primaryKey
     * @readonly
     * @type integer unsigned
     */
    protected $id;

    /**
     * @readonly
     * @type datetime
     * @default now
     */
    protected $creationDate;

    /**
     * Author of the bucket
     *
     * @private
     * @type foreign
     * @table user
     * @model MPF\User
     * @relation onetomany
     * @var \MPF\User
     */
    protected $author;

    /**
     * Name of the bucket
     *
     * @type varchar 125
     */
    protected $name;

    /**
     * Description of what is the bucket for
     *
     * @type text
     */
    protected $description;

    /**
     * Official site if any
     *
     * @var varchar 255
     */
    protected $url;

    /**
     * Where the bucket is downloaded
     *
     * @var varchar 255
     */
    protected $source;

    /**
     *
     * @var integer unsigned
     */
    protected $categories = 0;

    /**
     * Statuses for the bucket
     *
     * @private
     * @type foreign
     * @table bucket_status
     * @model MPF\Status
     * @relation onetomany
     * @var \MPF\Status
     */
    protected $statuses;
    protected $categories = 0;

    const STATUS_NOTAPPROVED = 50;
    const STATUS_ACTIVE = 100;
    const STATUS_SUSPENDED = 1000;
    const STATUS_DELETED = 2000;

    /**
     * Creates a new user
     *
     * @param string $username
     * @return \MPF\User
     */
    public static function create($username)
    {
        $class = get_called_class();
        $newUser = new $class();
        $newUser->setUsername($username);
        return $newUser;
    }

    /**
     *
     * @param $id
     * @return \MPF\User
     */
    public static function byId($id)
    {
        $result = self::byField(self::generateField('id', $id));

        if ($result->rowsTotal == 0) {
            $result->free();
            return null;
        }

        $user = $result->fetch();
        $result->free();
        return $user;
    }

    /**
     * Returns the default status
     *
     * @return \MPF\Status
     */
    protected function getDefaultStatus()
    {
        return Status::create($this, self::STATUS_NOTAPPROVED, User::USERID_SYSTEM);
    }

    /**
     * Returns the creation date of the user
     *
     * @return MPF\Date
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function save()
    {
        $dbLayer = \MPF\Db::byName($this->getDatabase());
        $dbLayer->transactionStart();

        try {
            parent::save();
        } catch (\Exception $e) {
            $dbLayer->transactionRollback();
            throw $e;
        }

        $dbLayer->transactionCommit();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return parent::toArray();
    }

}
