<?php

namespace MPF\User;

use MPF\User\Group;

/**
 * @object MPF\User\Group
 * @table user_group
 */
class Group extends \MPF\Db\Model
{

    const ADMIN_ID = 1;

    /**
     * @primaryKey
     * @readonly
     * @type integer unsigned
     * @var integer
     */
    protected $id;

    /**
     * Creator of the group
     *
     * @readonly
     * @foreignTable user
     * @type integer unsigned
     * @default 0
     * @var integer
     */
    protected $userId;

    /**
     *
     * @type varchar 75
     * @var string
     */
    protected $name;

    /**
     * Return the admin group
     *
     * @return MPF\User\Group
     */
    public static function ADMIN()
    {
        $result = self::byFields(self::generateField('id', self::ADMIN_ID));

        if ($result->rowsTotal == 0) {
            $result->free();
            return null;
        }

        $userGroup = $result->fetch();
        $result->free();
        return $userGroup;
    }

    /**
     * Returns all the groups the user is part of
     *
     * @param User $user
     * @return MPF\User\Group[]
     */
    public static function byUser(\MPF\User $user)
    {
        $userId = $user->getField('id');
        $userId->setLinkFieldName('userId');
        $knownFields = array($userId);

        $userGroupId = self::generateField('id');
        $userGroupId->setLinkFieldName('userGroupId');

        $linkTable = new \MPF\Db\ModelLinkTable($knownFields, $userGroupId, self::getDb(get_called_class()), 'user_group_link');
        $result = self::byLinkTable($linkTable);

        if ($result->rowsTotal == 0) {
            $result->free();
            return array();
        }

        $groups = array();
        while ($userGroup = $result->fetch()) {
            $groups[] = $userGroup;
        }

        $result->free();
        return $groups;
    }

    /**
     * Returns all the groups created by the user
     *
     * @param User $user
     * @return MPF\User\Group[]
     */
    public static function byOwner(\MPF\User $user)
    {
        //self::byField();
        return array();
    }

}
