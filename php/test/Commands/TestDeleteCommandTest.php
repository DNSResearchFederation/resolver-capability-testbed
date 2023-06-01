<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestDeleteCommandTest extends TestCase {

    /**
     * @var MockObject
     */
    private $testService;

    /**
     * @var string
     */
    private $basePath;

    public function setUp(): void {
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $this->basePath = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf {$this->basePath}/*");
    }

    public function testCanRemoveTestWhenCommandExecuted() {

        $command = new TestDeleteCommand($this->testService);

        $command->handleCommand("someKey");

        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["someKey"]));
    }

    public function testCanDeleteAllTests() {

        $command = new TestDeleteCommand($this->testService);

        $testOne = new Test("one", "type", "test.com");
        $testTwo = new Test("two", "type", "test.com");
        $testThree = new Test("three", "type", "test.com");

        $this->testService->returnValue("listTests", [$testOne,$testTwo, $testThree]);

        $command->handleCommand(null, true);
        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["one"]));
        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["two"]));
        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["three"]));

    }

}