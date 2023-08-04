<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\Config\GlobalConfigService;

include_once "autoloader.php";

class DAPLoggerTest extends TestCase {

    /**
     * @var GlobalConfigService
     */
    private $configService;

    /**
     * @var MockObject
     */
    private $requestDispatcher;

    public function setUp(): void {
        $this->configService = Container::instance()->get(GlobalConfigService::class);
        $this->requestDispatcher = MockObjectProvider::instance()->getMockInstance(HttpRequestDispatcher::class);
    }

    public function testCanCheckExistenceOfDAPCredentials() {

        $dapLogger = new DAPLogger($this->configService, $this->requestDispatcher);

        $this->configService->setDapApiKey("12345abc");
        $this->configService->setDapApiSecret("98765abc");
        $this->assertTrue($dapLogger->hasGotCredentials());

        $this->configService->setDapApiKey(null);
        $this->configService->setDapApiSecret(null);
        $this->assertFalse($dapLogger->hasGotCredentials());

    }

    public function testCanMakeCorrectAPICallForSendingLogToDAP() {

        $dapLogger = new DAPLogger($this->configService, $this->requestDispatcher);

        $this->configService->setDapApiKey("apiKey");
        $this->configService->setDapApiSecret("apiSecret");

        $dapLogger->writeLogToDAP("key", "type", ["fieldA" => "this", "fieldB" => "that"]);

        $expectedRequest = new Request("https://webservices.dap.live/api/tabularData/type", Request::METHOD_POST, [],
            '[{"key":"key","field_a":"this","field_b":"that"}]', new Headers(["API-KEY" => "apiKey", "API-SECRET" => "apiSecret"]));


        $this->assertTrue($this->requestDispatcher->methodWasCalled("dispatch"));
        $this->assertEquals($expectedRequest, $this->requestDispatcher->getMethodCallHistory("dispatch")[0][0]);


    }

}