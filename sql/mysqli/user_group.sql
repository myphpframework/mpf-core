CREATE TABLE IF NOT EXISTS `user_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `userId` mediumint(8) unsigned NOT NULL,
  `name` varchar(75) NOT NULL,
  FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `user_group` (`id`, `userId`, `name`) VALUES (1, 1, 'admin');
