<?php

/*
 * Job Creator Script
 * 
 * Usage:	php creator.php <number of jobs>
 * Example: php creator.php 50
 * 
 */

echo "Creator Script Starting...\n";

require 'config.php';

$predis = new Predis\Client(array(
    'scheme' => 'tcp',
    'host'   => REDIS_HOST,
    'port'   => REDIS_PORT,
));

echo "Connected to Redis Server \n";

$jobs_to_create = $argv[1]; // Get how many jobs to create

// If not passed, set default of 10
if(!$jobs_to_create)
{
	$jobs_to_create = 10; // Default
}

echo "Creating Jobs: $jobs_to_create \n";

$messages_str = file_get_contents('randomsayings.txt');
$messages = explode("\n", $messages_str);

$messages_count = count($messages);

$count = 0;

while($count < $jobs_to_create)
{
	$count++;
	
	$job = new stdClass();
	$job->wait = rand(0, 500000);
	$job->loops = rand(2,10);
	$job->message = $messages[rand(0, $messages_count - 1)];
	
	$job_json = json_encode($job);
	
	
	// Randomly select which queue to use
	$rand = rand(0, 10);
	$queue_name = '';
	
	if($rand >= 10)
	{
		$queue_name = 'queue.priority.high';
	}
	else if($rand >= 6)
	{
		$queue_name = 'queue.priority.normal';
	}
	else
	{
		$queue_name = 'queue.priority.low';
	}
	
	$predis->rpush($queue_name, $job_json);
	echo "Job [$count] Generated. \n";
}

echo "Done!\nGoodbye...\n";
