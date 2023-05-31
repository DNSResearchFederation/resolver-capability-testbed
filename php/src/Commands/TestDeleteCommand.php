<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name delete
 * @description Delete a test and remove all relevant files
 */
class TestDeleteCommand extends BaseTestCommand {

    /**
     * @param string $testKey @argument The test to be deleted
     * @param bool $all @option Boolean to delete all tests
     *
     * @return void
     */
    public function handleCommand($testKey = null, $all = false) {

        // Are you sure?

        if ($all) {
            foreach ($this->testService->listTests() as $test) {
                $this->testService->deleteTest($test->getKey());
            }
        } else if ($testKey) {
            $this->testService->deleteTest($testKey);
        }
    }

}