<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name update
 * @description Update properties of a test
 */
class TestUpdate extends BaseTestCommand {

    /**
     * @param string $testKey @argument @required The identifier key of the test
     * @param string $description @option The description of the test
     * @param string $starts @option The start time for the test
     * @param string $expiry @option The end time for the test
     *
     * @return void
     */
    public function handleCommand($testKey, $description = null, $starts = null, $expiry = null) {

        $test = $this->testService->getTest($testKey);

        if ($description) {
            $test->setDescription($description);
        }
        if ($starts) {
            $test->setStarts($starts);
        }
        if ($expiry) {
            $test->setExpires($expiry);
        }

        $this->testService->updateTest($test);

    }

}