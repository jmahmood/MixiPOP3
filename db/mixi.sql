--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `miximessage_id` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `page` tinyint(4) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `to` int(11) DEFAULT NULL,
  `from` int(11) DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `details` longtext COLLATE utf8_unicode_ci NOT NULL,
  `box` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`miximessage_id`),
  KEY `datetime` (`datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS `profile` (
  `mixi_id` int(10) unsigned NOT NULL,
  `nickname` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `sex` enum('男性','女性') COLLATE utf8_unicode_ci DEFAULT NULL,
  `location` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `age` tinyint(3) unsigned DEFAULT NULL,
  `birthday` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `bloodtype` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `hometown` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `hobby` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `introduction` longtext COLLATE utf8_unicode_ci NOT NULL,
  `profile_picture1` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `profile_picture2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `profile_picture3` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `foods` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `job` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `belong_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`mixi_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `ashiato` (
  `datetime` datetime NOT NULL,
  `from` int(11) DEFAULT NULL,
  `relationship` enum('friend','friend-of-friend') COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `from` (`from`,`datetime`),
  KEY `datetime` (`datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;