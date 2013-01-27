CREATE TABLE IF NOT EXISTS `user_status` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `foreignId` mediumint(8) unsigned NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `status` smallint(4) unsigned NOT NULL,
  `byWhoId` mediumint(8) unsigned default NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`foreignId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  FOREIGN KEY (`byWhoId`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
