<?php

/**
 * Represent a table structure in the database
 *
 * @object \MPF\User
 * @table user
 * @jpush insert,update,delete
 */
class ModelExample extends \MPF\Db\Model
{
    /**
     * @primaryKey
     * @readonly
     * @unsigned
     * @type integer unsigned
     */
    protected $id;

    /**
     * @readonly
     * @type datetime
     */
    protected $creationDate;

    /**
     * @readonly
     * @type timestamp
     */
    protected $lastLogin;

    /**
     * @type varchar 150
     */
    protected $email;

    /**
     * @type varchar 5
     */
    protected $testLength;

    /**
     * @type enum black,white,blue,green
     */
    protected $color;
}