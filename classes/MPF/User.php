<?php

namespace MPF;

use \MPF\Email;
use \MPF\Status;
use \MPF\User\Group;

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
     * @private
     * @type foreign
     * @table user_status
     * @model MPF\Status
     * @relation onetomany
     * @var \MPF\Status
     */
    protected $statuses;

    /**
     *
     * @private
     * @type foreign
     * @table user_group
     * @model MPF\User\Group
     * @relation onttomany
     * @var \MPF\User\Group
     */
    protected $groups = null;
    protected $groupCount = 0;

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
     * Returns the creation date of the user
     *
     * @return MPF\Date
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Returns the last datetime the user logged in
     *
     * @return MPF\Date
     */
    public function getLastLogin() {
        return $this->lastLogin;
    }

    /**
     * Return the email for the user
     *
     * @return MPF\Email
     */
    public function getEmail() {
        return Email::byString($this->email);
    }

    /**
     * Returns the groups the user is part of
     *
     * @return MPF\User\Group[]
     */
    public function getGroups() {
        // if the groups havent loaded we do so now
        if ($this->groups === null) {
            $this->groups = Group::byUser($this);
            $this->groupCount = count($this->groups);
        }

        return $this->groups;
    }

    /**
     * Add the user in the give group
     *
     * @param MPF\User\Group $group
     * @return bool
     */
    public function addGroup(Group $group) {
        // Restrictions to add users to the admin group
        if ($group->getId() == Group::ADMIN_ID) {
            $loggedUser = self::bySession();

            // if we only have the system user in the database we allow the first user to be an admin,
            if (self::getTotalEntries() != 1) {
                // otherwise only an admin can add a user to the admin group
                if (!$loggedUser || !$loggedUser->isInGroup(Group::ADMIN())) {
                    return false;
                }
            }
        }

        // only if its not already in the group
        if (!$this->isInGroup($group)) {
            $this->groups[] = $group;
        }

        return true;
    }

    /**
     * Verifies if the user is part of specific group
     *
     * @param MPF\User\Group $group
     * @return bool
     */
    public function isInGroup(Group $group) {
        foreach ($this->getGroups() as $grp) {
            if ($group->getId() == $grp->getId()) {
                return true;
            }
        }
        return false;
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

    public function save() {
        $dbLayer = \MPF\Db::byName($this->getDatabase());
        $dbLayer->transactionStart();

        try {
            parent::save();

            /// if we have new groups we save them
            $groups = $this->getGroups();
            if ($this->groupCount != count($groups)) {
                // remove all links (group) for user
                $userIdField = $this->getField('id');
                $userIdField->setLinkFieldName('userId');
                $linkTable = new \MPF\Db\ModelLinkTable(array($userIdField), null, self::getDb(get_called_class()), 'user_group_link');
                $linkTable->delete();

                $linkTables = array();
                foreach ($groups as $group) {
                    $linkTables[] = new \MPF\Db\ModelLinkTable(array(
                        $userIdField,
                        $group->getField('id')
                    ), null, self::getDb(get_called_class()), 'user_group_link');
                }
                \MPF\Db\ModelLinkTable::saveAll($linkTables);
            }
        } catch (\Exception $e) {
            $dbLayer->transactionRollback();
            throw $e;
        }

        $dbLayer->transactionCommit();
    }


    /**
     * @return array
     */
    public function toArray() {
        $array = parent::toArray();
        $array['groups'] = array();
        foreach ($this->getGroups() as $group) {
            $array['groups'][] = $group->toArray();
        }
        return $array;
    }
}