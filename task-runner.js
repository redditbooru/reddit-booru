var child_process = require('child_process'),
    cmd = 'php cron_reddit-booru.php',
    CRON_DELAY = 5 * 60 * 1000,
    HEALTH_DELAY = 30 * 1000,

    cronTimer = null,
    healthTimer = null,
    task = null,

    start = 0;

// I expect the PHP script to reasonably spit out data roughly every 30s
// If we get nothing, kill the task
function healthUpdate() {
    console.log('Script has hung, killing');
    task.kill('SIGHUP');
    task = null;
    clearTimeout(cronTimer);
    cronTimer = setTimeout(taskRunner, CRON_DELAY);
}

function taskRunner() {
    
    // If the task hasn't finished but is still healthy, let it run
    if (null === task) {
        console.log('Spawning cron task...');
        start = (new Date()).getTime();

        // Clean up any already running timers
        clearTimeout(healthTimer);
        clearTimeout(cronTimer);

        // Spawn the PHP script
        task = child_process.spawn('php', [ 'cron_reddit-booru.php' ]);
        task.stdout.on('data', function(chunk) {
            console.log(chunk.toString());
            clearTimeout(healthTimer);
            healthTimer = setTimeout(healthUpdate, HEALTH_DELAY);
        });

        // Clean up when the process is done
        task.on('exit', function() {
            var time = (new Date()).getTime();
            clearTimeout(healthTimer);
            console.log('Task finished in ' + ((time - start) / 1000) + ' seconds.');
            task = null;
            cronTimer = setTimeout(taskRunner, CRON_DELAY);
        });

        healthTimer = setTimeout(healthUpdate, HEALTH_DELAY);
        
    } else {
        console.log('Task is still busy');
    }

}

taskRunner();
cronTimer = setTimeout(taskRunner, CRON_DELAY);