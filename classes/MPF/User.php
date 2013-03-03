<?php

namespace MPF;

use \MPF\Email;
use \MPF\Status;

// TODO: add an IP field, helps to see if its a known user that has been blacklisted in the root .htaccess. Keep a history of IPs? foreign field?

/**
 * Represent a table structure in the database
 *
 * @object \MPF\User
 * @table user
 */
class User extends \MPF\Db\ModelStatus {

    /**
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
     * @readonly
     * @type timestamp
     * @default now
     */
    protected $lastLogin;

    /**
     * @type varchar 150
     */
    protected $email;

    /**
     * This field cannot be altered once live
     *
     * @private
     * @password sha512
     * @type varchar 256
     * @default
     */
    protected $password;

    /**
     * This field cannot be altered once live
     *
     * @private
     * @readonly
     * @salt sha512
     * @type varchar 18
     */
    protected $salt;

    /**
     * Statuses for the user
     *
     * @type foreign
     * @table user_status
     * @model MPF\Status
     * @relation onetomany
     */
    protected $statuses;

    const USERID_SYSTEM      =    1;

    const STATUS_NOTAPPROVED =   50;
    const STATUS_ACTIVE      =  100;
    const STATUS_SUSPENDED   = 1000;
    const STATUS_DELETED     = 2000;

    /**
     *
     * @return \MPF\User
     */
    public static function SYSTEM() {
        return self::byId(self::USERID_SYSTEM);
    }

    /**
     * Creates a new user
     *
     * @param Email $email
     * @return \MPF\User
     */
    public static function create(Email $email) {
        $class = get_called_class();
        $newUser = new $class();
        $newUser->setEmail($email);
        return $newUser;
    }

    /**
     * Returns the user for the session if any
     *
     * @return \MPF\User
     */
    public static function bySession() {
        \MPF\ENV::bootstrap(\MPF\ENV::SESSION);

        if (!\MPF\Session::get('userId')) {
            return null;
        }

        return self::byId((int)\MPF\Session::get('userId'));
    }

    /**
     *
     * @param $id
     * @return \MPF\User
     */
    public static function byId($id) {
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
     *
     * @param \MPF\Email $email
     * @return \MPF\User
     */
    public static function byEmail(Email $email) {
        $result = self::byField(self::generateField('email', $email->__toString()));
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
    protected function getDefaultStatus() {
        return Status::create($this, self::STATUS_NOTAPPROVED, User::USERID_SYSTEM);
    }

    /**
     * @return MPF\Date
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * @return MPF\Date
     */
    public function getLastLogin() {
        return $this->lastLogin;
    }

    /**
     * @return MPF\Email
     */
    public function getEmail() {
        return Email::byString($this->email);
    }

    /**
     * @param \MPF\Email $email
     */
    public function setEmail(Email $email) {
        $this->setField('email', $email->__toString());
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->setField('password', $password);
    }

    /**
     * Verifies if the password match the current one
     *
     * @param string $password
     * @return boolean
     */
    public function verifyPassword($password) {
        if ($this->password == $this->verifyField('password', $password)) {
            return true;
        }
        return false;
    }
}