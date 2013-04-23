CREATE TABLE IF NOT EXISTS `user_group_permission` (
  `userGroupId` mediumint(8) unsigned NOT NULL,
  `category` varchar(75) NOT NULL,
  `value` bigint(20) unsigned NOT NULL DEFAULT 0,
  FOREIGN KEY (`userGroupId`) REFERENCES `user_group` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  UNIQUE KEY `userGroupId` (`userGroupId`,`category`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
