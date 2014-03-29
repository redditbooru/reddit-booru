var _ = require('underscore'),
    child_process = require('child_process'),
    cmd = 'php cron/cron.php',
    CRON_DELAY = 2 * 60 * 1000,
    HEALTH_DELAY = 60 * 1000,
    MAX_PROCESSES = 4,
    SOURCES_TO_CHECK = [ 1, 2, 7, 8, 9, 11, 13, 14, 15, 17, 19, 20, 21, 22, 23, 24, 25, 26 ],

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
    console.log('Script for source ' + this.sourceId + ' has hung, killing');
    this.task.kill('SIGHUP');
    this.task = null;
    this.completeCallback();
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

function taskRunner() {

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
        cronTimer = setTimeout(taskRunner, CRON_DELAY);
        console.log('Finished all sources in ' + (Date.now() - start) + ' seconds');
    }

}

taskRunner();
cronTimer = setTimeout(taskRunner, CRON_DELAY);