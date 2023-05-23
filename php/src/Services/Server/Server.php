<?php

namespace ResolverTest\Services\Server;

use ResolverTest\Objects\Server\ServerOperation;

/**
 * @implementation linux ResolverTest\Services\Server\LinuxServer
 */
interface Server {

    /**
     * @param ServerOperation[] $operations
     * @return void
     */
    public function performOperations($operations);

}