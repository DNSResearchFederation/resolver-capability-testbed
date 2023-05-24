<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestDeleteAllTest extends TestCase {

    /**
     * @var MockObject
     */
    private $testService;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;

    public function setUp(): void {
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $this->objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);
        $this->basePath = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf {$this->basePath}/*");
    }

    public function testStopFunctionCalledByCommand() {

        $command = new TestDeleteAll($this->testService);

        $testOne = new Test("one", "type", "test.com");
        $testTwo = new Test("two", "type", "test.com");
        $testThree = new Test("three", "type", "test.com");

        $this->testService->returnValue("listTests", [$testOne,$testTwo, $testThree]);

        $command->handleCommand();
        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["one"]));
        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["two"]));
        $this->assertTrue($this->testService->methodWasCalled("deleteTest", ["three"]));

    }

}