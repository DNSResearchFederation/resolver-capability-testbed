<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestStartTest extends TestCase {

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

        $command = new TestStart($this->testService);

        $command->handleCommand("testKey");

        $this->assertTrue($this->testService->methodWasCalled("startTest", ["testKey"]));

    }
}