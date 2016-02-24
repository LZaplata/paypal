-- Adminer 4.1.0 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `meta_description` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `expirationDateFrom` datetime DEFAULT NULL,
  `expirationDateTo` datetime DEFAULT NULL,
  `description` text COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `sections_id` int(11) NOT NULL,
  `galleries_id` int(11) NOT NULL,
  `filestores_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `articles_categories`;
CREATE TABLE `articles_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `articles_id` int(11) NOT NULL,
  `categories_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

DROP TABLE IF EXISTS `articles_pages`;
CREATE TABLE `articles_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `articles_id` int(11) NOT NULL,
  `pages_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `articles_id` (`articles_id`),
  KEY `pages_id` (`pages_id`),
  CONSTRAINT `articles_pages_ibfk_1` FOREIGN KEY (`articles_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `articles_pages_ibfk_2` FOREIGN KEY (`pages_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

DROP TABLE IF EXISTS `articles_tags`;
CREATE TABLE `articles_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_articles` int(11) NOT NULL,
  `articles_id` int(11) NOT NULL,
  `editors_id` int(11) NOT NULL,
  `products_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `booking`;
CREATE TABLE `booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `price` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `email` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `phone` int(11) NOT NULL,
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `capacity` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `booking_bookings`;
CREATE TABLE `booking_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `booking_objects_id` int(11) NOT NULL,
  `dateFrom` datetime NOT NULL,
  `dateTo` datetime NOT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `booking_objects_id` (`booking_objects_id`),
  CONSTRAINT `booking_bookings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_bookings_ibfk_2` FOREIGN KEY (`booking_objects_id`) REFERENCES `booking_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `booking_hours`;
CREATE TABLE `booking_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_rooms_id` int(11) NOT NULL,
  `open_hour` int(11) NOT NULL,
  `close_hour` int(11) NOT NULL,
  `open_minute` int(11) NOT NULL,
  `close_minute` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_rooms_id` (`booking_rooms_id`),
  CONSTRAINT `booking_hours_ibfk_1` FOREIGN KEY (`booking_rooms_id`) REFERENCES `booking_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `booking_objects`;
CREATE TABLE `booking_objects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `booking_rooms_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_rooms_id` (`booking_rooms_id`),
  CONSTRAINT `booking_objects_ibfk_1` FOREIGN KEY (`booking_rooms_id`) REFERENCES `booking_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `booking_prices`;
CREATE TABLE `booking_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `booking_objects_id` int(11) NOT NULL,
  `dateFrom` date DEFAULT NULL,
  `dateTo` date DEFAULT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_objects_id` (`booking_objects_id`),
  CONSTRAINT `booking_prices_ibfk_1` FOREIGN KEY (`booking_objects_id`) REFERENCES `booking_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `booking_rooms`;
CREATE TABLE `booking_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `discount` int(11) DEFAULT NULL,
  `interval` int(11) DEFAULT NULL,
  `interval_divisor` int(11) DEFAULT NULL,
  `interval_min` int(11) DEFAULT NULL,
  `interval_max` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `capacity_min` int(11) DEFAULT NULL,
  `capacity_max` int(11) DEFAULT NULL,
  `layout` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `description` text COLLATE utf8_czech_ci NOT NULL,
  `sections_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL,
  `galleries_id` int(11) NOT NULL,
  `discount` float DEFAULT NULL,
  `categories_heureka_id` int(11) DEFAULT NULL,
  `categories_zbozi_id` int(11) DEFAULT NULL,
  `categories_merchants_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_zbozi_id` (`categories_zbozi_id`),
  KEY `categories_heureka_id` (`categories_heureka_id`),
  KEY `categories_merchants_id` (`categories_merchants_id`),
  CONSTRAINT `categories_ibfk_3` FOREIGN KEY (`categories_zbozi_id`) REFERENCES `categories_zbozi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_ibfk_5` FOREIGN KEY (`categories_heureka_id`) REFERENCES `categories_heureka` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_ibfk_6` FOREIGN KEY (`categories_merchants_id`) REFERENCES `categories_merchants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `categories_categories`;
CREATE TABLE `categories_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_category` int(11) NOT NULL,
  `categories_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_category` (`id_category`),
  KEY `categories_id` (`categories_id`),
  CONSTRAINT `categories_categories_ibfk_1` FOREIGN KEY (`id_category`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `categories_categories_ibfk_2` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `categories_heureka`;
CREATE TABLE `categories_heureka` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `heureka_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `name_full` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `categories_merchants`;
CREATE TABLE `categories_merchants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `merchants_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `name_full` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `categories_zbozi`;
CREATE TABLE `categories_zbozi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `old` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `editors`;
CREATE TABLE `editors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `date` datetime DEFAULT NULL,
  `users_id` int(11) NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `sections_id` int(11) NOT NULL,
  `pages_modules_id` int(11) NOT NULL,
  `galleries_id` int(11) NOT NULL,
  `filestores_id` int(11) NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `emails`;
CREATE TABLE `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `galleries_id` int(11) NOT NULL,
  `filestores_id` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `emails_content`;
CREATE TABLE `emails_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emails_id` int(11) NOT NULL,
  `articles_id` int(11) NOT NULL DEFAULT '0',
  `products_id` int(11) NOT NULL DEFAULT '0',
  `editors_id` int(11) NOT NULL DEFAULT '0',
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `emails_queue`;
CREATE TABLE `emails_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `emails_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `categories_id` int(11) NOT NULL,
  `date` datetime DEFAULT NULL,
  `send` datetime DEFAULT NULL,
  `view` datetime DEFAULT NULL,
  `logout` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `filestores`;
CREATE TABLE `filestores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `order` tinyint(3) NOT NULL DEFAULT '2',
  `direction` tinyint(3) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `filestores_files`;
CREATE TABLE `filestores_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `filestores_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `tag` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `filestores_files_tags`;
CREATE TABLE `filestores_files_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filestores_files_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `filters`;
CREATE TABLE `filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` text COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `form_fields`;
CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `type` tinyint(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `form_fields_options`;
CREATE TABLE `form_fields_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_fields_id` int(11) NOT NULL,
  `value` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_fields_id` (`form_fields_id`),
  CONSTRAINT `form_fields_options_ibfk_1` FOREIGN KEY (`form_fields_id`) REFERENCES `form_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `form_groups`;
CREATE TABLE `form_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `form_groups_fields`;
CREATE TABLE `form_groups_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_fields_id` int(11) NOT NULL,
  `form_groups_id` int(11) NOT NULL,
  `required` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `form_fields_id` (`form_fields_id`),
  KEY `form_groups_id` (`form_groups_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `galleries`;
CREATE TABLE `galleries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `order` tinyint(3) NOT NULL DEFAULT '2',
  `direction` tinyint(3) NOT NULL DEFAULT '2',
  `lmt` int(11) NOT NULL DEFAULT '20',
  `paginator` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `galleries_images`;
CREATE TABLE `galleries_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `galleries_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `galleries_images_tags`;
CREATE TABLE `galleries_images_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `galleries_images_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `key` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `position` int(11) NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `localization`;
CREATE TABLE `localization` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `text_id` int(1) unsigned NOT NULL,
  `lang` char(3) NOT NULL,
  `variant` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `translation` varchar(255) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `text_id` (`text_id`,`lang`,`variant`),
  CONSTRAINT `x` FOREIGN KEY (`text_id`) REFERENCES `localization_text` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='text translations';


DROP TABLE IF EXISTS `localization_text`;
CREATE TABLE `localization_text` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(255) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `text` (`text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='default texts for translations';


DROP TABLE IF EXISTS `log_login`;
CREATE TABLE `log_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  CONSTRAINT `log_login_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `log_search`;
CREATE TABLE `log_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `query` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `modules` (`id`, `name`) VALUES
(1,	'textové pole'),
(2,	'články'),
(3,	'e-shop'),
(4,	'rezervace');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no` int(11) DEFAULT NULL,
  `users_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `price` float NOT NULL,
  `currency` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `rate` float NOT NULL DEFAULT '1',
  `state` tinyint(3) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `surname` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `ic` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `dic` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `psc` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `delivery_name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `delivery_surname` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `delivery_street` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `delivery_psc` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `delivery_city` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `transport_id` int(11) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `transport` float DEFAULT NULL,
  `payment` float DEFAULT NULL,
  `trash` tinyint(1) NOT NULL DEFAULT '0',
  `text` text COLLATE utf8_czech_ci,
  `zasilkovna` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `orders_products`;
CREATE TABLE `orders_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orders_id` int(11) NOT NULL,
  `products_id` int(11) NOT NULL,
  `price` float DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT '1',
  `state` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `description` text COLLATE utf8_czech_ci NOT NULL,
  `layout` int(11) NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL DEFAULT '0',
  `position` int(11) NOT NULL,
  `menu` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `pages` (`id`, `name`, `url`, `title`, `keywords`, `description`, `layout`, `visibility`, `highlight`, `pid`, `position`, `menu`) VALUES
(1,	'Úvod',	'uvod',	'Úvod',	'Úvod',	'Úvod',	1,	1,	1,	0,	0,	0);

DROP TABLE IF EXISTS `pages_modules`;
CREATE TABLE `pages_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pages_id` int(11) NOT NULL,
  `modules_id` int(11) NOT NULL,
  `sections_id` int(11) NOT NULL,
  `layout` int(11) NOT NULL,
  `detail` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `lmt` int(11) NOT NULL,
  `cols` int(11) NOT NULL DEFAULT '1',
  `order` tinyint(3) NOT NULL,
  `direction` tinyint(3) NOT NULL,
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `paginator` tinyint(3) NOT NULL DEFAULT '0',
  `contact_form` tinyint(1) NOT NULL DEFAULT '0',
  `heading_level` int(3) NOT NULL DEFAULT '1',
  `menu` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `pages_modules` (`id`, `pages_id`, `modules_id`, `sections_id`, `layout`, `detail`, `position`, `lmt`, `cols`, `order`, `direction`, `highlight`, `paginator`, `contact_form`) VALUES
(1,	1,	1,	1,	1,	0,	1,	0,	1,	0,	0,	0,	0,	0);

DROP TABLE IF EXISTS `pages_modules_categories`;
CREATE TABLE `pages_modules_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categories_id` int(11) NOT NULL,
  `pages_modules_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text COLLATE utf8_czech_ci,
  `name` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `surname` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `users_id` int(11) DEFAULT NULL,
  `products_id` int(11) NOT NULL,
  `posts_id` int(11) NOT NULL DEFAULT '0',
  `trash` tinyint(1) NOT NULL DEFAULT '0',
  `view` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` tinyint(1) NOT NULL DEFAULT '1',
  `date` datetime DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `update` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `meta_description` text COLLATE utf8_czech_ci,
  `ean` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `code` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `price` float NOT NULL,
  `price_discount` float NULL,
  `price_filter` float NULL,
  `delivery_date` char(3) COLLATE utf8_czech_ci DEFAULT '0',
  `amount` int(11) NOT NULL,
  `expirationDateFrom` datetime DEFAULT NULL,
  `expirationDateTo` datetime DEFAULT NULL,
  `description` text COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  `galleries_id` int(11) NOT NULL,
  `filestores_id` int(11) NOT NULL,
  `pid` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `tax` tinyint(3) DEFAULT NULL,
  `products_id` int(11) NOT NULL DEFAULT '0',
  `users_id` int(11) DEFAULT NULL,
  `trash` tinyint(1) NOT NULL DEFAULT '0',
  `properties` text COLLATE utf8_czech_ci,
  `producer` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products_categories`;
CREATE TABLE `products_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `products_id` int(11) NOT NULL,
  `categories_id` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products_discounts`;
CREATE TABLE `products_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `products_id` int(11) NOT NULL,
  `discount` float DEFAULT NULL,
  `amount` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products_form_groups`;
CREATE TABLE `products_form_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `products_id` int(11) NOT NULL,
  `form_groups_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `products_id` (`products_id`),
  KEY `form_groups_id` (`form_groups_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products_prices`;
CREATE TABLE `products_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `products_id` int(11) NOT NULL,
  `price` float NOT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products_properties`;
CREATE TABLE `products_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `products_id` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `products_id` (`products_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `products_related`;
CREATE TABLE `products_related` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_products` int(11) NOT NULL,
  `products_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `modules_id` int(11) NOT NULL,
  `visName` tinyint(3) NOT NULL DEFAULT '1',
  `title` tinyint(3) NOT NULL DEFAULT '1',
  `keywords` tinyint(3) NOT NULL DEFAULT '1',
  `meta_description` tinyint(3) NOT NULL DEFAULT '1',
  `categories` tinyint(3) NOT NULL DEFAULT '1',
  `date` tinyint(3) NOT NULL DEFAULT '1',
  `expirationDate` tinyint(3) NOT NULL DEFAULT '0',
  `description` tinyint(3) NOT NULL DEFAULT '1',
  `text` tinyint(3) NOT NULL DEFAULT '1',
  `files` tinyint(3) NOT NULL DEFAULT '0',
  `gallery` tinyint(3) NOT NULL DEFAULT '0',
  `tags` tinyint(3) NOT NULL DEFAULT '0',
  `versions` tinyint(3) NOT NULL DEFAULT '0',
  `author` tinyint(3) NOT NULL DEFAULT '0',
  `watermark` tinyint(3) NOT NULL DEFAULT '0',
  `slider` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `sections` (`id`, `name`, `modules_id`, `visName`, `title`, `keywords`, `meta_description`, `categories`, `date`, `expirationDate`, `description`, `text`, `files`, `gallery`, `tags`, `versions`, `author`, `watermark`) VALUES
(1,	'textové stránky',	1,	1,	1,	1,	1,	1,	0,	0,	1,	1,	0,	0,	0,	0,	0,	0);

DROP TABLE IF EXISTS `sections_fields`;
CREATE TABLE `sections_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `type` int(11) NOT NULL,
  `default` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `sections_id` int(11) NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  `values` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `sections_tags`;
CREATE TABLE `sections_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `sections_id` int(11) NOT NULL,
  `modules_id` int(11) NOT NULL,
  `id_section` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `sections_thumbs`;
CREATE TABLE `sections_thumbs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sections_id` int(11) NOT NULL,
  `dimension` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `operation` tinyint(3) NOT NULL,
  `place` tinyint(3) NOT NULL,
  `watermark` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `seo_kw`;
CREATE TABLE `seo_kw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `main` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `vars` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `analyticsUID` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `analyticsURL` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `googleAPIKey` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `eshop` tinyint(1) NOT NULL DEFAULT '0',
  `mailing` tinyint(1) NOT NULL DEFAULT '0',
  `booking` tinyint(1) NOT NULL DEFAULT '0',
  `singlepage` tinyint(1) NOT NULL DEFAULT '0',
  `addthisActive` tinyint(1) NOT NULL DEFAULT '0',
  `addthis` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `webmasterToolsVerification` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `sklikConversion` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `heurekaConversion` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `heurekaVerification` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `contact_to` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `contact_cc` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `contact_bcc` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title_editors` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title_articles` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title_articles_categories` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title_products` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `title_products_categories` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `seo_assist` tinyint(1) NOT NULL DEFAULT '0',
  `context_help` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `booking` (`booking`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `settings` (`id`, `analyticsUID`, `analyticsURL`, `googleAPIKey`, `eshop`, `mailing`, `booking`, `singlepage`, `addthis`, `webmasterToolsVerification`, `sklikConversion`, `heurekaConversion`, `heurekaVerification`, `contact_to`, `contact_cc`, `contact_bcc`, `title_editors`, `title_articles`, `title_articles_categories`, `title_products`, `title_products_categories`) VALUES
(1,	NULL,	NULL,	NULL,	0,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `shop_methods`;
CREATE TABLE `shop_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `type` tinyint(3) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `shop_methods_relations`;
CREATE TABLE `shop_methods_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_methods_id` int(11) DEFAULT NULL,
  `id_shop_methods` int(11) DEFAULT NULL,
  `price` float NOT NULL DEFAULT '0',
  `max` float DEFAULT NULL,
  `country` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `highlight` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `shop_properties`;
CREATE TABLE `shop_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categories_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `categories_id` (`categories_id`),
  CONSTRAINT `shop_properties_ibfk_1` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `shop_settings`;
CREATE TABLE `shop_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinyint(3) NOT NULL DEFAULT '1',
  `title` tinyint(3) NOT NULL DEFAULT '1',
  `keywords` tinyint(3) NOT NULL DEFAULT '1',
  `ean` tinyint(3) NOT NULL DEFAULT '0',
  `code` tinyint(3) NOT NULL DEFAULT '0',
  `expirationDate` tinyint(3) NOT NULL DEFAULT '0',
  `price` tinyint(3) NOT NULL DEFAULT '1',
  `stock` tinyint(3) NOT NULL DEFAULT '1',
  `description` tinyint(3) NOT NULL DEFAULT '1',
  `text` tinyint(3) NOT NULL DEFAULT '1',
  `files` tinyint(3) NOT NULL DEFAULT '0',
  `gallery` tinyint(3) NOT NULL DEFAULT '1',
  `siblings` tinyint(3) NOT NULL DEFAULT '0',
  `modules_id` tinyint(3) NOT NULL DEFAULT '3',
  `versions` tinyint(3) NOT NULL DEFAULT '0',
  `watermark` tinyint(3) NOT NULL DEFAULT '0',
  `discounts` tinyint(1) NOT NULL DEFAULT '0',
  `posts` tinyint(1) NOT NULL DEFAULT '0',
  `dynamicform` tinyint(1) NOT NULL DEFAULT '0',
  `heureka` tinyint(1) NOT NULL DEFAULT '0',
  `zbozi` tinyint(1) NOT NULL DEFAULT '0',
  `merchants` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `password` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `company` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `ic` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `dic` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `street` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `city` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `psc` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `delivery_name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `delivery_surname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `delivery_street` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `delivery_city` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `delivery_psc` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `role` varchar(255) COLLATE utf8_czech_ci NOT NULL DEFAULT 'user',
  `newsletter` tinyint(3) NOT NULL DEFAULT '0',
  `discount` float DEFAULT NULL,
  `posts` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `users` (`id`, `password`, `name`, `surname`, `email`, `company`, `ic`, `dic`, `street`, `city`, `psc`, `phone`, `delivery_name`, `delivery_surname`, `delivery_street`, `delivery_city`, `delivery_psc`, `role`, `newsletter`, `discount`, `posts`) VALUES
(1,	'a96fe27333d92f649ce880d1b6944856478a6e16cf65458b9c12a25c54169fec86f9efd76e5a0ef2ca57cd2b49240d486654e3131d0c0595c323e9a2a54dcae0',	'Lukáš',	'Záplata',	'zap@hucr.cz',	'',	'',	'',	'Libušinka 403',	'Trutnov',	'54101',	'775652656',	'',	'',	'',	'',	'',	'superadmin',	1,	NULL,	0),
(2,	'4a82822c90c131a026295230fd7181eba1362226f3b0c88066bc64a9d0a79b55b659f39dc8724c5f41a36be7d77356350a790a2cdef620ba574097b6485e13ba',	'Humlnet',	'Creative',	'info@hucr.cz',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'superadmin',	1,	NULL,	0),
(3,	'414bd875f918b1ca00746c2c169755dd3c79a0836dbc7ece3af9ec8b6c484f9c03408d4823dd7bcdb78e99df422bb134bc40a9a3c7470275ed7f814758f1f7e1',	'Adam',	'Valenta',	'valenta@hucr.cz',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'',	'admin',	0,	NULL,	0);

DROP TABLE IF EXISTS `users_categories`;
CREATE TABLE `users_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `categories_id` int(11) NOT NULL,
  `visibility` tinyint(3) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `users_privileges`;
CREATE TABLE `users_privileges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sections_id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2015-08-17 08:45:45