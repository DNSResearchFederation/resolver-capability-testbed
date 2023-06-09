<?php

namespace ResolverTest\Objects\Log;

/**
 * @table nameserver_log
 * @generate
 */
class NameserverLog extends BaseLog {
    /**
     * @var string
     */
    private $ipAddress;
}