<?php

namespace Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\TestStopCommand;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestStopCommandTest extends TestCase {

    /**
     * @var MockObject
     */
    private $testService;

    public function setUp(): void {
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $basePath = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf $basePath/*");
    }

    public function testStopFunctionCalledByCommand() {

        $command = new TestStopCommand($this->testService);

        $command->handleCommand("testKey");

        $this->assertTrue($this->testService->methodWasCalled("stopTest", ["testKey"]));

    }

    public function testCanStopAllTests() {

        $command = new TestStopCommand($this->testService);

        $testOne = new Test("one", "type", "test.com");
        $testTwo = new Test("two", "type", "test.com");
        $testThree = new Test("three", "type", "test.com");

        $this->testService->returnValue("listTests", [$testOne,$testTwo, $testThree]);

        $command->handleCommand(null, true);
        $this->assertTrue($this->testService->methodWasCalled("stopTest", ["one"]));
        $this->assertTrue($this->testService->methodWasCalled("stopTest", ["two"]));
        $this->assertTrue($this->testService->methodWasCalled("stopTest", ["three"]));

    }
}