<?php

/*
 * Worker That will Process "Jobs"
 */

$worker_id = $argv[1];

if(!$worker_id)
{
	$worker_id = rand(100, 999);
}

echo "Worker [$worker_id] Starting...\n";

require 'config.php';

echo "Connected to Redis Server \n";

$predis = new Predis\Client(array(
    'scheme' => 'tcp',
    'host'   => REDIS_HOST,
    'port'   => REDIS_PORT,
));

// Setting the Worker's Status
$predis->hset('worker.status', $worker_id, 'Started');
$predis->hset('worker.status.last_time', $worker_id, time());

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

echo "	Waiting for a Job .";

// Continue looping as long as we don't go past the time limit
while(time() < $start_time + $time_limit)
{
	// Setting the Worker's Status
	$predis->hset('worker.status', $worker_id, 'Waiting');
	$predis->hset('worker.status.last_time', $worker_id, time());
	
	// Check to see if there are any items in the queues in
	// order of priority. If all are empty, wait up to 10 
	// seconds for something to be added to the queue.
	$job = $predis->blpop('queue.priority.high'
						, 'queue.priority.normal'
						, 'queue.priority.low'
						, 10);
	
	// If a job was pulled out
	if($job)
	{
		/*
		 * Start Working
		 * 
		 * This is where you will use the data from the 
		 */
		echo "\nJob Started ";
		// Setting the Worker's Status
		$predis->hset('worker.status', $worker_id, 'Working');
		$predis->hset('worker.status.last_time', $worker_id, time());
		
		$queue_name = $job[0]; // 0 is the name of the queue
		$details = json_decode($job[1]); // parse the json data from the job
		
		/* Progress Dots Logic
		 * 
		 * To make our application look cooler, we will print out a "."
		 * for every time we wait. The final result is will look as such:
		 * 
		 * Job Started .... Done!
		 */
		$dot_counter =0;
		$dot_limit = $details->loops;
		while($dot_counter < $dot_limit)
		{
			usleep($details->wait);
			echo ".";
			$dot_counter++;
		}
		echo " Done!\n	Message from $queue_name: $details->message \n";
		echo "	Waiting for a Job .";
	}
	else
	{
		/* Waiting Dots Logic
		 * 
		 * 
		 */
		echo ".";
	}
}

// Setting the Worker's Status
$predis->hset('worker.status', $worker_id, 'Closed');
$predis->hset('worker.status.last_time', $worker_id, time());

echo "\n\nWorker [$worker_id] Finished! \nGoodbye...\n";