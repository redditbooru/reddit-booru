/**
 * Updates denormalized post data from source of truth tables
 */
DROP PROCEDURE IF EXISTS `proc_UpdatePostDataSortHot`;

DELIMITER //

CREATE PROCEDURE `proc_UpdatePostDataSortHot` ()
BEGIN

    DECLARE sourcesDone BOOLEAN DEFAULT FALSE;
    DECLARE minDate INT DEFAULT UNIX_TIMESTAMP();
    DECLARE sourceId INT;

    DECLARE sources CURSOR FOR
        SELECT
            `source_id`
        FROM
            `sources`
        WHERE
            `source_enabled` = 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET sourcesDone = TRUE;

    OPEN sources;

    WHILE sourcesDone = FALSE DO

        FETCH sources INTO sourceId;

        BEGIN

            DECLARE postsDone BOOLEAN DEFAULT FALSE;
            DECLARE posts CURSOR FOR
                SELECT
                    `post_id`,
                    `post_score`,
                    `post_date`
                FROM
                    `post_data`
                WHERE
                    `source_id` = sourceId;

            DECLARE CONTINUE HANDLER FOR NOT FOUND SET postsDone = TRUE;

            OPEN posts;

            WHILE postsDone = FALSE DO

                BEGIN

                    DECLARE ordering,
                            sign,
                            seconds,
                            postId,
                            postScore,
                            postDate INT;

                    FETCH posts INTO postId, postScore, postDate;

                    SET ordering = LOG10(GREATEST(ABS(postScore), 1));
                    SET seconds = postDate - 1134028003;

                    CASE
                        WHEN postScore > 1 THEN
                            SET sign = 1;
                        WHEN @postScore < 0 THEN
                            SET sign = -1;
                        ELSE
                            SET sign = 0;
                    END CASE;

                    UPDATE
                        `post_data`
                    SET
                        `sort_hot` = ROUND(sign * ordering + seconds / 45000, 7)
                    WHERE
                        `post_id` = postId;

                END;

            END WHILE;

        END;

    END WHILE;

    CLOSE sources;

END //