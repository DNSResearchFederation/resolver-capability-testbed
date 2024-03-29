<?php

$args = [];
$numericalArgs = 0;
for ($i = 1; $i < sizeof($argv); $i++) {
    if (str_starts_with($argv[$i], "-")) {
        $args[substr($argv[$i], 1)] = $argv[++$i];
    } else {
        $args[$numericalArgs++] = $argv[$i];
    }
}


if ($args["K"] ?? null && $args["d"] ?? null) {
    unset($argv[0]);
    unset($argv[1]);
    unset($argv[2]);
    unset($argv[3]);
    unset($argv[4]);
    $keyDir = $args["K"];
    file_put_contents($args[0] . ".signed", join(" ", $argv));

    if (!file_exists($keyDir))
        mkdir($keyDir, 0777, true);
    file_put_contents($keyDir . "/dsset-" . $args["o"], $args["o"] . ".\tIN\tDS\t9875\t13\t1\t4E8B6264E81D803A693BE18E50291097707E2056");
}

