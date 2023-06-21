<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseLogCommand;

/**
 * @name log
 * @description Retrieve the logs
 */
class LogCommand extends BaseLogCommand {

    /**
     * @param string $key @argument @required The key of the test for which to generate logs
     * @param int $maxAge @option The maximum age in minutes for the returned logs
     * @param string $fromDate @option A start date for the returned logs
     * @param string $toDate @option An end date for the returned logs
     * @param int $fromId @option A start sequential id for the returned logs
     * @param int $toId @option An end sequential id for the returned logs
     * @param string $format @option The format of the returned logs. Can be json, jsonl or csv
     * @param string $file @option The filepath for where to write the logs
     * @param int $resultLimit @option The maximum number of log entries to be returned
     *
     * @return void
     */
    public function handleCommand($key, $maxAge = 5, $fromDate = null, $toDate = null, $fromId = null, $toId = null, $format = "jsonl", $file = STDOUT, $resultLimit = 10000) {

        // Get Logs
        if ($toId || $fromId) {
            $logs = $this->loggingService->getLogsById($key, $fromId, $toId, $resultLimit, $format);
        } else {
            $fromDate = $fromDate ? date_create($fromDate) : date_create("now");
            $toDate = $toDate ? date_create($toDate) : (new \DateTime())->add(new \DateInterval("PT{$maxAge}M"));
            $logs = $this->loggingService->getLogsByDate($key, $fromDate->format("Y-m-d H:i:s"), $toDate->format("Y-m-d H:i:s"), $resultLimit, $format);
        }


        // Output them
        if ($file != STDOUT) {
            file_put_contents($file, $logs);
        } else {
            fputs(STDOUT, $logs ?? "");
        }

    }

}