-- phpMyAdmin SQL Dump
-- version 2.11.5.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 06, 2008 at 06:43 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.5-3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `melindas_chat`
--

-- --------------------------------------------------------

--
-- Table structure for table `emoticons`
--

CREATE TABLE IF NOT EXISTS `emoticons` (
  `key` varchar(20) NOT NULL COMMENT 'Code, excluding '':'' which is used in text to be replaced by image',
  `filename` varchar(50) NOT NULL COMMENT 'image file name',
  PRIMARY KEY  (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii COMMENT='Used to store emoticons';

--
-- Dumping data for table `emoticons`
--

INSERT INTO `emoticons` (`key`, `filename`) VALUES
('banana', 'banana.gif'),
('wall', 'brickwall.gif'),
('bye', 'bye.gif'),
('cheeky', 'cheeky.gif'),
('cry', 'cry.gif'),
('dunno', 'dunno.gif'),
('rage', 'enraged.gif'),
('har', 'har.gif'),
('help', 'help.gif'),
('hug', 'hug.gif'),
('mad', 'mad.gif'),
('out', 'out.gif'),
('pic', 'pickle.gif'),
('prrr', 'prrr.gif'),
('rofl', 'rofl.gif'),
('-)', 'smiley.gif'),
('thx', 'thanks.gif'),
('yes', 'thumbsup.gif'),
('wub', 'wub.gif'),
('yikes', 'yikes.gif'),
('zzz', 'zzz.gif'),
('cheer', 'cheer.gif');

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `lid` bigint(20) unsigned NOT NULL auto_increment,
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'time as utc',
  `uid` mediumint(8) NOT NULL COMMENT 'user id of sender',
  `name` tinytext NOT NULL,
  `role` enum('A','L','M','B','H','G','S','R') NOT NULL,
  `rid` bigint(20) NOT NULL COMMENT 'this is either a rid or wid',
  `type` enum('LI','LO','LT','RE','RX','RM','RN','WJ','WL','LH','ME','WH','MQ','MR','PE','PX') NOT NULL COMMENT 'Login Room Whisper Message',
  `text` text,
  PRIMARY KEY  (`lid`),
  KEY `time` (`time`)
) ENGINE=MyISAM  DEFAULT CHARSET=ascii AUTO_INCREMENT=88 ;

--
-- Dumping data for table `log`
--


-- --------------------------------------------------------

--
-- Table structure for table `participant`
--

CREATE TABLE IF NOT EXISTS `participant` (
  `uid` mediumint(9) NOT NULL,
  `wid` bigint(20) NOT NULL,
  PRIMARY KEY  (`uid`,`wid`),
  KEY `uid` (`uid`),
  KEY `wid` (`wid`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='Members of a Whisper Channel';

--
-- Dumping data for table `participant`
--


-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE IF NOT EXISTS `rooms` (
  `rid` tinyint(4) NOT NULL COMMENT 'room id ',
  `name` varchar(30) NOT NULL COMMENT 'room name',
  `type` enum('O','M','C') NOT NULL default 'C' COMMENT 'Open, Baby, Adult, Moderated or Committee',
  `smf_group` tinyint(4) default NULL COMMENT 'The SMF Group which defines access to this room',
  PRIMARY KEY  (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='Rooms in Chat';

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES
(1, 'Members Lounge', 'O', NULL),
(2, 'The Blue Room', 'O', NULL),
(3, 'The Green Room', 'O', NULL),
(4, 'Vamp Club', 'O', NULL),
(5, 'Auditorium', 'M', NULL),
(6, 'Lead Team', 'C', 9),
(7, 'The Music Room', 'C', 12),
(8, 'The Africa Room', 'C', 16),
(9, 'The News Room', 'C', 17),
(10, 'The Spiders Lair', 'C', 18);

-- --------------------------------------------------------

--
-- Table structure for table `scholarship`
--

CREATE TABLE IF NOT EXISTS `scholarship` (
  `no` tinyint(4) NOT NULL default '1',
  `amount` int(11) NOT NULL,
  PRIMARY KEY  (`no`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;

--
-- Dumping data for table `scholarship`
--

INSERT INTO `scholarship` (`no`, `amount`) VALUES
(1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `uid` mediumint(8) NOT NULL COMMENT 'id from smf',
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'last activity by user',
  `name` tinytext NOT NULL COMMENT 'display name in smf',
  `role` enum('A','L','H','B','G','R','M','S') NOT NULL default 'R' COMMENT 'Role on the forum, which contributes, but doesn''t completely define) role in room',
  `rid` tinyint(4) NOT NULL default '0' COMMENT 'room id',
  `moderator` enum('A','L','H','B','G','R','M','N','S') NOT NULL default 'N' COMMENT 'Reseve role in moderated rooms',
  `question` text COMMENT 'Pending Question to be asked in Moderated Room',
  `private` bigint(20) NOT NULL default '0' COMMENT 'If non zero is in whisper as a private room',
  PRIMARY KEY  (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='Users - mirroring SMF database to some extent';

--
-- Dumping data for table `users`
--


-- --------------------------------------------------------

--
-- Table structure for table `whisper`
--

CREATE TABLE IF NOT EXISTS `whisper` (
  `wid` bigint(20) NOT NULL,
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`wid`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COMMENT='Whisper Channel - Really for auto_increment';

--
-- Dumping data for table `whisper`
--

INSERT INTO `whisper` (`wid`, `time`) VALUES
(100, '2008-03-30 21:25:04');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `participant`
--
ALTER TABLE `participant`
  ADD CONSTRAINT `participant_ibfk_3` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;
