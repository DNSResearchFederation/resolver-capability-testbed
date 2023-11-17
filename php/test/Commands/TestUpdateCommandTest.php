<?php

namespace Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\TestUpdateCommand;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;
use TestBase;
use ResolverTest\ValueObjects\TestType\TestType;

include_once "autoloader.php";

class TestUpdateCommandTest extends TestBase {

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
        parent::setUp();
        $this->testService = Container::instance()->get(TestService::class);
        $this->objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);

        // Hook up a test manager
        $this->testManager = MockObjectProvider::instance()->getMockInstance(TestTypeManager::class);

        // Sample test type
        $testType = new TestType("testType", null, null, null);
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/testType.json", $this->objectToJSONConverter->convert($testType));
    }

    public function tearDown(): void {

        unlink(Configuration::readParameter("config.root") . "/resolvertest/testType.json");

    }

    public function testDoesUpdateExistingTest() {

        $command = new TestUpdateCommand($this->testService);
        $now = date("Y-m-d H:i:s");

        $test = new Test("ourKey", "testType", "test.com");
        $path = Configuration::readParameter("storage.root") . "/tests/ourKey.json";

        $test->save();

        // Check description is updated
        $command->handleCommand("ourKey", "this is our test object");
        $test->setDescription("this is our test object");
        $this->assertEquals($test, Test::fetch("ourKey"));

        // Check expires is updated and description remains intact
        $date = (new \DateTime())->add(new \DateInterval("P2M"));
        $command->handleCommand("ourKey", null, null, $date->format("Y-m-d H:i:s"));
        $test->setExpires($date);
        $alteredTest = Test::fetch("ourKey");
        $this->assertEquals("this is our test object", $alteredTest->getDescription());
        $this->assertEquals($date->format("Y-m-d H:i:s"), $alteredTest->getExpires()->format("Y-m-d H:i:s"));


        // Test re-updating description
        $command->handleCommand("ourKey", "alt description");
        $test->setDescription("alt description");
        $alteredTest = Test::fetch("ourKey");
        $this->assertEquals("alt description", $alteredTest->getDescription());

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