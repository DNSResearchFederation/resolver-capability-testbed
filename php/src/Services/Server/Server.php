<?php

namespace ResolverTest\Services\Server;

use ResolverTest\Objects\Server\ServerOperation;

interface Server {

    /**
     * @param ServerOperation[] $operations
     * @return void
     */
    public function performOperations($operations);

}