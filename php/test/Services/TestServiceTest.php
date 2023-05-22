<?php

namespace ResolverTest\Services;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Exception\InvalidTestStartDateException;
use ResolverTest\Exception\NonExistentTestException;
use ResolverTest\Exception\TestAlreadyExistsForDomainException;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestManager\TestManager;

include_once "autoloader.php";

class TestServiceTest extends TestCase {

    /**
     * @var TestService
     */
    private $testService;

    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;

    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;

    /**
     * @var MockObject
     */
    private $testManager;

    /**
     * @var MockObject
     */
    private $server;


    public function setUp(): void {
        $this->jsonToObjectConverter = Container::instance()->get(JSONToObjectConverter::class);
        $this->objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);
        $this->testService = new TestService($this->jsonToObjectConverter, $this->objectToJSONConverter);
        $path = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf $path/*");

        // Hook up a test manager
        $this->testManager = MockObjectProvider::instance()->getMockInstance(TestManager::class);
        Container::instance()->addInterfaceImplementation(TestManager::class, "test", get_class($this->testManager));
        Container::instance()->addInterfaceImplementation(TestManager::class, "testType", get_class($this->testManager));
        Container::instance()->set(get_class($this->testManager), $this->testManager);

        // Hook up a server instance
        $this->server = MockObjectProvider::instance()->getMockInstance(Server::class);
        Container::instance()->addInterfaceImplementation(Server::class, "test", get_class($this->server));
        Container::instance()->set(get_class($this->server), $this->server);

    }

    public function testCanSaveNewSimpleTest() {

        $path = Configuration::readParameter("storage.root") . "/tests/testKey.json";
        if (file_exists($path)) {
            unlink($path);
        }

        $now = date("Y-m-d H:i:s");

        $test = new Test("testKey", "test", "oxil.co.uk");
        $this->testService->createTest($test);

        $this->assertTrue(file_exists($path));
        $this->assertEquals('{"key":"testKey","type":"test","domainName":"oxil.co.uk","description":null,"starts":"' . $now . '","expires":null,"status":"Active","testData":[]}', file_get_contents($path));

    }

    public function testCanSaveComprehensiveTest() {

        $path = Configuration::readParameter("storage.root") . "/tests/testKey.json";
        if (file_exists($path)) {
            unlink($path);
        }

        $date1 = (new \DateTime())->add(new \DateInterval("P1M"))->format("Y-m-d H:i:s");
        $date2 = (new \DateTime())->add(new \DateInterval("P2M"))->format("Y-m-d H:i:s");

        $test = new Test("testKey", "testType", "oxil.co.uk", null, $date1, $date2, null, ["arg1" => "this", "arg2" => "that"]);
        $this->testService->createTest($test);

        $this->assertTrue($this->testManager->methodWasCalled("validateConfig"));

        $this->assertTrue(file_exists($path));
        $this->assertEquals('{"key":"testKey","type":"testType","domainName":"oxil.co.uk","description":null,"starts":"'.$date1.'","expires":"'.$date2.'","status":"Pending","testData":{"arg1":"this","arg2":"that"}}', file_get_contents($path));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotCreateNewTestWithExistingKey() {

        $test = new Test("testKey", "test", "test.co.uk");
        $this->testService->createTest($test);

        try {
            $anotherTest = new Test("testKey", "test", "hello.co.uk");
            $this->testService->createTest($anotherTest);
            $this->fail("Should have thrown here");
        } catch (InvalidTestKeyException $e) {
            // Great
        }
    }

    public function testCanGetTestByKey() {

        $path = Configuration::readParameter("storage.root") . "/tests/ourTest.json";

        $newTest = new Test("ourTest", "testType", "oxil.co.uk");

        if (file_exists($path)) {
            unlink($path);
        }
        file_put_contents($path, $this->objectToJSONConverter->convert($newTest));

        $retrievedTest = $this->testService->getTest("ourTest");

        $this->assertTrue(file_exists($path));
        $this->assertEquals($newTest, $retrievedTest);

    }

    public function testCanUpdateExistingTest() {

        $path = Configuration::readParameter("storage.root") . "/tests/testKey.json";
        if (file_exists($path)) {
            unlink($path);
        }

        $now = date("Y-m-d H:i:s");

        $test = new Test("testKey", "test", "oxil.co.uk");
        $this->testService->createTest($test);

        $this->assertTrue(file_exists($path));
        $this->assertEquals('{"key":"testKey","type":"test","domainName":"oxil.co.uk","description":null,"starts":"' . $now . '","expires":null,"status":"Active","testData":[]}', file_get_contents($path));

        $reTest = $this->testService->getTest("testKey");

        $reTest->setExpires("2023-08-15 00:00:00");
        $reTest->setDomainName("kinikit.com");
        $this->testService->updateTest($reTest);

        $this->assertEquals('{"key":"testKey","type":"test","domainName":"kinikit.com","description":null,"starts":"' . $now . '","expires":"2023-08-15 00:00:00","status":"Active","testData":[]}', file_get_contents($path));

    }

    public function testCanDeleteTest() {

        $path = Configuration::readParameter("storage.root") . "/tests/toBeDeleted.json";

        $test = new Test("toBeDeleted", "testType", "oxil.co.uk");

        if (file_exists($path)) {
            unlink($path);
        }
        file_put_contents($path, $this->objectToJSONConverter->convert($test));

        $this->assertTrue(file_exists($path));
        $this->testService->deleteTest("toBeDeleted");
        $this->assertFalse(file_exists($path));

    }

    public function testCanListAllTests() {

        $basePath = Configuration::readParameter("storage.root") . "/tests";

        foreach (glob($basePath . "/*") as $file) {
            unlink($file);
        }

        $test1 = new Test("test1", "testType", "domain");
        $test2 = new Test("test2", "testType", "domain");
        $test3 = new Test("test3", "testType", "domain");

        file_put_contents($basePath . "/test1.json", $this->objectToJSONConverter->convert($test1));
        file_put_contents($basePath . "/test2.json", $this->objectToJSONConverter->convert($test2));
        file_put_contents($basePath . "/test3.json", $this->objectToJSONConverter->convert($test3));

        $testList = $this->testService->listTests();

        $this->assertEquals([$test1, $test2, $test3], $testList);

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotCreateTestWhichStartsInThePast() {
        $test1 = new Test("test1", "testType", "1.co.uk", null, date("2021-01-01 10:00:00"));
        try {
            $this->testService->createTest($test1);
            $this->fail("Should have thrown here");
        } catch (InvalidTestStartDateException $e) {
            // Great
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotCreateNewTestForDomainNameWhichOverlapsAnotherTest() {

        $test1 = new Test("test1", "test", "1.co.uk");
        $this->testService->createTest($test1);

        // Confirm obvious overlap for no future time
        $test2 = new Test("test2", "testType", "1.co.uk");
        try {
            $this->testService->createTest($test2);
            $this->fail("Should have thrown here");
        } catch (TestAlreadyExistsForDomainException $e) {
            // Great
        }

        $date = new \DateTime();
        $date->add(new \DateInterval("P2M"));
        $test3 = new Test("test3", "test", "2.co.uk", null, null, $date->format("Y-m-d H:i:s"));
        $this->testService->createTest($test3);

        // Confirm one which starts before end time of same domain
        $date2 = new \DateTime();
        $date2->add(new \DateInterval("P1M"));
        $test4 = new Test("test4", "testType", "2.co.uk", null, $date2->format("Y-m-d H:i:s"));
        try {
            $this->testService->createTest($test4);
            $this->fail("Should have thrown here");
        } catch (TestAlreadyExistsForDomainException $e) {
            // Great
        }

        // Confirm one which starts after end time of same domain
        $date3 = new \DateTime();
        $date3->add(new \DateInterval("P3M"));
        $test5 = new Test("test5", "testType", "2.co.uk", null, $date3->format("Y-m-d H:i:s"));
        $this->testService->createTest($test5);


        $test6 = new Test("test6", "testType", "3.co.uk", null, $date->format("Y-m-d H:i:s"), $date3->format("Y-m-d H:i:s"));
        $this->testService->createTest($test6);

        // Non overlapping
        $test7 = new Test("test7", "testType", "3.co.uk", null, $date2->format("Y-m-d H:i:s"), $date->format("Y-m-d H:i:s"));
        $this->testService->createTest($test7);

        $test8 = new Test("test8", "testType", "3.co.uk", null, $date2->format("Y-m-d H:i:s"), $date3->format("Y-m-d H:i:s"));
        try {
            $this->testService->createTest($test8);
            $this->fail("Should have thrown here");
        } catch (TestAlreadyExistsForDomainException $e) {
            // Great
        }

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotUpdateNonExistentTest() {

        $test = new Test("key", "type", "test.com");

        try {
            $this->testService->updateTest($test);
            $this->fail("Should have thrown here");
        } catch (NonExistentTestException $e) {
            // Great
        }
    }

    public function testSynchroniseShouldBeCalledOnCreateToActivatePendingTestsWhichAreStartedAndCallInstallerForReadyTests() {

        $installOperations = [new ServerOperation(ServerOperation::OPERATION_ADD, "BINGO"), new ServerOperation(ServerOperation::OPERATION_ADD, "BONGO")];
        $this->testManager->returnValue("install", $installOperations);

        $test = new Test("test-me", "test", "hello.co.uk", "A wonderful test");
        $this->testService->createTest($test);

        $reTest = $this->testService->getTest("test-me");
        $this->assertEquals(Test::STATUS_ACTIVE, $reTest->getStatus());

        // Check the server was updated
        $this->assertTrue($this->server->methodWasCalled("performOperations", [$installOperations]));

    }

    public function testSynchroniseCallsUninstallerForActiveTestsPastExpiry() {

        $installOperations = [new ServerOperation(ServerOperation::OPERATION_REMOVE, "BINGO"), new ServerOperation(ServerOperation::OPERATION_REMOVE, "BONGO")];
        $this->testManager->returnValue("uninstall", $installOperations);

        $test = new Test("test-me", "test", "hello.co.uk", "A wonderful test");
        $this->testService->createTest($test);

        $now = date("Y-m-d H:i:s");
        $test->setExpires($now);
        $this->testService->updateTest($test);

        $reTest = $this->testService->getTest("test-me");
        $this->assertEquals(Test::STATUS_COMPLETED, $reTest->getStatus());

        // Check the server was updated
        $this->assertTrue($this->server->methodWasCalled("performOperations", [$installOperations]));

    }

}