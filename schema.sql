-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 26, 2016 at 11:17 PM
-- Server version: 5.5.46-MariaDB-1~wheezy
-- PHP Version: 5.6.24-1~dotdeb+7.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `redditbooru2`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `proc_GetLinkedPosts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetLinkedPosts`(IN postId INT)
BEGIN

    SELECT
        DISTINCT `post_id`,
        `post_title`,
        `post_date`,
        `post_external_id`,
        `source_name`,
        `user_name`
    FROM
        `post_data`
    WHERE
        `post_id` IN (
            SELECT
                `post_id`
            FROM
                `posts`
            WHERE
                `post_link` LIKE (
                    SELECT
                        CONCAT(`post_link`, '%')
                    FROM
                        `posts`
                    WHERE
                        `post_id` = postId
                    LIMIT 1
                )
                AND `post_id` != postId
            );

END$$

DROP PROCEDURE IF EXISTS `proc_UpdateDenormalizedPostData`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_UpdateDenormalizedPostData`(IN postId INT)
BEGIN

    DECLARE imageId, postDataId INT;
    DECLARE done INT DEFAULT FALSE;

    DECLARE postData CURSOR FOR
        SELECT
            `pd_id`,
            `image_id`
        FROM
            `post_data`
        WHERE
            `post_id` = postId;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN postData;

    WHILE done = FALSE DO

        FETCH postData INTO postDataId, imageId;

        UPDATE
            `post_data` pd
        INNER JOIN
            `posts` p ON p.`post_id` = postId
        INNER JOIN
            `images` i ON i.`image_id` = imageId
        LEFT JOIN
            `sources` s ON s.`source_id` = p.`source_id`
        LEFT JOIN
            `users` u ON u.`user_id` = p.`user_id`
        SET
            pd.`post_title` = p.`post_title`,
            pd.`post_keywords` = p.`post_keywords`,
            pd.`post_nsfw` = p.`post_nsfw`,
            pd.`post_score` = p.`post_score`,
            pd.`post_visible` = p.`post_visible`,
            pd.`user_id` = u.`user_id`,
            pd.`user_name` = u.`user_name`,
            pd.`source_id` = s.`source_id`,
            pd.`source_name` = s.`source_name`,
            pd.`image_caption` = i.`image_caption`,
            pd.`image_source` = i.`image_source`
        WHERE
            pd.`pd_id` = postDataId;


    END WHILE;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bot_users`
--

DROP TABLE IF EXISTS `bot_users`;
CREATE TABLE IF NOT EXISTS `bot_users` (
  `bot_id` int(10) NOT NULL AUTO_INCREMENT,
  `bot_name` varchar(50) NOT NULL,
  `bot_password` varchar(50) NOT NULL,
  `bot_hash` varchar(255) DEFAULT NULL,
  `bot_cookie` varchar(255) DEFAULT NULL,
  `bot_data` text,
  `bot_callback` varchar(30) NOT NULL,
  `bot_updated` int(10) NOT NULL,
  `bot_created` int(10) NOT NULL,
  `bot_enabled` tinyint(4) NOT NULL,
  PRIMARY KEY (`bot_id`),
  UNIQUE KEY `bot_name` (`bot_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE IF NOT EXISTS `images` (
  `image_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `image_caption` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_hist_r1` float NOT NULL,
  `image_hist_r2` float NOT NULL,
  `image_hist_r3` float NOT NULL,
  `image_hist_r4` float NOT NULL,
  `image_hist_g1` float NOT NULL,
  `image_hist_g2` float NOT NULL,
  `image_hist_g3` float NOT NULL,
  `image_hist_g4` float NOT NULL,
  `image_hist_b1` float NOT NULL,
  `image_hist_b2` float NOT NULL,
  `image_hist_b3` float NOT NULL,
  `image_hist_b4` float NOT NULL,
  `image_height` int(11) DEFAULT NULL,
  `image_width` int(11) DEFAULT NULL,
  `image_type` char(3) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`image_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_external_id` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_date` int(11) NOT NULL,
  `post_updated` int(11) NOT NULL,
  `post_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_keywords` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_score` int(11) DEFAULT NULL,
  `post_visible` bit(1) DEFAULT NULL,
  `post_nsfw` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`post_id`),
  UNIQUE KEY `UC_source_id_post_external_id` (`source_id`,`post_external_id`),
  KEY `FK_posts_source_id` (`source_id`),
  KEY `post_external_id` (`post_external_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_data`
--

DROP TABLE IF EXISTS `post_data`;
CREATE TABLE IF NOT EXISTS `post_data` (
  `pd_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `image_width` int(11) NOT NULL,
  `image_height` int(11) NOT NULL,
  `image_caption` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `source_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_keywords` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_nsfw` tinyint(1) DEFAULT NULL,
  `post_date` int(11) NOT NULL,
  `post_external_id` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_score` int(11) NOT NULL,
  `post_visible` tinyint(4) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image_type` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`pd_id`),
  KEY `FK_post_data_post_id` (`post_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

DROP TABLE IF EXISTS `post_images`;
CREATE TABLE IF NOT EXISTS `post_images` (
  `image_id` bigint(20) NOT NULL,
  `post_id` int(11) NOT NULL,
  KEY `FK_post_images_post_id` (`post_id`),
  KEY `FK_post_images_image_id` (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

DROP TABLE IF EXISTS `sources`;
CREATE TABLE IF NOT EXISTS `sources` (
  `source_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `source_baseurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `source_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `source_enabled` bit(1) NOT NULL,
  `source_subdomain` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_generate_report` bit(1) NOT NULL,
  `source_content_rating` smallint(1) NOT NULL,
  `source_repost_check` int(11) DEFAULT NULL,
  PRIMARY KEY (`source_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `user_reddit_id` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_date_created` int(11) NOT NULL,
  `user_link_karma` int(11) NOT NULL,
  `user_comment_karma` int(11) NOT NULL,
  `user_avatar` bit(1) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `FK_posts_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_data`
--
ALTER TABLE `post_data`
  ADD CONSTRAINT `FK_post_data_post_id` FOREIGN KEY (`post_id`) REFERENCES `post_images` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_images`
--
ALTER TABLE `post_images`
  ADD CONSTRAINT `FK_post_images_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_post_images_post_id` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;
