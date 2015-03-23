# RedditBooru API Endpoints

**Sources**
----
  Returns all the available subreddits that are aggregated.

* ***URL***

  /sources/

  Note: the trailing '/' is required

* ***Method:***
  
  GET

* **Success Response:***
  
  <_What should the status code be on success and is there any returned data? This is useful when people need to to know what their callbacks should expect!_>

  * ***Code:** 200 <br />
    ***Content:** `[{ "name" : "r\/awwnime", "title":"awwnime","value":"1","checked":true }, ...`
 
* ***Error Response:***

  The only error that should be returned is a 404 if the url was malformed, in which case it will return the default webpage

* **Sample Call:***

  `curl http://redditbooru.com/sources/`




**Images (Search)**
----
  Returns images. If this is POSTed to with an image file, it will honor all of these settings while doing a reverse image search on the file supplied.

* ***/images/***

* ***Method:***
  
  `GET, POST`

*  ***URL Params***

   ***Optional:***

 
   `imageUri=<image url>` -- URL to an image; returns visually similar results. Default: empty
   
   `sources=<sources,>` -- ID (value) of the subreddits to return images from separated by a comma. Empty searches all sources _Default: empty_
   
   `limit=<limit>` -- Max number of images to return. _Default: 30_

   `afterId=<postID>` -- Only returns images that have an ID less (older) than the provided postID. _Default: null_

   `postId=<postID>` -- Returns the image that corresponds to the post ID. _Default: null_

   `exteranlId=<externalId>` -- Returns the image that corresponds to the external ID. _Default: null_

   `afterDate=<date>` -- Returns images created after the date. Formatted in unix epoch time. _Default: null_

   `user=<reddit username>` -- Returns images posted by this reddit username. _Default: null_

   `q=<query>` -- Keywords to query for separated by spaces. 1210197

   `ignoreSource=<true|false>` -- Ignore the default (or provided) sources to query images from. *DEPRECATED*

   `ignoreUser=<true|false>` -- Ignore the default or provided user. *DEPRECATED*

   `honorVisible=<true|false>` -- Don't show images that aren't marked visible. `True` by default *DEPRECATED*

* ***Success Response:***

  * ***Code:*** 200 <br />
    ***Content:*** _Varies with query._ Will most likely be an array of image objects
 
* ***Error Response:***

  * ***Code:*** 500 INTERNAL SERVER Error <br />
    ***Content:*** _none_

    OR

  * ***Code:*** 404 CONTENT NOT FOUND <br />
    ***Content:*** _none_

* ***Sample Call:***

  `curl http://redditbooru.com/api/images?sources=1&limit=5&user=chiefnoah&q=alice`

  
