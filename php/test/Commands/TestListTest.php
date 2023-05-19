<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use MathieuViossat\Util\ArrayToTextTable;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestListTest extends TestCase {

    /**
     * @var MockObject
     */
    private $testService;

    public function setUp(): void {
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
    }

    public function testCommandDoesOutputListOfTestsCorrectly() {

        $command = new TestList($this->testService);
        $this->testService->returnValue("listTests", [
            new Test("test1", "type", "1.co.uk", null, "2022-05-01 10:30:20", "2023-07-01 11:00:00", "ACTIVE"),
            new Test("test2", "type", "2.co.uk", null, "2023-05-01 10:30:20", null, "ACTIVE"),
            new Test("test3", "type", "3.co.uk", null, "2022-07-01 10:30:20", null, "ACTIVE"),
        ]);

        ob_start();
        $command->handleCommand();
        $output = ob_get_contents();
        ob_end_clean();


        $this->assertEquals((new ArrayToTextTable([
            [
                "Key" => "test1",
                "Type" => "type",
                "Domain Name" => "1.co.uk",
                "Status" => "ACTIVE",
                "Start Time" => "2022-05-01 10:30:20",
                "End Time" => "2023-07-01 11:00:00"
            ], [
                "Key" => "test2",
                "Type" => "type",
                "Domain Name" => "2.co.uk",
                "Status" => "ACTIVE",
                "Start Time" => "2023-05-01 10:30:20",
                "End Time" => "Never"
            ], [
                "Key" => "test3",
                "Type" => "type",
                "Domain Name" => "3.co.uk",
                "Status" => "ACTIVE",
                "Start Time" => "2022-07-01 10:30:20",
                "End Time" => "Never"
            ]
        ]))->getTable(), $output);

    }

    public function testOutputForWhenNoTestsExist() {

        $command = new TestList($this->testService);

        $this->testService->returnValue("listTests", []);

        ob_start();
        $command->handleCommand();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("No tests exist\n", $output);
    }

}