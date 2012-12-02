<?php

/**
 * Represent a table structure in the database
 *
 * @table test
 * @database test
 */
class ModelExample extends \MPF\Db\ModelStatus
{
    /**
     * @primaryKey
     * @readonly
     * @unsigned
     * @type integer unsigned
     */
    protected $id;

    /**
     * @type integer unsigned
     */
    protected $integerTest;

    /**
     * @readonly
     * @type datetime
     */
    protected $creationDate;

    /**
     * @type date
     * @default 2000-01-01
     */
    protected $mydate;

    /**
     * @type datetime
     * @default 2000-01-01
     */
    protected $mydatetime;

    /**
     * @type datetime
     */
    protected $lastLogin;

    /**
     * @type varchar 150
     * @default false
     */
    protected $email;

    /**
     * @type varchar 5
     * @default
     */
    protected $testLength;

    /**
     * @type enum black,white,blue,green
     * @default test
     */
    protected $color;

    /**
     * @private
     * @password sha512
     * @type varchar 256
     * @default
     */
    protected $password;

    /**
     * @private
     * @readonly
     * @salt sha512
     * @type varchar 18
     */
    protected $salt;

    /**
     * Statuses for the user
     *
     * @database test
     * @table testStatus
     * @type foreign
     * @model MPF\Status
     * @relation onetomany
     */
    protected $statuses;

    const STATUS_NOTAPPROVED =   50;
    const STATUS_ACTIVE      =  100;
    const STATUS_SUSPENDED   = 1000;
    const STATUS_DELETED     = 2000;

    /**
     * Returns the default status
     *
     * @return \MPF\Status
     */
    protected function getDefaultStatus() {
        return Status::create($this, self::STATUS_NOTAPPROVED, User::USERID_SYSTEM);
    }

}