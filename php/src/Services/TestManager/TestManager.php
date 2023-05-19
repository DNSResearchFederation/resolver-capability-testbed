<?php

namespace ResolverTest\Services\TestManager;

use ResolverTest\Objects\Test\Test;

interface TestManager {

    /**
     * @param Test $test
     * @return mixed
     */
    public function install($test);

    /**
     * @param Test $test
     * @return mixed
     */
    public function uninstall($test);

}