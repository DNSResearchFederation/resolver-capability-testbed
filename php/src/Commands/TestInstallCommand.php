<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;
use ResolverTest\Objects\Test\Test;

/**
 * @name install
 * @description Install a test
 */
class TestInstallCommand extends BaseTestCommand {

    /**
     * @param string $test @argument @required The test to be installed for the session
     * @param string $domain @argument @required The domain name to use for the test
     * @param string $description @option The description of the test
     * @param string $starts @option Start time for the test
     * @param string $expires @option End time for the test
     * @param string $testKey @option The identifying key for the test
     * @param string[] $testArgs @argument Any additional test specific arguments
     *
     * @return void
     */
    public function handleCommand($test, $domain, $description = null, $starts = null, $expires = null, $testKey = null, ...$testArgs) {

        $testKey = $testKey ?? $test . "-" . date("U");

        $testObject = new Test($testKey, $test, $domain, $description, $starts, $expires, null, $testArgs);
        $this->testService->createTest($testObject);

    }

}