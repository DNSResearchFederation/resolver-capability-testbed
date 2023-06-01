<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;

include_once "autoloader.php";

class TestUpdateCommandTest extends TestCase {

    /**
     * @var TestService
     */
    private $testService;

    /**
     * @var MockObject
     */
    private $testManager;

    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;

    /**
     * @var string
     */
    private $basePath;

    public function setUp(): void {
        $this->testService = Container::instance()->get(TestService::class);
        $this->objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);
        $this->basePath = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf {$this->basePath}/*");

        // Hook up a test manager
        $this->testManager = MockObjectProvider::instance()->getMockInstance(TestTypeManager::class);
        Container::instance()->addInterfaceImplementation(TestTypeManager::class, "testType", get_class($this->testManager));
    }

    public function testDoesUpdateExistingTest() {

        $command = new TestUpdateCommand($this->testService);
        $now = date("Y-m-d H:i:s");

        $test = new Test("ourKey", "testType", "test.com");
        $path = Configuration::readParameter("storage.root") . "/tests/ourKey.json";

        file_put_contents($path, $this->objectToJSONConverter->convert($test));

        // Check description is updated
        $command->handleCommand("ourKey", "this is our test object");
        $this->assertEquals("{\"key\":\"ourKey\",\"type\":\"testType\",\"domainName\":\"test.com\",\"description\":\"this is our test object\",\"starts\":\"$now\",\"expires\":null,\"status\":null,\"testData\":[]}", file_get_contents($path));

        // Check expires is updated and description remains intact
        $date = (new \DateTime())->add(new \DateInterval("P2M"))->format("Y-m-d H:i:s");
        $command->handleCommand("ourKey", null,null, $date);
        $this->assertEquals("{\"key\":\"ourKey\",\"type\":\"testType\",\"domainName\":\"test.com\",\"description\":\"this is our test object\",\"starts\":\"$now\",\"expires\":\"$date\",\"status\":null,\"testData\":[]}", file_get_contents($path));

        // Test re-updating description
        $command->handleCommand("ourKey", "alt description");
        $this->assertEquals("{\"key\":\"ourKey\",\"type\":\"testType\",\"domainName\":\"test.com\",\"description\":\"alt description\",\"starts\":\"$now\",\"expires\":\"$date\",\"status\":null,\"testData\":[]}", file_get_contents($path));

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotUpdateNonExistentTest() {

        $command = new TestUpdateCommand($this->testService);

        try {
            $command->handleCommand("blah blah blah", "blah");
            $this->fail();
        } catch (InvalidTestKeyException $e) {
            // Great
        }
    }

}