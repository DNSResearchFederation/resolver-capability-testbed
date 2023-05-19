#!/usr/bin/php
<?php

use Kinikit\CLI\Routing\Router;

// Ensure we are in the place we expect to be
chdir(__DIR__);

include_once "../vendor/autoload.php";

Router::route($argv);