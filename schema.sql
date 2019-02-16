-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 14, 2019 at 08:20 AM
-- Server version: 10.0.17-MariaDB-1~wheezy-log
-- PHP Version: 7.0.33-1~dotdeb+8.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `redditbooru2`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `proc_GetLinkedPosts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_GetLinkedPosts` (IN `postId` INT)  BEGIN

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
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_UpdateDenormalizedPostData` (IN `postId` INT)  BEGIN

    DECLARE isInPostData INT;
    SELECT
        COUNT(1) INTO isInPostData
    FROM
        `post_data`
    WHERE
        `post_id` = postId;

    IF isInPostData = 0 THEN
        BEGIN
            INSERT INTO
                `post_data`
            (
                `post_id`,
                `post_title`,
                `post_keywords`,
                `post_nsfw`,
                `post_date`,
                `post_external_id`,
                `post_score`,
                `post_visible`,
                `image_id`,
                `image_width`,
                `image_height`,
                `image_type`,
                `image_caption`,
                `image_source`,
                `source_id`,
                `source_name`,
                `user_id`,
                `user_name`
            )
            SELECT
                p.`post_id`,
                p.`post_title`,
                p.`post_keywords`,
                p.`post_nsfw`,
                p.`post_date`,
                p.`post_external_id`,
                p.`post_score`,
                p.`post_visible`,
                i.`image_id`,
                i.`image_width`,
                i.`image_height`,
                i.`image_type`,
                i.`image_caption`,
                i.`image_source`,
                s.`source_id`,
                s.`source_name`,
                u.`user_id`,
                u.`user_name`
            FROM
                `post_images` pi
            INNER JOIN
                `posts` p ON p.`post_id` = pi.`post_id`
            INNER JOIN
                `images` i ON i.`image_id` = pi.`image_id`
            LEFT JOIN
                `sources` s ON s.`source_id` = p.`source_id`
            LEFT JOIN
                `users` u ON u.`user_id` = p.`user_id`
            WHERE
                pi.`post_id` = postId;
        END;
    ELSE
        BEGIN

            DECLARE imageId, postDataId INT;
            DECLARE updateDone INT DEFAULT FALSE;
            DECLARE postData CURSOR FOR
                SELECT
                    `pd_id`,
                    `image_id`
                FROM
                    `post_data`
                WHERE
                    `post_id` = postId;

            DECLARE CONTINUE HANDLER FOR NOT FOUND SET updateDone = TRUE;

            OPEN postData;
            WHILE updateDone = FALSE DO

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

        END;
    END IF;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bot_users`
--

DROP TABLE IF EXISTS `bot_users`;
CREATE TABLE `bot_users` (
  `bot_id` int(10) NOT NULL,
  `bot_name` varchar(50) NOT NULL,
  `bot_password` varchar(50) NOT NULL,
  `bot_hash` varchar(255) DEFAULT NULL,
  `bot_cookie` varchar(255) DEFAULT NULL,
  `bot_data` text,
  `bot_callback` varchar(30) NOT NULL,
  `bot_updated` int(10) NOT NULL,
  `bot_created` int(10) NOT NULL,
  `bot_enabled` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `image_id` bigint(20) NOT NULL,
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
  `image_dhashr` bigint(20) DEFAULT NULL,
  `image_dhashg` bigint(20) DEFAULT NULL,
  `image_dhashb` bigint(20) DEFAULT NULL,
  `image_height` int(11) DEFAULT NULL,
  `image_width` int(11) DEFAULT NULL,
  `image_type` char(3) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_b1`
--

DROP TABLE IF EXISTS `img_lookup_b1`;
CREATE TABLE `img_lookup_b1` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_b2`
--

DROP TABLE IF EXISTS `img_lookup_b2`;
CREATE TABLE `img_lookup_b2` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_b3`
--

DROP TABLE IF EXISTS `img_lookup_b3`;
CREATE TABLE `img_lookup_b3` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_b4`
--

DROP TABLE IF EXISTS `img_lookup_b4`;
CREATE TABLE `img_lookup_b4` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_g1`
--

DROP TABLE IF EXISTS `img_lookup_g1`;
CREATE TABLE `img_lookup_g1` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_g2`
--

DROP TABLE IF EXISTS `img_lookup_g2`;
CREATE TABLE `img_lookup_g2` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_g3`
--

DROP TABLE IF EXISTS `img_lookup_g3`;
CREATE TABLE `img_lookup_g3` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_g4`
--

DROP TABLE IF EXISTS `img_lookup_g4`;
CREATE TABLE `img_lookup_g4` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_r1`
--

DROP TABLE IF EXISTS `img_lookup_r1`;
CREATE TABLE `img_lookup_r1` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_r2`
--

DROP TABLE IF EXISTS `img_lookup_r2`;
CREATE TABLE `img_lookup_r2` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_r3`
--

DROP TABLE IF EXISTS `img_lookup_r3`;
CREATE TABLE `img_lookup_r3` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `img_lookup_r4`
--

DROP TABLE IF EXISTS `img_lookup_r4`;
CREATE TABLE `img_lookup_r4` (
  `image_id` bigint(20) NOT NULL,
  `source_id` int(14) NOT NULL,
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
  `image_hist_b4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_external_id` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_date` int(11) NOT NULL,
  `post_updated` int(11) NOT NULL,
  `post_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_keywords` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_score` int(11) DEFAULT NULL,
  `post_visible` tinyint(4) DEFAULT NULL,
  `post_nsfw` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_data`
--

DROP TABLE IF EXISTS `post_data`;
CREATE TABLE `post_data` (
  `pd_id` bigint(20) NOT NULL,
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
  `image_type` varchar(4) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

DROP TABLE IF EXISTS `post_images`;
CREATE TABLE `post_images` (
  `image_id` bigint(20) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sources`
--

DROP TABLE IF EXISTS `sources`;
CREATE TABLE `sources` (
  `source_id` int(11) NOT NULL,
  `source_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `source_baseurl` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `source_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `source_enabled` bit(1) NOT NULL,
  `source_subdomain` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `source_generate_report` bit(1) NOT NULL,
  `source_content_rating` smallint(1) NOT NULL,
  `source_repost_check` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracking`
--

DROP TABLE IF EXISTS `tracking`;
CREATE TABLE `tracking` (
  `tracking_event` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `tracking_date` int(11) NOT NULL,
  `tracking_data` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `user_reddit_id` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_date_created` int(11) NOT NULL,
  `user_link_karma` int(11) NOT NULL,
  `user_comment_karma` int(11) NOT NULL,
  `user_avatar` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bot_users`
--
ALTER TABLE `bot_users`
  ADD PRIMARY KEY (`bot_id`),
  ADD UNIQUE KEY `bot_name` (`bot_name`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes for table `img_lookup_b1`
--
ALTER TABLE `img_lookup_b1`
  ADD KEY `FK_img_lookup_b1_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_b1_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_b2`
--
ALTER TABLE `img_lookup_b2`
  ADD KEY `FK_img_lookup_b2_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_b2_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_b3`
--
ALTER TABLE `img_lookup_b3`
  ADD KEY `FK_img_lookup_b3_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_b3_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_b4`
--
ALTER TABLE `img_lookup_b4`
  ADD KEY `FK_img_lookup_b4_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_b4_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_g1`
--
ALTER TABLE `img_lookup_g1`
  ADD KEY `FK_img_lookup_g1_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_g1_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_g2`
--
ALTER TABLE `img_lookup_g2`
  ADD KEY `FK_img_lookup_g2_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_g2_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_g3`
--
ALTER TABLE `img_lookup_g3`
  ADD KEY `FK_img_lookup_g3_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_g3_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_g4`
--
ALTER TABLE `img_lookup_g4`
  ADD KEY `FK_img_lookup_g4_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_g4_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_r1`
--
ALTER TABLE `img_lookup_r1`
  ADD KEY `FK_img_lookup_r1_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_r1_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_r2`
--
ALTER TABLE `img_lookup_r2`
  ADD KEY `FK_img_lookup_r2_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_r2_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_r3`
--
ALTER TABLE `img_lookup_r3`
  ADD KEY `FK_img_lookup_r3_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_r3_source_id` (`source_id`);

--
-- Indexes for table `img_lookup_r4`
--
ALTER TABLE `img_lookup_r4`
  ADD KEY `FK_img_lookup_r4_image_id` (`image_id`),
  ADD KEY `FK_img_lookup_r4_source_id` (`source_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `UC_source_id_post_external_id` (`source_id`,`post_external_id`),
  ADD KEY `FK_posts_source_id` (`source_id`),
  ADD KEY `post_external_id` (`post_external_id`);

--
-- Indexes for table `post_data`
--
ALTER TABLE `post_data`
  ADD PRIMARY KEY (`pd_id`),
  ADD KEY `FK_post_data_post_id` (`post_id`),
  ADD KEY `FK_post_data_source_id` (`source_id`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD KEY `FK_post_images_post_id` (`post_id`),
  ADD KEY `FK_post_images_image_id` (`image_id`);

--
-- Indexes for table `sources`
--
ALTER TABLE `sources`
  ADD PRIMARY KEY (`source_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bot_users`
--
ALTER TABLE `bot_users`
  MODIFY `bot_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `image_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_data`
--
ALTER TABLE `post_data`
  MODIFY `pd_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sources`
--
ALTER TABLE `sources`
  MODIFY `source_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `img_lookup_b1`
--
ALTER TABLE `img_lookup_b1`
  ADD CONSTRAINT `FK_img_lookup_b1_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_b1_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_b2`
--
ALTER TABLE `img_lookup_b2`
  ADD CONSTRAINT `FK_img_lookup_b2_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_b2_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_b3`
--
ALTER TABLE `img_lookup_b3`
  ADD CONSTRAINT `FK_img_lookup_b3_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_b3_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_b4`
--
ALTER TABLE `img_lookup_b4`
  ADD CONSTRAINT `FK_img_lookup_b4_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_b4_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_g1`
--
ALTER TABLE `img_lookup_g1`
  ADD CONSTRAINT `FK_img_lookup_g1_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_g1_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_g2`
--
ALTER TABLE `img_lookup_g2`
  ADD CONSTRAINT `FK_img_lookup_g2_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_g2_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_g3`
--
ALTER TABLE `img_lookup_g3`
  ADD CONSTRAINT `FK_img_lookup_g3_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_g3_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_g4`
--
ALTER TABLE `img_lookup_g4`
  ADD CONSTRAINT `FK_img_lookup_g4_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_g4_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_r1`
--
ALTER TABLE `img_lookup_r1`
  ADD CONSTRAINT `FK_img_lookup_r1_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_r1_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_r2`
--
ALTER TABLE `img_lookup_r2`
  ADD CONSTRAINT `FK_img_lookup_r2_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_r2_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_r3`
--
ALTER TABLE `img_lookup_r3`
  ADD CONSTRAINT `FK_img_lookup_r3_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_r3_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `img_lookup_r4`
--
ALTER TABLE `img_lookup_r4`
  ADD CONSTRAINT `FK_img_lookup_r4_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_img_lookup_r4_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `FK_posts_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_data`
--
ALTER TABLE `post_data`
  ADD CONSTRAINT `FK_post_data_post_id` FOREIGN KEY (`post_id`) REFERENCES `post_images` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_post_data_source_id` FOREIGN KEY (`source_id`) REFERENCES `sources` (`source_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_images`
--
ALTER TABLE `post_images`
  ADD CONSTRAINT `FK_post_images_image_id` FOREIGN KEY (`image_id`) REFERENCES `images` (`image_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_post_images_post_id` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
