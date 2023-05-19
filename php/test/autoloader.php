<?php

include_once __DIR__ . "/../vendor/autoload.php";
/**
 * Test autoloader - includes src one as well.
 */
spl_autoload_register(function ($class) {
    $class = str_replace("ResolverTest\\", "", $class);
    $file = DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists(__DIR__ . $file)) {
        include_once __DIR__ . $file;
        return true;
    } else if (file_exists(__DIR__ . "/../src$file")) {
        include_once __DIR__ . "/../src$file";
        return true;
    } else
        return false;
});
