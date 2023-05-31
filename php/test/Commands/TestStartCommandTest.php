<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestStartCommandTest extends TestCase {

    /**
     * @var MockObject
     */
    private $testService;

    public function setUp(): void {
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $basePath = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf $basePath/*");
    }

    public function testStartFunctionCalledByCommand() {

        $command = new TestStartCommand($this->testService);

        $command->handleCommand("testKey");

        $this->assertTrue($this->testService->methodWasCalled("startTest", ["testKey"]));

    }

    public function testCanStartAllTests() {

        $command = new TestStartCommand($this->testService);

        $testOne = new Test("one", "type", "test.com");
        $testTwo = new Test("two", "type", "test.com");
        $testThree = new Test("three", "type", "test.com");

        $this->testService->returnValue("listTests", [$testOne,$testTwo, $testThree]);

        $command->handleCommand(null, true);
        $this->assertTrue($this->testService->methodWasCalled("startTest", ["one"]));
        $this->assertTrue($this->testService->methodWasCalled("startTest", ["two"]));
        $this->assertTrue($this->testService->methodWasCalled("startTest", ["three"]));

    }
}