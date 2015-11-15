var http = require('http');
var https = require('https');

var activeRequests = [];
var requestMaxId = 0;
var requestsInTimeSpan = 0;
var canMakeRequest = true;
var timerHandle = null;

// Technically, SauceNAO's paid limit is 12 requests per 30
// seconds, but I want a little bit of leeway.
var MAX_REQUESTS = 10;
var TIME_SPAN = 30000;
var QUEUE_INTERVAL = 1000;
var HEALTH_CHECK_INTERVAL = 100;
var SAUCENAO_KEY = process.env.SVC_SAUCENAO_KEY;
var SAUCENAO_URL = 'https://saucenao.com/search.php?db=999&output_type=2&testmode=1&numres=16&url={{URL}}&api_key=' + SAUCENAO_KEY;

/**
 * Keeps track of how many in-flight requests are within the
 * SauceNAO allowed time span. When that number is exceeded,
 * it disables any additional requests until the queue
 * clears up
 *
 * @method manageQueue
 */
function manageQueue() {
  var minTime = Date.now() - TIME_SPAN;
  var request;
  var requestsInSpan = 0;

  if (activeRequests.length) {
    for (var i = 0; i < activeRequests.length; i++) {
      request = activeRequests[i];
      if (request.endTime) {

        // Overly pessimistic: a request isn't considered "done" until
        // it's end time has passed outside of the window
        if (request.endTime < minTime) {
          console.log('Request ' + request.id + ' has fallen outside of time span. Removing.');
          activeRequests.splice(i, 1);
          i--;
        } else {
          requestsInSpan++;
        }
      } else {
        // If this request is outside of the timeout period, kill it
        if (request.startTime < minTime) {
          console.log('Request ' + request.id + ' has hung. Giving the kill signal.');
          request.kill = true;
        } else {
          requestsInSpan++;
        }
      }
    }
  }

  canMakeRequest = requestsInSpan <= MAX_REQUESTS;
  if (activeRequests.length > 0) {
    timerHandle = setTimeout(manageQueue, QUEUE_INTERVAL);
  } else {
    timerHandle = null;
  }

  console.log(activeRequests.length + ' requests in the queue. Ability to make requests is ' + (canMakeRequest ? 'open' : 'closed'));

}

var server = http.createServer(function(req, res) {

  var url = req.url.replace('/', '');
  var data = '';
  var request = {
    id: ++requestMaxId
  };

  /**
   * Ensures that a request can be made before firing. If
   * not, checks back after a period of time to try again.
   *
   * @method tryToRequest
   */
  function tryToRequest() {
    if (canMakeRequest) {
      fireRequest();
    } else {
      setTimeout(tryToRequest, 25);
    }
  }

  /**
   * Fires off the SauceNAO request
   *
   * @method fireRequest
   */
  function fireRequest() {
    request.startTime = Date.now();
    activeRequests.push(request);
    console.log('Making request at ' + request.startTime + '. Position in queue is ' + activeRequests.length);
    https.get(SAUCENAO_URL.replace('{{URL}}', url), handleResponse);
  }

  /**
   * The request response handler
   *
   * @method handleResponse
   */
  function handleResponse(res) {
    res.on('data', function(chunk) {
      data += chunk.toString();
    });
    res.on('end', responseComplete);
    res.on('error', closeOut);
    setTimeout(healthCheck, HEALTH_CHECK_INTERVAL);
  }

  /**
   * The request queue manager can demand a request kill itself.
   * Generally this is because we've waited too long for a final
   * response from the server. This check looks for that flag
   *
   * @method healthCheck
   */
  function healthCheck() {
    if (request.kill) {
      console.log('Request ' + request.id + ' recieved the kill signal.');
      req.abort();
      data = null;
      closeOut();
    }
    setTimeout(healthCheck, HEALTH_CHECK_INTERVAL);
  }

  /**
   * Response complete handler
   *
   * @method responseComplete
   */
  function responseComplete() {
    console.log('Response for request ' + request.id + ' is complete.');
    try {
      data = JSON.parse(data);
    } catch (e) {
      data = null;
    } finally {
      closeOut();
    }
  }

  /**
   * Writes whatever data needs to be written and
   * performs steps for cleanup
   */
  function closeOut() {
    console.log('Closing out request ' + request.id);
    if (typeof data === 'object') {
      res.write(JSON.stringify(data));
    }
    res.end();

    request.endTime = Date.now();
    if (!timerHandle) {
      timerHandle = setTimeout(manageQueue, QUEUE_INTERVAL);
    }
  }

  console.log('Incoming request ' + request.id);
  tryToRequest();

});

server.listen(process.env.SVC_SAUCENAO_PORT);
timerHandle = setTimeout(manageQueue, QUEUE_INTERVAL);