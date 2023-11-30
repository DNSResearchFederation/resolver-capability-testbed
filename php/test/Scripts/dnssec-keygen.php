<?php


$args = [];
$numericalArgs = 0;
for ($i = 1; $i < sizeof($argv); $i++) {
    if (str_starts_with($argv[$i], "-")) {
        if ($argv[$i] == "-3") $args["3"] = 1;
        else
            $args[substr($argv[$i], 1)] = $argv[++$i];
    } else {
        $args[$numericalArgs++] = $argv[$i];
    }
}


if ($args["K"] ?? null) {
    $outputDir = $args["K"];
    unset($argv[1]);
    unset($argv[2]);

    $line = $args[0] . ". 3600 IN DNSKEY " . join(" ", array_slice($argv, 1));
    file_put_contents($outputDir . "/" . $args[0] . "-" . ($args["f"] ?? "ZSK") . ".key", $line);
}

file_put_contents($outputDir . "/dsset-" . $args[0] . ".", "EXAMPLE-DS-RECORDS-" . $args[0]);
