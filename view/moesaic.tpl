<!DOCTYPE html>
<html>
    <head>
        <title>{TITLE}</title>
        <style type="text/css">
            body { background:url(/view/reddit-booru/images/cream_pixels.png); padding:0; margin:0; }
            #content { margin:0 auto; position:relative; }
            a img { border:0; }
            img { position:absolute; background:url(/view/reddit-booru/images/ajax.gif) no-repeat center center; }
            img.small { width:100px; height:100px; }
            img.medium { width:200px; height:200px; }
            img.large { width:400px; height:400px; }
            #form { padding:20px; border:1px solid #ddd; margin:100px auto; width:450px; background:#fff; }
            #form label { font:bold 24px sans-serif; text-align:center; display:block; }
            #form input { padding:5px; width:100%; font:24px sans-serif; margin:5px 0 15px; box-sizing:border-box; -moz-box-sizing:border-box; }
            #form button { width:150px; height:40px; margin:0 auto; }
        </style>
    </head>
    <body>
        <div id="content"></div>
        <div id="form">
            <form action="#" method="get">
                <label for="user">Enter your username</h1>
                <input type="text" name="user" id="user" />
                <button type="submit">Make it!</button>
            </form>
        </div>
        <script type="text/javascript" src="//code.jquery.com/jquery-1.10.0.min.js"></script>
        <script type="text/javascript" src="http://underscorejs.org/underscore-min.js"></script>
        <script type="text/javascript">
            var cache = null,
                user = '{USER}',
                avg = 0,
                max = 0,
                min = 1000,
                out = [],
                diff = 0,
                $window = $(window),
                $content = $('#content'),
                winWidth = 0,
                contentWidth = 0,
                colCount = 0,
                rows = [],
                rowCount = 0,

                createRow = function() {
                    rows.push(new Array(colCount));
                    rowCount++;
                },

                reserveSpot = function(row, col, size) {
                    var x = col,
                        y = row,
                        xMax = x + size,
                        yMax = y + size;

                    for (; y < yMax; y++) {
                        if (y >= rowCount) {
                            createRow();
                        }
                        for (x = col; x < xMax; x++) {
                            rows[y][x] = -1;
                        }
                    }
                },

                checkForIntersect = function(row, col, size) {
                    var x = col,
                        y = row,
                        xMax = x + size,
                        yMax = y + size < rowCount ? y + size : rowCount;

                    for (; y < yMax; y++) {
                        for (x = col; x < xMax; x++) {
                            if (typeof rows[y][x] === 'object' || rows[y][x] === -1) {
                                return true;
                            }
                        }
                    }
                    return false;
                },

                placeItem = function(item) {
                    var dimension = item.size === 'small' ? 1 : item.size === 'medium' ? 2 : 4,
                        i = 0,
                        j = 0,
                        jCount = colCount - dimension + 1;

                    for (; i < rowCount; i++) {
                        for (j = 0; j < jCount; j++) {
                            if (!rows[i][j] && !checkForIntersect(i, j, dimension)) {
                                reserveSpot(i, j, dimension);
                                rows[i][j] = item;
                                return;
                            }
                        }
                    }

                    // If we've made it here, we're full up. Add a new row and try again
                    createRow();
                    placeItem(item);

                },

                generateMosaic = function(data) {
                    
                    winWidth = $window.width();
                    // Subtract one column to avoid horizontal scrollies
                    contentWidth = Math.floor(winWidth / 400) * 400 - 100;
                    colCount = contentWidth / 100;

                    rows = [];
                    createRow();

                    _.each(data, function(item) {
                        max = item.score > max ? item.score : max;
                        min = item.score < min ? item.score : min;
                        avg += item.score;
                    });

                    avg /= data.length;
                    diff = max - min;

                    _.each(data, function(item) {
                        item.perc = Math.round((item.score - min) / diff * 10);
                        item.size = item.perc < 3 ? 'small' : item.perc < 7 ? 'medium' : 'large';
                        placeItem(item);
                    });

                    for (var i = 0; i < rowCount; i++) {
                        for (var j = 0; j < colCount; j++) {
                            if (typeof rows[i][j] === 'object') {
                                var item = rows[i][j],
                                    size = item.size === 'small' ? 100 : item.size === 'medium' ? 200 : 400,
                                    src = item.thumb + '_' + size + '_' + size + '.jpg';

                                out.push('<a href="' + item.cdnUrl + '" target="_blank"><img class="' + item.size + '" src="/cache/' + src + '" style="left:' + (j * 100) + 'px; top:' + (i * 100) + 'px;" /></a>');
                            }
                        }
                    }

                    $content
                        .css('width', contentWidth + 'px')
                        .html(out);

                },

                generateForUser = function(user) {
                    $.ajax({
                        url:'/images/?user=' + user + '&limit=500',
                        dataType:'json',
                        success:generateMosaic
                    });
                };

                if (user) {
                    $('#form').remove();
                    generateForUser(user);
                } else {
                    $('form').on('submit', function(e) {
                        if ($('input').val().length > 0) {
                            window.location.href = '/' + $('input').val();
                        }
                        e.preventDefault();
                        return false;
                    });
                }

        </script>
        <script type="text/javascript">
            var _gaq=_gaq||[];_gaq.push(["_setAccount","UA-280226-8"]);_gaq.push(["_trackPageview"]);(function(){var a=document.createElement("script");a.type="text/javascript";a.async=!0;a.src=("https:"==document.location.protocol?"https://ssl":"http://www")+".google-analytics.com/ga.js";var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b)})();
        </script>
    </body>
</html>