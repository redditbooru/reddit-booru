# RedditBooru API Endpoints

**Sources**
----
  Returns a list of all enabled subreddit sources.

* ***URL:*** `sources/`

  Note: the trailing '/' is required

* ***Method:***
  
  GET

* ***Success Response:***
  
  * ***Code:*** 200 <br />
  *  ***Content:*** `[{ "name" : "r\/awwnime", "title":"awwnime","value":"1","checked":true }, ...`
 
* ***Error Response:***

  The only error that should be returned is a 404 if the url was malformed, in which case it will return the default webpage

* ***Sample Call:***

  `curl http://redditbooru.com/sources/`




**Images**
----
  Returns images. If this is POSTed to with an image file, it will honor all of these settings while doing a reverse image search on the file supplied.

* ***URL:*** `images/`

* ***Method:***
  
  `GET, POST`

*  ***URL Params***

   ***Optional:***

 
 *  `imageUri` -- URL to an image; returns visually similar results. _Default: empty_
   
 *  `sources` -- a comma delimited list of sources to search through. Empty searches all sources. _Default: empty_
   
 *  `limit` -- The max number of images to return. _Default: 30_

 *  `afterId` -- For paging; only fetches results after the image ID (currently buggy with albums). _Default: null_

 *  `postId` -- Returns all images for a specific post ID. _Default: null_

 *  `exteranlId` -- Returns all images for a specific reddit post ID. _Default: null_

 *  `afterDate` -- Return only images posted after a specific unix timestamp. _Default: null_

 *  `user` -- Return images posted by a specific user. _Default: null_

 *  `q` -- Return images that match a keywords search query. _Default: null_

 *  `ignoreSource` -- Ignore the default or provided sources. *DEPRECATED*

 *  `ignoreUser` -- Ignore the provided user. *DEPRECATED*

 *  `honorVisible` -- Don't show images that aren't marked visible. `True` by default. *DEPRECATED*

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

  `curl http://redditbooru.com/api/images/?sources=1&limit=5&user=chiefnoah&q=alice`

  
