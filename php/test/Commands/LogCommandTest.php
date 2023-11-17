<?php

namespace Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\LogCommand;
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

        $from = (new \DateTime())->sub(new \DateInterval("PT5M"));
        $to = date_create("now");

        $this->logCommand->handleCommand("test");

        $args = $this->loggingService->getMethodCallHistory("generateLogsByDate")[0];
        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsByDate", ["test", $from->format("Y-m-d H:i:s"), $to->format("Y-m-d H:i:s"), 10000, "jsonl", $args[5]]));

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

        $from = (new \DateTime())->sub(new \DateInterval("PT40M"));
        $to = date_create("now");

        $this->logCommand->handleCommand("test", 40);

        $args = $this->loggingService->getMethodCallHistory("generateLogsByDate")[0];
        $this->assertTrue($this->loggingService->methodWasCalled("generateLogsByDate", ["test", $from->format("Y-m-d H:i:s"), $to->format("Y-m-d H:i:s"), 10000, "jsonl", $args[5]]));

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