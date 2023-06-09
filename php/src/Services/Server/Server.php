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
     * @param ServerOperation[] $operations
     * @return void
     */
    public function performOperations($operations);

    /**
     * @param string $logString
     * @param string $service
     * @return BaseLog
     */
    public function processLog($logString, $service);

}