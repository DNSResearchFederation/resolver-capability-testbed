<?php

namespace ResolverTest\Services\TestType;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\ValueObjects\TestType\TestType;


/**
 * Test type manager
 */
class TestTypeManager {

    /**
     * @var TestType[]
     */
    private $testTypes;

    /**
     * @var GlobalConfigService
     */
    private $globalConfig;

    public function __construct() {
        $this->globalConfig = Container::instance()->get(GlobalConfigService::class);
    }

    /**
     * List all test types
     *
     * @return TestType[]
     */
    public function listTestTypes() {
        return $this->loadTestTypes();
    }

    /**
     * @param Test $test
     * @return TestType
     */
    public function getTestTypeForTest($test) {
        $list = $this->listTestTypes();
        return $list[$test->getType()] ?? null;
    }

    /**
     * Get the install server operations
     *
     * @param Test $test
     * @return ServerOperation[]
     */
    public function getInstallServerOperations($test) {

        $testType = $this->listTestTypes()[$test->getType()];
        $serverOperations = [];
        $config = $testType->getConfig();

        $testData = $test->getTestData();

        // Map parameter values
        $testParameterValues = [];
        foreach ($testType->getParameters() ?? [] as $index => $parameter) {
            $testParameterValues[$parameter->getIdentifier()] = $testData[$index] ?? null;
        }

        $dnsZones = $config->getDnsZones() ?? [$config->getDnsZone()];

        if ($dnsZones) {
            foreach ($dnsZones as $dnsZone) {
                $dnsZone->updateDynamicValues($this->globalConfig, $test, $testParameterValues);
                $serverOperations[] = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);
            }
        }

        $webVirtualHosts = $config->getWebVirtualHosts() ?? [$config->getWebVirtualHost()];

        if ($webVirtualHosts) {
            foreach ($webVirtualHosts as $webVirtualHost) {
                $webVirtualHost->updateDynamicValues($test, $testParameterValues);
                $serverOperations[] = new ServerOperation(ServerOperation::OPERATION_ADD, $webVirtualHost);
            }
        }

        return $serverOperations;
    }


    /**
     * Get the uninstall server operations
     *
     * @param Test $test
     * @return ServerOperation[]
     */
    public function getUninstallServerOperations($test) {

        $testType = $this->listTestTypes()[$test->getType()];
        $serverOperations = [];
        $config = $testType->getConfig();

        $dnsZones = $config->getDnsZones() ?? [$config->getDnsZone()];

        foreach ($dnsZones as $dnsZone) {
            $dnsZone->updateDynamicValues($this->globalConfig, $test);
            $serverOperations[] = new ServerOperation(ServerOperation::OPERATION_REMOVE, $dnsZone);
        }

        $webVirtualHosts = $config->getWebVirtualHosts() ?? [$config->getWebVirtualHost()];
        foreach ($webVirtualHosts as $webVirtualHost) {
            $webVirtualHost->updateDynamicValues($test);
            $serverOperations[] = new ServerOperation(ServerOperation::OPERATION_REMOVE, $webVirtualHost);
        }

        return $serverOperations;

    }


    /**
     * @return TestType[]
     */
    private function loadTestTypes() {

        if (!$this->testTypes) {

            /**
             * @var JSONToObjectConverter $jsonToObjectConverter
             */
            $jsonToObjectConverter = Container::instance()->get(JSONToObjectConverter::class);

            /**
             * @var FileResolver $fileResolver
             */
            $fileResolver = Container::instance()->get(FileResolver::class);

            // In-built tests
            $path = $fileResolver->resolveFile("Config/templates/test-type") . "/*";
            foreach (glob($path) as $file) {
                $testType = $jsonToObjectConverter->convert(file_get_contents($file), TestType::class);
                $this->testTypes[$testType->getType()] = $testType;
            }

            // Custom tests
            foreach (glob(Configuration::readParameter("config.root") . "/resolvertest/*") as $file) {
                $testType = $jsonToObjectConverter->convert(file_get_contents($file), TestType::class);
                $this->testTypes[$testType->getType()] = $testType;
            }
        }

        return $this->testTypes;

    }

}