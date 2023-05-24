<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestDeleteTest extends TestCase {

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

        $command = new TestDelete($this->testService);

        $command->handleCommand("someKey");

        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["someKey"]));
    }

}