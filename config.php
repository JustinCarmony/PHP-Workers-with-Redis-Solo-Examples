<?php

/*
 * Config file used for connecting to Redis
 */

// Load Predis
require 'predis/lib/Predis/Autoloader.php';

Predis\Autoloader::register();

const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;