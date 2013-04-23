CREATE TABLE IF NOT EXISTS `user_group_link` (
  `userId` mediumint(8) unsigned NOT NULL,
  `userGroupId` mediumint(8) unsigned NOT NULL,
  FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  FOREIGN KEY (`userGroupId`) REFERENCES `user_group` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  UNIQUE KEY `userGroups` (`userId`,`userGroupId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
