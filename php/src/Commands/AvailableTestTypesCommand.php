<?php

namespace ResolverTest\Commands;

use MathieuViossat\Util\ArrayToTextTable;

/**
 * @name available-test-types
 * @description List the available test types
 */
class AvailableTestTypesCommand {

    const TEST_TYPES = [
        "ipv6" => "tbc",
        "qname-minimisation" => "pending"
    ];

    /**
     * @var ArrayToTextTable
     */
    private $testTypesTable = null;

    /**
     * @return void
     */
    public function handleCommand() {

        if (!isset($this->testTypesTable)) {
            $this->loadTestTypesTable();
        }

        print($this->testTypesTable->getTable());

    }

    private function loadTestTypesTable() {

        $data = [];

        foreach (glob(__DIR__ . "/../Config/templates/test-type/*") as $file) {
            $newTest = [];
            $content = json_decode(file_get_contents($file), true);
            $newTest["name"] = $content["type"];
            $newTest["description"] = $content["description"];
            $data[] = $newTest;
        }

        $this->testTypesTable = new ArrayToTextTable($data);

    }

}