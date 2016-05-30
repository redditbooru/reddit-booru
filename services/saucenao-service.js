const http = require('http');

const SauceNaoRequest = require('./saucenao-request');

const SERVER_PORT = process.env.SVC_SAUCENAO_PORT;

var requests = [];

// Technically, SauceNAO's free limit is 20 requests per 30
// seconds, but I want a little bit of leeway.
var MAX_REQUESTS = 10;
var TIME_SPAN = 30000;

function manageQueue() {
  // Get all uncompleted requests and then filter out the ones
  // that haven't yet started.
  const uncompletedRequests = requests.filter((request) => !request.done);
  var activeRequests = 0;
  uncompletedRequests.forEach((request) => {
    activeRequests += !request.startTime ? 0 : 1;
  });
  const waitingRequests = uncompletedRequests.filter((request) => !request.startTime);

  if (waitingRequests.length > 0 && activeRequests < MAX_REQUESTS) {
    while (activeRequests < MAX_REQUESTS && !!waitingRequests.length) {
      const request = waitingRequests.shift();
      request.start();
      activeRequests++;
    }
  }

  // The old requests array gets wiped away with our new active only requests
  requests = uncompletedRequests;
}

var server = http.createServer((req, res) => {
  const request = new SauceNaoRequest(req, res);
  request.on('complete', manageQueue);
  request.on('timeout', manageQueue);
  requests.push(request);
  manageQueue();
});

server.listen(SERVER_PORT);
console.log(`Listening on port ${SERVER_PORT}`);