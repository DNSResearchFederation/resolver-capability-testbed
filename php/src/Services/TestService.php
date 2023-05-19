<?php

namespace ResolverTest\Services;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Exception\InvalidTestStartDateException;
use ResolverTest\Exception\NonExistentTestException;
use ResolverTest\Exception\TestAlreadyExistsForDomainException;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestManager\TestManager;

class TestService {

    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;

    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;

    /**
     * @param JSONToObjectConverter $jsonToObjectConverter
     * @param ObjectToJSONConverter $objectToJSONConverter
     */
    public function __construct($jsonToObjectConverter, $objectToJSONConverter) {
        $this->jsonToObjectConverter = $jsonToObjectConverter;
        $this->objectToJSONConverter = $objectToJSONConverter;
    }

    /**
     * @return Test[]
     */
    public function listTests() {

        $tests = [];

        foreach (glob(Configuration::readParameter("storage.root") . "/tests/*") as $file) {
            $test = $this->jsonToObjectConverter->convert(file_get_contents($file), Test::class);
            $tests[] = $test;
        }

        return $tests;
    }


    /**
     * Get a test by key
     *
     * @param $key
     * @return Test
     */
    public function getTest($key) {
        $path = Configuration::readParameter("storage.root") . "/tests/$key.json";
        if (file_exists($path)) {
            return $this->jsonToObjectConverter->convert(file_get_contents($path), Test::class);
        } else {
            throw new InvalidTestKeyException($key);
        }
    }


    /**
     * @param $test Test
     * @return void
     */
    public function createTest($test) {

        $test->validate();

        // Ensure start is not in the past
        if ($test->getStarts() < date("Y-m-d H:i:s")) {
            throw new InvalidTestStartDateException();
        }

        $path = Configuration::readParameter("storage.root") . "/tests/{$test->getKey()}.json";

        // Check key is unique
        if (file_exists($path)) {
            throw new InvalidTestKeyException($test->getKey());
        }

        // Ensure start is not in the past
        if ($test->getStarts() < date("Y-m-d H:i:s")) {
            throw new InvalidTestStartDateException();
        }

        // Ensure no overlap with existing test on domain
        foreach ($this->listTests() as $existingTest) {
            if ($test->getDomainName() != $existingTest->getDomainName()) {
                continue;
            }

            if (!$existingTest->getExpires()) {
                throw new TestAlreadyExistsForDomainException($test->getDomainName());
            }

            if ((($test->getStarts() > $existingTest->getStarts()) && ($test->getStarts() < $existingTest->getExpires())) ||    // New test starts within existing test
                (($test->getExpires() < $existingTest->getStarts()) && ($test->getExpires() > $existingTest->getExpires())) ||  // New test ends within existing test
                (($test->getStarts() <= $existingTest->getStarts()) && ($test->getExpires() >= $existingTest->getExpires()))) { // New test surrounds existing test
                throw new TestAlreadyExistsForDomainException($test->getDomainName());
            }
        }

        $test->setStatus(Test::STATUS_PENDING);

        $testJSON = $this->objectToJSONConverter->convert($test);
        file_put_contents($path, $testJSON);

        // Synchronise tests to start this test if required
        $this->synchroniseTests();
    }

    /**
     * @param Test $test
     * @return void
     */
    public function updateTest($test) {

        $key = $test->getKey();
        $path = Configuration::readParameter("storage.root") . "/tests/$key.json";
        if (!file_exists($path)) {
            throw new NonExistentTestException($key);
        }

        $test->validate();
        $testJSON = $this->objectToJSONConverter->convert($test);
        file_put_contents($path, $testJSON);

        $this->synchroniseTests();
    }

    /**
     * @param $key string
     * @return void
     */
    public function deleteTest($key) {

        $path = Configuration::readParameter("storage.root") . "/tests/$key.json";
        if (file_exists($path)) {
            unlink($path);
        } else {
            throw new NonExistentTestException($key);
        }
    }

    public function synchroniseTests() {

        // Update status according to start/end
        foreach (glob(Configuration::readParameter("storage.root") . "/tests/*") as $file) {
            /**
             * @var Test $test
             */
            $test = $this->jsonToObjectConverter->convert(file_get_contents($file), Test::class);

            if ($test->getStatus() == Test::STATUS_PENDING && $test->getStarts() <= date("Y-m-d H:i:s")) {

                $testManager = Container::instance()->getInterfaceImplementation(TestManager::class, $test->getType());
                $server = Container::instance()->getInterfaceImplementation(Server::class, Configuration::readParameter("server.key"));

                $server->performOperations($testManager->install($test));

                $test->setStatus(Test::STATUS_ACTIVE);
                $this->updateTest($test);
            }

            if ($test->getStatus() == Test::STATUS_ACTIVE && $test->getExpires() && $test->getExpires() <= date("Y-m-d H:i:s")) {

                $testManager = Container::instance()->getInterfaceImplementation(TestManager::class, $test->getType());
                $server = Container::instance()->getInterfaceImplementation(Server::class, Configuration::readParameter("server.key"));

                $server->performOperations($testManager->uninstall($test));

                $test->setStatus(Test::STATUS_COMPLETED);
                $this->updateTest($test);
            }
        }

    }


}