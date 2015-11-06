<?php

/**
 * dxprog.com PHP library
 */

// Used to keep track of page generation time
$_begin = microtime (true);
require_once('app-config.php');
chdir(CORE_LOCATION);
// Include base libraries
require_once('./lib/aal.php');

// Start the session
Lib\Session::start();

// Define our globals
$GLOBALS['_content'] = null;
$GLOBALS['_sidebars'] = null;

// Handle URL and templating things
$found = Lib\Url::Rewrite('config/rewrites.json');
$GLOBALS['_baseURI'] = current(explode('?', Lib\Url::getRawUrl()));
Lib\Display::setTheme('.');
Lib\Display::setLayout('main');

// Warm up the test buck stuff
Lib\TestBucket::initialize();

// Handle URL rewrites
if (!$found) {

	// If the rewriter couldn't come up with anything,
	header('HTTP/1.1 404 Content Not Found');
	Lib\Display::showError(404, 'Sorry, but we couldn\'t find what you were looking for.');

} else {

	// Check to see which page must be included
	$_page = isset($_GET['page']) ? $_GET['page'] : 'redditbooru';

	// Make sure that there exists a page for the request
	if (!is_readable('./controller/' . $_page . '.php')) {
		header('HTTP/1.1 404 Content Not Found');
		Lib\Display::showError(404, 'Sorry, but we couldn\'t find what you were looking for.');
	} else {

		// Include the config file if it exists
		if (file_exists('./config/config.' . $_page . '.php')) {
			include('./config/config.' . $_page . '.php');
		}

		// Turn control over to the requested page
		call_user_func(array('Controller\\' . $_page, 'render'));

	}

}

// Render the page to output
Lib\Display::render();

// Calculate the amount of time it took to generate the page
$genTime = microtime (true) - $GLOBALS['_begin'];
echo "<!--\n\tGenerated in ", $genTime, " seconds.\n\tDB hits - ", Lib\Db::$callCount, ".\n\tMax memory used - ", memory_get_peak_usage(), "\n-->";

Api\DxApi::clean();
