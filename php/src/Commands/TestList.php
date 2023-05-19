<?php

namespace ResolverTest\Commands;

use MathieuViossat\Util\ArrayToTextTable;
use ResolverTest\Framework\BaseTestCommand;

/**
 * @name list
 * @description List the active tests for a given session
 */
class TestList extends BaseTestCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        $data = [];

        foreach ($this->testService->listTests() as $test) {
            $newItem["Key"] = $test->getKey();
            $newItem["Type"] = $test->getType();
            $newItem["Domain Name"] = $test->getDomainName();
            $newItem["Status"] = $test->getStatus() ;
            $newItem["Start Time"] = $test->getStarts();
            $newItem["End Time"] = $test->getExpires() ?? "Never";

            $data[] = $newItem;
        }

        if ($data == []) {
            print("No tests exist\n");
        } else {
            $renderer = new ArrayToTextTable($data);
            print($renderer->getTable());
        }

    }

}