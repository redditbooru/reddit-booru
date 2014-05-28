/**
 * Updates denormalized post data from source of truth tables
 */
DROP PROCEDURE IF EXISTS `proc_UpdateDenormalizedPostData`;

DELIMITER //

CREATE PROCEDURE `proc_UpdateDenormalizedPostData`
    (IN postId INT)
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

END //