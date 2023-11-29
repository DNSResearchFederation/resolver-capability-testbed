<?php

namespace ResolverTest\Services\Server;

use ResolverTest\Objects\Log\BaseLog;
use ResolverTest\Objects\Server\ServerOperation;

/**
 * @implementation linux ResolverTest\Services\Server\LinuxServer
 * @implementationConfigParam server.key
 */
interface Server {

    const SERVICE_WEBSERVER = "webserver";
    const SERVICE_NAMESERVER = "nameserver";

    /**
     * Perform operations and return an array of additional information
     *
     * @param ServerOperation[] $operations
     * @return string[]
     */
    public function performOperations($operations);

    /**
     * @param string $logString
     * @param string $service
     * @return BaseLog
     */
    public function processLog($logString, $service);

}