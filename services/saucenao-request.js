'use strict';

const request = require('request');

const SAUCENAO_KEY = process.env.SVC_SAUCENAO_KEY;
const SAUCENAO_URL = `https://saucenao.com/search.php?db=999&output_type=2&testmode=1&numres=16&api_key=${SAUCENAO_KEY}&url=`;
const TIMEOUT = 60000;

module.exports = class SauceNaoRequest {
  constructor(req, res) {
    this.req = req;
    this.res = res;
    this.url = req.url.replace('/', '');
    this.done = false;
    this.timerHandle = null;
    this.events = {};
    this.startTime = false;
  }

  on(event, cb) {
    if (!this.events.hasOwnProperty(event)) {
      this.events[event] = [];
    }
    this.events[event].push(cb);
  }

  fire(event, data) {
    if (event in this.events) {
      this.events[event].forEach((cb) => {
        cb(data);
      });
    }
  }

  start() {
    this.timerHandle = setTimeout(this.timedOut.bind(this), TIMEOUT);
    this.startTime = Date.now();
    console.log(`Request started for ${decodeURIComponent(this.url)}`);
    request(`${SAUCENAO_URL}${this.url}`, (err, res, body) => {
      if (!err) {
        console.log(`Request finished successfully for ${decodeURIComponent(this.url)}`);
        this.res.write(body);
      } else {
        console.log(`Request failed for ${decodeURIComponent(this.url)}`);
      }
      this.res.end();
      this.done = true;
      clearTimeout(this.timerHandle);
      this.fire('complete');
    });
  }

  timedOut() {
    console.log(`Request timed out for ${decodeURIComponent(this.url)}`);
    this.res.end();
    this.done = true;
    this.fire('timeout');
  }
};