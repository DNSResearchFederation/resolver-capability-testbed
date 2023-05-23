<?php

namespace ResolverTest\Services\TestManager;

use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;


/**
 * @implementation ipv6 ResolverTest\Services\TestManager\IPv6TestManager
 */
interface TestManager {

    /**
     * @param Test $test
     * @return ServerOperation[]
     */
    public function install($test);

    /**
     * @param Test $test
     * @return ServerOperation[]
     */
    public function uninstall($test);

    /**
     * @param Test $test
     */
    public function validateConfig($test);

}