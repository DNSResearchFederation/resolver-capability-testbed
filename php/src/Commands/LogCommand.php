<?php

namespace ResolverTest\Commands;

/**
 * @name log
 * @description Retrieve the logs
 */
class LogCommand {

    /**
     * @param int $maxAge @option The maximum age in minutes for the returned logs
     * @param string $fromDate @option A start date for the returned logs
     * @param string $toDate @option An end date for the returned logs
     * @param int $fromId @option A start sequential id for the returned logs
     * @param int $toId @option An end sequential id for the returned logs
     * @param string $format @option The format of the returned kogs. Can be json, jsonl or csv
     * @param string $file @option The filepath for where to write the logs
     * @param int $resultLimit @option The maximum number of log entries to be returned
     *
     * @return void
     */
    public function handleCommand($maxAge = null, $fromDate = null, $toDate = null, $fromId = null, $toId = null, $format = null, $file = null, $resultLimit = null) {

    }

}