DROP DATABASE intelligence_artificielle;
CREATE DATABASE intelligence_artificielle
  DEFAULT CHARACTER SET utf8
  DEFAULT COLLATE utf8_general_ci;
USE intelligence_artificielle;

CREATE TABLE IF NOT EXISTS `regle` (
  `id` int(11) unsigned AUTO_INCREMENT,
  `proposition` varchar(64) DEFAULT '',
  `verbe` varchar(64) DEFAULT '',
  `negative` tinyint(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `relations` (
  `conclusion` int(11) unsigned,
  `premisse_of` int(11) unsigned,
  PRIMARY KEY (`premisse_of`,`conclusion`),
  FOREIGN KEY (`conclusion`) REFERENCES `regle` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`premisse_of`) REFERENCES `regle` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;