#!/opt/homebrew/bin/php
<?php

use Kinikit\CLI\Routing\Router;

include_once "../vendor/autoload.php";

Router::route($argv);




