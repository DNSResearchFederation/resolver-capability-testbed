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
     * @param string $nameserverSet @option The set of nameservers to use. Defaults to 'default'
     * @param string[] $testArgs @argument Any additional test specific arguments
     *
     * @return void
     */
    public function handleCommand($test, $domain, $description = null, $starts = null, $expires = null, $testKey = null, $nameserverSet = "default", ...$testArgs) {

        $testKey = $testKey ?? $test . "-" . date("U");

        if ($starts) {
            $starts = date_create($starts);
        }
        if ($expires) {
            $expires = date_create("Y-m-d H:i:s", $expires);
        }

        $testObject = new Test($testKey, $test, $domain, $description, $starts, $expires, null, $nameserverSet, $testArgs);
        $test = $this->testService->createTest($testObject);

        if ($test && $test->getAdditionalInformation()) {
            print_r("\n\nAdditional Test Info\n\n" . join("\n\n", $test->getAdditionalInformation()));
        }

    }

}