<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestInstallTest extends TestCase {

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

    public function testCanCreateANewTestWithDefaultTestKey() {

        $command = new TestInstall($this->testService);
        $defaultKey = "test-" . date("U");

        $command->handleCommand("test", "test.com");
        $this->assertTrue($this->testService->methodWasCalled("createTest", [new Test($defaultKey, "test", "test.com")]));


    }

    public function testCanCreateANewTestWithCustomTestKey() {

        $command = new TestInstall($this->testService);

        $command->handleCommand("test", "test.com", null, null, null, "key");
        $this->assertTrue($this->testService->methodWasCalled("createTest", [new Test("key", "test", "test.com")]));
    }

}