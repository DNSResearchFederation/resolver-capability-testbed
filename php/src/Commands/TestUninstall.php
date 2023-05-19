<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name uninstall
 * @description Uninstall a test
 */
class TestUninstall extends BaseTestCommand {

    /**
     * @param string $testKey @argument @required The test to be uninstalled
     *
     * @return void
     */
    public function handleCommand($testKey) {

        $this->testService->deleteTest($testKey);

    }

}