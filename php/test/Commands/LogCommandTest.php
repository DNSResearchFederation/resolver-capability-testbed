<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\Logging\LoggingService;

class LogCommandTest extends TestCase {

    /**
     * @var LogCommand
     */
    private $logCommand;

    /**
     * @var LoggingService
     */
    private $loggingService;

    public function setUp(): void {
        $this->loggingService = MockObjectProvider::instance()->getMockInstance(LoggingService::class);
        $this->logCommand = new LogCommand($this->loggingService);
    }

    public function testDefaultBehaviourForLogCommand() {

        $now = new \DateTime();
        $soon = (new \DateTime())->add(new \DateInterval("PT5M"));

        $this->logCommand->handleCommand("test");
        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsByDate", ["test", $now->format("Y-m-d H:i:s"), $soon->format("Y-m-d H:i:s"), 10000, "jsonl", STDOUT]));

    }

    public function testCanWriteLogsToOutputFile() {

        $filename = Configuration::readParameter("storage.root") . "/logs/output.json";

        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->logCommand->handleCommand("testKey", null, null, null, 0, 10, null, $filename);

        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsById"));

        $args = $this->loggingService->getMethodCallHistory("generateLogsById")[0];
        $this->assertEquals(["testKey", 0, 10, 10000, null, $args[5]], $args);
    }

    public function testCanUseMaxAgeParameter() {

        $now = new \DateTime();
        $soon = (new \DateTime())->add(new \DateInterval("PT40M"));

        $this->logCommand->handleCommand("test", 40);
        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsByDate", ["test", $now->format("Y-m-d H:i:s"), $soon->format("Y-m-d H:i:s"), 10000, "jsonl", STDOUT]));

    }

    public function testLogServiceCalledCorrectlyWhenDatesSpecifiedAndOverridesMaxAge() {

        $now = date_create("15-06-2023 00:00:00");
        $soon = date_create("20-06-2023 00:00:00");

        $this->logCommand->handleCommand("test", null, "15-06-2023 00:00:00", "20-06-2023 00:00:00");
        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsByDate", ["test", $now->format("Y-m-d H:i:s"), $soon->format("Y-m-d H:i:s"), 10000, "jsonl", STDOUT]));

    }

    public function testLogsServiceCalledCorrectlyWhenIDsPassedThrough() {

        $this->logCommand->handleCommand("test", null, null, null, 25, 50, "csv");
        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsById", ["test", 25, 50, 10000, "csv", STDOUT]));

    }

}