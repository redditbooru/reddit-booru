/**
 * Updates denormalized post data from source of truth tables
 */
DROP PROCEDURE IF EXISTS `proc_GetLinkedPosts`;

DELIMITER //

CREATE PROCEDURE `proc_GetLinkedPosts`
    (IN postId INT)
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

END //