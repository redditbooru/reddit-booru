var _ = require('underscore'),
    child_process = require('child_process'),
    http = require('http'),
    cmd = 'php cron/cron.php',
    CRON_DELAY = 2 * 60 * 1000,
    HEALTH_DELAY = 60 * 1000,
    MAX_PROCESSES = 4,
    SOURCES_TO_CHECK = [],

    cronTimer = null,
    healthTimer = null,
    start = null,
    currentSource = 0,
    processes = [],

    Process = function(sourceId, completeCallback) {
        this.sourceId = sourceId;
        this.completeCallback = completeCallback;
        this.task = this.startTask();
    };

Process.prototype.startTask = function() {
    var self = this,
        task = null;

    console.log('Spawning cron task for source ' + self.sourceId + '...');
    self._start = Date.now();

    // Spawn the PHP script
    task = child_process.spawn('php', [ 'cron/cron.php', '--source=' + self.sourceId ]);
    task.stdout.on('data', function(chunk) {
        // console.log(self.sourceId + ' - ' + chunk.toString());
        clearTimeout(self.healthTimer);
        self.healthTimer = setTimeout(_.bind(self.healthUpdate, self), HEALTH_DELAY);
    });

    // Clean up when the process is done
    task.on('exit', function() {
        clearTimeout(self.healthTimer);
        console.log('Task for source ' + self.sourceId + ' finished in ' + ((Date.now() - self._start) / 1000) + ' seconds.');
        self.task = null;
        self.completeCallback();
    });

    self.healthTimer = setTimeout(_.bind(self.healthUpdate, self), HEALTH_DELAY);

    return task;
};

// I expect the PHP script to reasonably spit out data roughly every 30s
// If we get nothing, kill the task
Process.prototype.healthUpdate = function() {
    if (null !== this.task) {
        console.log('Script for source ' + this.sourceId + ' has hung, killing');
        this.task.kill('SIGHUP');
        this.task = null;
        this.completeCallback();
    }
};

function taskComplete() {

    var i = 0;

    // Find this task in the processes and remove it
    for (; i < MAX_PROCESSES; i++) {
        if (processes[i] == this) {
            processes.splice(i, 1);
            break;
        }
    }

    // Reset the cron timer and run the next task
    clearTimeout(cronTimer);
    taskRunner();

}

function getActiveSources(callback) {
    http.get('http://redditbooru.com/sources/', function(res) {
        var json = '';
        res.on('data', function(data) {
            json += data;
        }).on('end', function() {
            try {
                var sources = JSON.parse(json),
                    i = 0,
                    count = sources instanceof Array ? sources.length : 0;
                SOURCES_TO_CHECK = [];
                for (; i < count; i++) {
                    SOURCES_TO_CHECK.push(parseInt(sources[i].value, 10));
                }
                callback();
            } catch (exc) {
                console.log('Invalid JSON sources data, working off of old data');
                callback();
            }
        });
    }).on('error', function() {
        console.log('Error retrieving sources');
        clearTimeout(cronTimer);
        cronTimer = setTimeout(taskRunner, CRON_DELAY);
    });
}

function taskRunner() {

    getActiveSources(function() {

        var i = currentSource,
            count = SOURCES_TO_CHECK.length;

        if (i === 0) {
            start = Date.now();
        }

        for (; i < count; i++) {
            if (processes.length < MAX_PROCESSES) {
                processes.push(new Process(SOURCES_TO_CHECK[i], taskComplete));
            } else {
                break;
            }
        }

        // Save our spot and wrap if we're at the end of the whole list
        currentSource = i;
        if (i >= count && processes.length === 0) {
            currentSource = 0;
            clearTimeout(cronTimer);
            cronTimer = setTimeout(taskRunner, CRON_DELAY);
            console.log('Finished all sources in ' + ((Date.now() - start) / 1000) + ' seconds');
        }
    });

}

taskRunner();
cronTimer = setTimeout(taskRunner, CRON_DELAY);