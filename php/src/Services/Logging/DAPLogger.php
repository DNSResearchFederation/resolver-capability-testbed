<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Util\StringUtils;
use ResolverTest\Services\Config\GlobalConfigService;

class DAPLogger {

    /**
     * @var GlobalConfigService
     */
    private $configService;

    /**
     * @var HttpRequestDispatcher
     */
    private $requestDispatcher;

    /**
     * @param GlobalConfigService $configService
     * @param HttpRequestDispatcher $requestDispatcher
     */
    public function __construct($configService, $requestDispatcher) {
        $this->configService = $configService;
        $this->requestDispatcher = $requestDispatcher;
    }


    public function hasGotCredentials() {

        $apiSecret = $this->configService->getDapApiSecret();
        $apiKey = $this->configService->getDapApiKey();
        return $apiSecret && $apiKey;

    }

    /**
     * @param string $sessionKey
     * @param string $type
     * @param array $log
     * @return void
     */
    public function writeLogToDAP($sessionKey, $type, $log) {

        $apiSecret = $this->configService->getDapApiSecret();
        $apiKey = $this->configService->getDapApiKey();

        $payloadArray["key"] = $sessionKey;

        foreach ($log as $key => $value) {

            $newKey = StringUtils::convertToSnakeCase($key);
            $payloadArray[$newKey] = $value;

        }

        $payload = json_encode($payloadArray);

        $request = new Request("https://webservices.dap.live/api/tabularData/$type", Request::METHOD_POST, [], "[$payload]", new Headers(["API-KEY" => $apiKey, "API-SECRET" => $apiSecret]));

        $response = $this->requestDispatcher->dispatch($request);

    }

}