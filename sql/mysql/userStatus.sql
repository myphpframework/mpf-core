CREATE TABLE IF NOT EXISTS `userStatus` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `foreignId` mediumint(8) unsigned NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `status` smallint(4) unsigned NOT NULL,
  `byWhoId` mediumint(8) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `foreignId` (`foreignId`),
  FOREIGN KEY (`foreignId`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;