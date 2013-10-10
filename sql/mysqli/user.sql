
CREATE TABLE IF NOT EXISTS `user` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `creationDate` datetime NOT NULL,
  `lastAttempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `username` varchar(25) NOT NULL,
  `password` varchar(256) DEFAULT NULL,
  `salt` varchar(18) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `lastAttempt` (`lastAttempt`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

INSERT INTO `user` VALUES(null, NOW(), null, "MPF System", null, "system");
