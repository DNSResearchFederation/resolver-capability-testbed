<?php

namespace ResolverTest\Services;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use ResolverTest\Exception\InvalidConfigException;
use ResolverTest\Exception\InvalidTestKeyException;
use ResolverTest\Exception\NonExistentTestException;
use ResolverTest\Exception\TestAlreadyExistsForDomainException;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\Services\Config\NameserverConfigService;
use ResolverTest\Services\Logging\LoggingService;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestType\TestTypeManager;
use ResolverTest\Services\Whois\WhoisService;

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
     * @var TestTypeManager
     */
    private $testTypeManager;

    /**
     * @var GlobalConfigService
     */
    private $globalConfig;

    /**
     * @var NameserverConfigService
     */
    private $nameserverConfig;

    /**
     * @var WhoisService
     */
    private $whoisService;

    /**
     * @var Server
     */
    private $server;

    /**
     * @param JSONToObjectConverter $jsonToObjectConverter
     * @param ObjectToJSONConverter $objectToJSONConverter
     * @param TestTypeManager $testTypeManager
     * @param GlobalConfigService $globalConfig
     * @param NameserverConfigService $nameserverConfig
     * @param WhoisService $whoisService
     * @param Server $server
     */
    public function __construct($jsonToObjectConverter, $objectToJSONConverter, $testTypeManager, $globalConfig, $nameserverConfig, $whoisService, $server) {
        $this->jsonToObjectConverter = $jsonToObjectConverter;
        $this->objectToJSONConverter = $objectToJSONConverter;
        $this->testTypeManager = $testTypeManager;
        $this->globalConfig = $globalConfig;
        $this->nameserverConfig = $nameserverConfig;
        $this->whoisService = $whoisService;
        $this->server = $server;
    }

    /**
     * @return Test[]
     */
    public function listTests() {
        return Test::filter("ORDER BY starts");
    }


    /**
     * Get a test by key
     *
     * @param $key
     * @return Test
     */
    public function getTest($key) {
        try {
            return Test::fetch($key);
        } catch (ObjectNotFoundException $e) {
            throw new InvalidTestKeyException($key);
        }
    }

    /**
     * @param string $hostname
     * @return Test
     */
    public function getTestByHostname($hostname) {
        return Test::filter("WHERE '$hostname' LIKE '%' || domain_name AND status LIKE 'ACTIVE'")[0] ?? null;
    }


    /**
     * @param $test Test
     * @return Test
     */
    public function createTest($test) {

        // Check the global config is correct
        if (!($this->globalConfig->isValid())) {
            throw new InvalidConfigException();
        }

        // Check the nameservers are correct for the domain
        $configNameservers = $this->nameserverConfig->getNameserversByKey($test->getNameserversKey()) ?? [];

        $actualNameservers = $this->whoisService->getNameservers($test->getDomainName()) ?? [];
        if (sort($configNameservers) != sort($actualNameservers)) {
            throw new \Exception("The domain doesn't have the correct nameservers");
        };

        // Validate the test for general correctness
        $test->validate();


        // Check key is unique
        try {
            Test::fetch($test->getKey());
            throw new InvalidTestKeyException($test->getKey());
        } catch (ObjectNotFoundException $e) {
            // Great
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
        $test->save();

        /**
         * @var LoggingService $loggingService
         */
        $loggingService = Container::instance()->get(LoggingService::class);
        $loggingService->createLogDatabaseForTest($test);

        // Synchronise tests to start this test if required
        $this->synchroniseTest($test);

        return $test;
    }

    /**
     * @param Test $test
     * @return void
     */
    public function updateTest($test) {
        try {
            $key = $test->getKey();
            Test::fetch($key);
        } catch (ObjectNotFoundException $e) {
            throw new NonExistentTestException($key);
        }

        $test->save();
        $this->synchroniseTest($test);
    }

    /**
     * @param $key string
     * @return void
     */
    public function deleteTest($key) {

        $test = $this->getTest($key);
        if ($test->getStatus() == Test::STATUS_ACTIVE) {
            $this->stopTest($key);
        }

        /**
         * @var LoggingService $loggingService
         */
        $loggingService = Container::instance()->get(LoggingService::class);
        $loggingService->removeLogDatabaseForTest($test);

        $test->remove();
    }

    public function startTest($key) {

        $test = $this->getTest($key);

        if ($test->getStatus() == Test::STATUS_PENDING) {
            $test->setStarts(new \DateTime());
            $this->updateTest($test);
            $this->synchroniseTests();
        }
    }

    public function stopTest($key) {

        $test = $this->getTest($key);

        if ($test->getStatus() == Test::STATUS_ACTIVE) {
            $test->setExpires(new \DateTime());
            $this->updateTest($test);
            $this->synchroniseTests();
        }

    }

    public function synchroniseTest($test) {

        $testProcessed = false;

        if ($test->getStatus() == Test::STATUS_PENDING && $test->getStarts()->format("Y-m-d H:i:s") <= date("Y-m-d H:i:s")) {

            $test->setStatus(Test::STATUS_INSTALLING);
            $this->updateTest($test);

            $additionalInfo = $this->server->performOperations($this->testTypeManager->getInstallServerOperations($test));

            $test->setAdditionalInformation(array_merge($test->getAdditionalInformation() ?? [], $additionalInfo ?? []));
            $test->setStatus(Test::STATUS_ACTIVE);
            $this->updateTest($test);

            $testProcessed = true;

        }

        if ($test->getStatus() == Test::STATUS_ACTIVE && $test->getExpires() && $test->getExpires()->format("Y-m-d H:i:s") <= date("Y-m-d H:i:s")) {

            $test->setStatus(Test::STATUS_UNINSTALLING);
            $this->updateTest($test);

            $this->server->performOperations($this->testTypeManager->getUninstallServerOperations($test));

            $test->setStatus(Test::STATUS_COMPLETED);
            $this->updateTest($test);

            $testProcessed = true;
        }

        return $testProcessed;


    }

    public function synchroniseTests() {
        // Update status according to start/end
        foreach (Test::filter() as $test) {

            $this->synchroniseTest($test);

        }

    }


}