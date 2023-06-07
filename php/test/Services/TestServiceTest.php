<?php

namespace ResolverTest\Services;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Exception\InvalidTestStartDateException;
use ResolverTest\Exception\NonExistentTestException;
use ResolverTest\Exception\TestAlreadyExistsForDomainException;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestType\TestTypeManager;
use ResolverTest\ValueObjects\TestType\TestType;
use ResolverTest\TestBase;

include_once "autoloader.php";

class TestServiceTest extends TestBase {

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
    private $testTypeManager;

    /**
     * @var MockObject
     */
    private $server;


    public function setUp(): void {
        parent::setUp();

        $this->jsonToObjectConverter = Container::instance()->get(JSONToObjectConverter::class);
        $this->objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);

        // Hook up a test manager
        $this->testTypeManager = MockObjectProvider::instance()->getMockInstance(TestTypeManager::class);
        $testType = Container::instance()->get(TestType::class);
        $testType->setType("test");
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/test.json", $this->objectToJSONConverter->convert($testType));
        $testType->setType("testType");
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/testType.json", $this->objectToJSONConverter->convert($testType));
        Container::instance()->set(get_class($this->testTypeManager), $this->testTypeManager);

        // Hook up a server instance
        $this->server = MockObjectProvider::instance()->getMockInstance(Server::class);
        Container::instance()->addInterfaceImplementation(Server::class, "test", get_class($this->server));
        Container::instance()->set(get_class($this->server), $this->server);

        $globalConfig = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);
        $globalConfig->returnValue("isValid", true);

        $this->testService = new TestService($this->jsonToObjectConverter, $this->objectToJSONConverter, $this->testTypeManager, $globalConfig);

    }

    public function tearDown(): void {

        unlink(Configuration::readParameter("config.root") . "/resolvertest/test.json");
        unlink(Configuration::readParameter("config.root") . "/resolvertest/testType.json");

    }

    public function testCanSaveNewSimpleTest() {

        $test = new Test("testKey", "test", "oxil.co.uk");
        $this->testService->createTest($test);

        $test->setStatus(Test::STATUS_ACTIVE);
        $this->assertEquals($test, Test::fetch("testKey"));

    }

    public function testCanSaveComprehensiveTest() {

        $path = Configuration::readParameter("storage.root") . "/tests/testKey.json";
        if (file_exists($path)) {
            unlink($path);
        }

        $date1 = (new \DateTime())->add(new \DateInterval("P1M"));
        $date2 = (new \DateTime())->add(new \DateInterval("P2M"));

        $test = new Test("testKey", "testType", "oxil.co.uk", null, $date1, $date2, null, ["arg1" => "this", "arg2" => "that"]);
        $this->testService->createTest($test);

        $this->assertEquals($test, Test::fetch("testKey"));}

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotCreateNewTestWithExistingKey() {

        $test = new Test("testKey", "test", "test.co.uk");
        $test->save();

        try {
            $anotherTest = new Test("testKey", "test", "hello.co.uk");
            $this->testService->createTest($anotherTest);
            $this->fail("Should have thrown here");
        } catch (InvalidTestKeyException $e) {
            // Great
        }
    }

    public function testCanGetTestByKey() {

        $newTest = new Test("ourTest", "testType", "oxil.co.uk");
        $newTest->save();

        $retrievedTest = $this->testService->getTest("ourTest");

        $this->assertEquals($newTest, $retrievedTest);

    }

    public function testCanUpdateExistingTest() {

        $test = new Test("testKey", "test", "oxil.co.uk");
        $test->save();

        $this->assertEquals($test, Test::fetch("testKey"));

        $reTest = $this->testService->getTest("testKey");

        $reTest->setExpires((new \DateTime())->add(new \DateInterval("P2M")));
        $reTest->setDomainName("kinikit.com");
        $this->testService->updateTest($reTest);

        $this->assertEquals($reTest, Test::fetch("testKey"));

    }

    public function testCanDeleteTest() {

        $test = new Test("toBeDeleted", "testType", "oxil.co.uk");
        $test->save();

        $this->assertEquals($test, Test::fetch("toBeDeleted"));
        $this->testService->deleteTest("toBeDeleted");
        try {
            Test::fetch("toBeDeleted");
            $this->fail();
        } catch (ObjectNotFoundException $e) {
            // Great
        }

    }

    public function testCanListAllTests() {

        $test1 = new Test("test1", "testType", "domain");
        $test2 = new Test("test2", "testType", "domain");
        $test3 = new Test("test3", "testType", "domain");

        $test1->save();
        $test2->save();
        $test3->save();

        $testList = $this->testService->listTests();

        $this->assertEquals([$test1, $test2, $test3], $testList);

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCannotCreateTestWhichStartsInThePast() {
        $test1 = new Test("test1", "testType", "1.co.uk", null, (new \DateTime())->sub(new \DateInterval("P2M")));
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
        $test1->save();

        // Confirm obvious overlap for no future time
        $test2 = new Test("test2", "testType", "1.co.uk");
        try {
            $this->testService->createTest($test2);
            $this->fail("Should have thrown here");
        } catch (TestAlreadyExistsForDomainException $e) {
            // Great
        }

        $date = (new \DateTime())->add(new \DateInterval("P2M"));
        $test3 = new Test("test3", "test", "2.co.uk", null, null, $date);
        $this->testService->createTest($test3);

        // Confirm one which starts before end time of same domain
        $date2 = (new \DateTime())->add(new \DateInterval("P1M"));

        $test4 = new Test("test4", "testType", "2.co.uk", null, $date2);
        try {
            $this->testService->createTest($test4);
            $this->fail("Should have thrown here");
        } catch (TestAlreadyExistsForDomainException $e) {
            // Great
        }

        // Confirm one which starts after end time of same domain
        $date3 = (new \DateTime())->add(new \DateInterval("P3M"));
        $test5 = new Test("test5", "testType", "2.co.uk", null, $date3);
        $this->testService->createTest($test5);


        $test6 = new Test("test6", "testType", "3.co.uk", null, $date, $date3);
        $this->testService->createTest($test6);

        // Non overlapping
        $test7 = new Test("test7", "testType", "3.co.uk", null, $date2, $date);
        $this->testService->createTest($test7);

        $test8 = new Test("test8", "testType", "3.co.uk", null, $date2, $date3);
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

        // Check the server was updated
        $installOperations = [new ServerOperation(ServerOperation::OPERATION_ADD, "BINGO"), new ServerOperation(ServerOperation::OPERATION_ADD, "BONGO")];
        $this->testTypeManager->returnValue("getInstallServerOperations", $installOperations);

        $test = new Test("test-me", "test", "hello.co.uk", "A wonderful test");
        $this->testService->createTest($test);

        $reTest = $this->testService->getTest("test-me");
        $this->assertEquals(Test::STATUS_ACTIVE, $reTest->getStatus());

        // Check the server was updated
        $this->assertTrue($this->server->methodWasCalled("performOperations", [$installOperations]));

    }

    public function testSynchroniseCallsUninstallerForActiveTestsPastExpiry() {

        $uninstallOperations = [new ServerOperation(ServerOperation::OPERATION_ADD, "BINGO"), new ServerOperation(ServerOperation::OPERATION_ADD, "BONGO")];
        $this->testTypeManager->returnValue("getUninstallServerOperations", $uninstallOperations);

        $test = new Test("test-me", "test", "hello.co.uk", "A wonderful test");
        $this->testService->createTest($test);

        $now = new \DateTime();
        $test->setExpires($now);
        $this->testService->updateTest($test);

        $reTest = $this->testService->getTest("test-me");
        $this->assertEquals(Test::STATUS_COMPLETED, $reTest->getStatus());

        // Check the server was updated
        $this->assertTrue($this->server->methodWasCalled("performOperations", [$uninstallOperations]));

    }

    public function testDoesStartTestCorrectlyIfTypeIsPending() {

        // Pending test, hit start and check status and start time
        $now = new \DateTime();
        $future = (new \DateTime())->add(new \DateInterval("P2M"));
        $test = new Test("key", "testType", "test.co.uk", null, $future, null, Test::STATUS_PENDING);
        $test->save();

        $this->testService->startTest("key");
        $alteredTest = $this->testService->getTest("key");

        $this->assertEquals($now->format("Y-m-d H:i:s"), $alteredTest->getStarts()->format("Y-m-d H:i:s"));
        $this->assertEquals(Test::STATUS_ACTIVE, $alteredTest->getStatus());

        // Completed test, check nothing happened
        $past1 = (new \DateTime())->sub(new \DateInterval("P1M"));
        $past2 = (new \DateTime())->sub(new \DateInterval("P2M"));
        $test = new Test("key", "testType", "test.co.uk", null, $past2, $past1, Test::STATUS_COMPLETED);

        $test->save();

        $this->testService->startTest("key");
        $alteredTest = $this->testService->getTest("key");

        $this->assertEquals($test, $alteredTest);

    }

    public function testDoesStopTestCorrectlyIfTypeIsActive() {

        // Active test, hit stop and check status and expires time
        $now = new \DateTime();
        $past = (new \DateTime())->sub(new \DateInterval("P1M"));
        $test = new Test("key1", "testType", "test.co.uk", null, $past, null, Test::STATUS_ACTIVE);

        $test->save();

        $this->testService->stopTest("key1");
        $alteredTest = $this->testService->getTest("key1");

        $this->assertEquals($now->format("Y-m-d H:i:s"), $alteredTest->getExpires()->format("Y-m-d H:i:s"));
        $this->assertEquals(Test::STATUS_COMPLETED, $alteredTest->getStatus());

        // Non-active test, check nothing happens to it
        $future = (new \DateTime())->add(new \DateInterval("P2M"));
        $test = new Test("key2", "testType", "test.co.uk", null, $future, null, Test::STATUS_PENDING);
        $this->testService->createTest($test);

        $this->testService->stopTest("key2");
        $alteredTest = $this->testService->getTest("key2");

        $this->assertEquals($test, $alteredTest);

    }

}