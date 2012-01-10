<?php

/*
 * Snippits of code used in the post. This PHP file is not intended to run,
 * but be a simple resource for those wishing to copy and past some of the
 * code snippits used in the post.
 */

/*
 * Connecting to Redis
 */

const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;

$predis = new Predis\Client(array(
    'scheme' => 'tcp',
    'host'   => REDIS_HOST,
    'port'   => REDIS_PORT,
));


/*
 * Adding items to the queue
 */

$job = new stdClass();
$job->id = 1;
$job->report = 'general';

// Add the job to the high priority queue
$predis->rpush('queue.priority.high', json_encode($job));

// Or, you could add it to the normal or low priority queue.
$predis->rpush('queue.priority.normal', json_encode($job));
$predis->rpush('queue.priority.low', json_encode($job));

/*
 * Simple Continuous While Loop
 */

// Always True
while(1)
{
	/* ... perform tasks here ...  */
}

/*
 * Checking the Queue
 */
$job = $predis->blpop('queue.priority.high'
						, 'queue.priority.normal'
						, 'queue.priority.low'
						, 10);

/*
 * Checking to see if a Job was returned
 */

if($job)
{
	// Index 0 of the array holds which queue was returned
	$queue_name = $job[0];
	// Index 1 of the array holds the string value of the job.
	// Since we are passing it JSON, we'll decode it:
	$details = json_decode($job[1]);
	
	/* ... do job work ... */	
}

/* 
 * A Smarter While Statement
 */

// Set the time limit for php to 0 seconds
set_time_limit(0);

/*
 * We'll set our base time, which is one hour (in seconds).
 * Once we have our base time, we'll add anywhere between 0 
 * to 10 minutes randomly, so all workers won't quick at the
 * same time.
 */
$time_limit = 60 * 60 * 1; // Minimum of 1 hour
$time_limit += rand(0, 60 * 10); // Adding additional time

// Set the start time
$start_time = time();

// Continue looping as long as we don't go past the time limit
while(time() < $start_time + $time_limit)
{
	/* ... perorm BLPOP command ... */
	/* ... process jobs when received ... */
}

/* ... will quit once the time limit has been reached ... */

/*
 * Assigning Worker IDs & Monitoring
 * 
 * Usage: php worker.php 1
 */

// Gets the worker ID from the command line argument
$worker_id = $argv[1];

// Setting the Worker's Status
$predis->hset('worker.status', $worker_id, 'Started');

// Set the last time this worker checked in, use this to 
// help determine when scripts die
$predis->hset('worker.status.last_time', $worker_id, time());


/* 
 * Using Versions to Check for Reloads
 */

$version = $predis->get('worker.version'); // i.e. number: 6

while(time() < $start_time + $time_limit)
{
	/* ... check for jobs and process them ... */
	
	/* ... then, at the very end of the while ... */
	if($predis->get('worker.version') != $version)
	{
		echo "New Version Detected... \n";
		echo "Reloading... \n";
		exit();
	}
}