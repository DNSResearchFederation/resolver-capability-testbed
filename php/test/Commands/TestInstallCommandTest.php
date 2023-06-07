<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;
use ResolverTest\TestBase;

include_once "autoloader.php";

class TestInstallCommandTest extends TestBase {

    /**
     * @var MockObject
     */
    private $testService;

    public function setUp(): void {
        parent::setUp();
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
    }

    public function testCanCreateANewTestWithDefaultTestKey() {

        $command = new TestInstallCommand($this->testService);
        $defaultKey = "test-" . date("U");

        $command->handleCommand("test", "test.com");
        $createdTest = $this->testService->getMethodCallHistory("createTest")[0][0];

        $this->assertEquals($createdTest->getKey(), $defaultKey);
        $this->assertEquals($createdTest->getDomainName(), "test.com");
        $this->assertEquals($createdTest->getType(), "test");

    }

    public function testCanCreateANewTestWithCustomTestKey() {

        $command = new TestInstallCommand($this->testService);

        $command->handleCommand("test", "test.com", null, null, null, "key");
        $createdTest = $this->testService->getMethodCallHistory("createTest")[0][0];

        $this->assertEquals($createdTest->getKey(), "key");
        $this->assertEquals($createdTest->getDomainName(), "test.com");
        $this->assertEquals($createdTest->getType(), "test");
    }

}