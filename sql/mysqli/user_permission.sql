CREATE TABLE IF NOT EXISTS `user_permission` (
  `userId` mediumint(8) unsigned NOT NULL,
  `category` varchar(75) NOT NULL,
  `value` bigint(20) unsigned NOT NULL DEFAULT 0,
  FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  UNIQUE KEY `userId` (`userId`,`category`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
