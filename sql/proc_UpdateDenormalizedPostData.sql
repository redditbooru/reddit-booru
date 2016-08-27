/**
 * Updates denormalized post data from source of truth tables
 */
DROP PROCEDURE IF EXISTS `proc_UpdateDenormalizedPostData`;

DELIMITER //

CREATE PROCEDURE `proc_UpdateDenormalizedPostData`
    (IN postId INT)
BEGIN

    DECLARE isInPostData INT;
    SELECT
        COUNT(1) INTO isInPostData
    FROM
        `post_data`
    WHERE
        `post_id` = postId;

    IF isInPostData = 0 THEN
        /* If it doesn't exist, create the records */
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
        /* Otherwise, just update */
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

END //