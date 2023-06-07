<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseTestCommand;

/**
 * @name update
 * @description Update properties of a test
 */
class TestUpdateCommand extends BaseTestCommand {

    /**
     * @param string $testKey @argument @required The identifier key of the test
     * @param string $description @option The description of the test
     * @param string $starts @option The start time for the test
     * @param string $expires @option The end time for the test
     *
     * @return void
     */
    public function handleCommand($testKey, $description = null, $starts = null, $expires = null) {

        $test = $this->testService->getTest($testKey);

        if ($description) {
            $test->setDescription($description);
        }
        if ($starts) {
            $starts = date_create_from_format("Y-m-d H:i:s", $starts);
            $test->setStarts($starts);
        }
        if ($expires) {
            $expires = date_create_from_format("Y-m-d H:i:s", $expires);
            $test->setExpires($expires);
        }

        $this->testService->updateTest($test);

    }

}