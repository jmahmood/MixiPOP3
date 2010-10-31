-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 31, 2010 at 12:14 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mixi`
--

-- --------------------------------------------------------

--
-- Table structure for table `ashiato`
--

CREATE TABLE IF NOT EXISTS `ashiato` (
  `datetime` datetime NOT NULL,
  `from` int(11) DEFAULT NULL,
  `relationship` enum('friend','friend-of-friend') COLLATE utf8_unicode_ci DEFAULT NULL,
  UNIQUE KEY `from` (`from`,`datetime`),
  KEY `datetime` (`datetime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE IF NOT EXISTS `message` (
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

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `thread_id` int(11) NOT NULL,
  `community` int(11) NOT NULL,
  `thread_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message_order` int(11) NOT NULL,
  `from` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `contents` longtext COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`thread_id`,`message_order`),
  KEY `community` (`community`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE IF NOT EXISTS `profile` (
  `mixi_id` int(10) unsigned NOT NULL,
  `nickname` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `sex` enum('ç”·æ€§','å¥³æ€§') COLLATE utf8_unicode_ci DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE IF NOT EXISTS `threads` (
  `thread_id` int(11) NOT NULL,
  `community` int(11) NOT NULL,
  `community_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `datetime` datetime NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`thread_id`),
  KEY `community` (`community`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
