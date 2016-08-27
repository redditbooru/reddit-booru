#RedditBooru
---

##What is it?
RedditBooru is an image board site driven by content posted to subreddits.

###Advanced Searching
Keyword search can be done and, while not as powerful as reddit's search, it's a goodly bit faster (I like to think). But, what RedditBooru provides above reddit or karmadecay is reverse image searching. This allows users to keep content reposting to a minimum. The search algorithm isn't perfect, but it works most of the time.

###Image Hosting
Content hosting through RedditBooru ensures that what's uploaded is exactly what the end users will receive, no middle-man processing, scaling, or JPEGifying in between. Additionally, the original source can be directly attributed to an image so that proper props can be given. Galleries can also be created and modified thanks to login support via reddit OAuth.

###Reddit Voting
Taking advantage of the aforementioned reddit OAuth, RedditBooru allows users to vote on images right through the site's interface. Combined with the fullscreen viewer, this makes browsing many subreddits worth of images very simple (and hopefully a nicer experience).

##Are there any new features in the works?
RedditBooru is never finished! Here are some ideas that are being worked on/in the thought bucket:

- Better support for mobile devices (and maybe native apps)
- Subreddit statistics pages
- Image/gallery view statistics for users
- Leaderboards

##I'd like to help. What can I do?
If you're here, you know the code is completely open for anybody to hack on. Found a bug or have a great idea? Open a ticket and we'll see what can happen. Want to write that code yourself? Make a pull request! Obviously, I can't guarantee that all requests will make it to the site (gotta make sure all works and that features won't bring the server to their knees), but I'm very much open to people contributing. For help setting up your development environment, checkout the next section.

## Development Setup

If you want to get RedditBooru up and running for yourself, follow these steps.

**NOTE** - As RedditBooru prepares to move towards a more modular, microservice structure, this is all highly subject to change. Currently it's a mess and it needs to be made not a mess.

### Pre-requisites

You will need the following to run RedditBooru:

- A webserver that can do URL rewriting. I personally use [nginx](https://www.nginx.com/resources/wiki/start/topics/tutorials/install/), but anything should be fine
- [PHP 5.4+](http://us3.php.net/downloads.php) or [HHVM](http://hhvm.com/). PHP7 may not work yet due to reliance on the old Memcache module
- [composer](https://getcomposer.org/) for installing PHP packages
- MySQL/[MariaDB 5.0+](https://mariadb.org/download/)
- [memcache](https://memcached.org/downloads) and the [Memcache](http://us3.php.net/manual/en/book.memcache.php) module for PHP
- [nodejs](https://nodejs.org/en/) for services and front-end build pipeline

### Initial Setup

Assuming you have all of the above:

```bash
# Get the code
git clone https://github.com/dxprog/reddit-booru.git
cd reddit-booru

# Create the backend cache directory and make it writeable
mkdir cache
chmod 777 cache/

# Install composer packages
composer install

# Install grunt for front-end building
npm install -g grunt-cli

# Install node dependencies
npm install

# Assuming you have an empty database set up, create the schema
mysql -u YOUR_USER -p -D reddit-booru < schema.sql

# Build front end files
grunt
```

### Configuration

RedditBooru can be setup to run multiple frontends with a single backend (though, some work still needed). Given that, there are two configuration files:

- `config.php`: This is the backend config which configures "global" things like database, AWS/S3 credentials, etc. Checkout `config.sample.php` for more info.
- `app-config.php`: The configuration file for a frontend, things like images URL, image storage location, etc. Checkout `app-config.sample.php` for more info.

#### Paths

You can happily run the entirety of RedditBooru from the same directory. If you want split up backend and front-end, here's what you need to know:

##### Backend

In a split configuration, none of this is accessible to the outside world.

- `api/`
- `cache/` - Writable directory for temporary image cache. This can be symlinked to the thumbnail cache on the front end.
- `config/`
- `controller/`
- `cron/`
- `lib/`
- `node_modules/` - You can copy this folder directory, or copy `package.json` and run `npm install`
- `services/`
- `vendor/` - You can copy this folder directly, or copy `composer.json` and run `composer install`
- `views/`
- `config.php`

##### Frontend
- `static/`
- `app-config.php`
- `index.php`
- `upload.php` - Deprecated. To be removed soon

### Adding a Source

Sadly, this is a very manual process currently, requiring direct entry into the database. It's not difficult, just tedious. Here's what you need to know about the fields:

- `source_name` - The name of the subreddit (including the r/). Ex: `r/pics`
- `source_baseurl` - **(DEPRECATED)** Full URL path to the subreddt. Ex: `http://www.reddit.com/r/pics`
- `source_type` - Type of source. The only currently supported option is `subreddit`. This will probably be deprecated and removed in the future.
- `source_enabled` - Whether this source is enabled. `1` for enabled, `0` for disabled. Disabled sources will not be displayed nor processed.
- `source_subdomain` **(OPTIONAL)** The subdomain where this can be directly accessed. Ex: `pics` -> pics.redditbooru.com
- `source_generate_report` - Whether the stats bot will generate a monthly report for this subreddit. `1` for enabled, `0` for disabled.
- `source_content_rating` - **(DEPRECATED)** Not used anymore and will be removed in the future.
- `source_repost_check` - Whether to have the bot check for reposts and report/comment on them. `0` is disabled, any other number is the number of seconds in which reposting is not allowed. Ex: `86400` -> don't allow reposts for one day.

### Getting Content

Content is inserted in one of two ways:

#### `cron/cron.php`

To manually update content, run the following from the backend directory:

```bash
php cron/cron.php --source=SOURCE_ID
```

This will scan that source and update/insert the latest 100 items. This can be run as a cron job for continual updates.

#### Service

The set-it-and-forget-it node script to continually updates all enabled sources. Using either `screen` (or some other detached-like terminal) or something like [forever](https://github.com/foreverjs/forever), run:

```bash
node services/task-runner.js
```

This service does currently have a hard coded URL to the API that returns all sources. Locate `http://redditbooru.com/sources/` in `task-runner.js` and replace with the appropriate path. **THIS IS GOING TO BE REPLACED WITH [EXTERNAL SERVICE NODES](https://github.com/dxprog/redditbooru-node) VERY SOON!**

### Additional Services

There are some additional services rolled up into the main RedditBooru codebase. It's all very hacked together, so please excuse the mess.

#### SauceNao

The SauceNao service is a node script that manages requests for getting image sources via SauceNao without going over API limits. To get it running:

```bash
# SauceNao API key
export SVC_SAUCENAO_KEY = SAUCENAO_API_KEY

# Port to run the service on. SAUCENAO_PORT in config.php should be this same value. Someday, it'll use the env var too.
export SVC_SAUCENAO_PORT = 4444

# Run it. Use screen/forever/init.d script to persist
node services/saucenao-service.js
```

#### `cron/clean.php` (DEPRECATED)

Used to remove old cached files. It's recommended that you use `find` on a cron job instead.

#### ai-tan (DEPRECATED)

"ai-tan" provides a couple of features on two different scripts:

- `cron/repost_check.php` - checks new posts to see if they're reposts (as dictated by the source's `source_repost_check`)
- `cron/ai-tan.php` - generates and posts montly report of a source's activity (as dictated by the source's `source_generate_report`)

These scripts rely on an old [PHP based reddit framework](https://github.com/dxprog/RedditBots) of mine that's terrible and outdated. Setting this up is non-trivial and won't be covered here as they are actively being replaced by better, stand-alone modules.