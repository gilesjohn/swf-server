# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.7.21)
# Database: spotify
# Generation Time: 2019-04-21 22:43:13 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table friends
# ------------------------------------------------------------

DROP TABLE IF EXISTS `friends`;

CREATE TABLE `friends` (
  `leader` varchar(64) NOT NULL DEFAULT '',
  `listener` varchar(64) NOT NULL DEFAULT '',
  `listening` tinyint(1) NOT NULL,
  PRIMARY KEY (`leader`,`listener`),
  KEY `leader` (`leader`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table rate_limit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `rate_limit`;

CREATE TABLE `rate_limit` (
  `next_request` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `rate_limit` WRITE;
/*!40000 ALTER TABLE `rate_limit` DISABLE KEYS */;

INSERT INTO `rate_limit` (`next_request`)
VALUES
	(0);

/*!40000 ALTER TABLE `rate_limit` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sessions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `session_id` varchar(64) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `session_start` int(11) NOT NULL,
  `session_end` int(11) NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table spotify_callback
# ------------------------------------------------------------

DROP TABLE IF EXISTS `spotify_callback`;

CREATE TABLE `spotify_callback` (
  `state` varchar(64) NOT NULL DEFAULT '',
  `url` varchar(128) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table syncs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `syncs`;

CREATE TABLE `syncs` (
  `started` int(10) unsigned DEFAULT NULL,
  `finished` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

LOCK TABLES `syncs` WRITE;
/*!40000 ALTER TABLE `syncs` DISABLE KEYS */;

INSERT INTO `syncs` (`started`, `finished`)
VALUES
	(NULL,0);

/*!40000 ALTER TABLE `syncs` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `code` varchar(512) DEFAULT NULL,
  `refresh` varchar(512) DEFAULT NULL,
  `access` varchar(512) DEFAULT NULL,
  `access_expire` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
