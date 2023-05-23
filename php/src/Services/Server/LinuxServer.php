<?php

namespace ResolverTest\Services\Server;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Template\MustacheTemplateParser;
use ResolverTest\Objects\Server\Config\DNSZone;
use ResolverTest\Objects\Server\Config\WebServerVirtualHost;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Services\Config\GlobalConfigService;

class LinuxServer implements Server {

    /**
     * @var GlobalConfigService
     */
    private $configService;

    /**
     * @var MustacheTemplateParser
     */
    private $templateParser;

    /**
     * @var FileResolver
     */
    private $fileResolver;

    const TEMPLATES = [
        DNSZone::class => "bind-zonefile.txt",
        WebServerVirtualHost::class => "httpd-virtualhost.txt"
    ];

    const TARGET_LOCATION_PARAM = [
        DNSZone::class => "server.bind.config.dir",
        WebServerVirtualHost::class => "sever.httpd.config.dir"
    ];

    /**
     * @param GlobalConfigService $configService
     * @param MustacheTemplateParser $templateParser
     * @param FileResolver $fileResolver
     */
    public function __construct($configService, $templateParser, $fileResolver) {
        $this->configService = $configService;
        $this->templateParser = $templateParser;
        $this->fileResolver = $fileResolver;
    }


    public function performOperations($operations) {

        foreach ($operations as $operation) {

            switch ($operation->getMode()) {
                case ServerOperation::OPERATION_ADD:
                    $this->installTemplateFile($operation);
                    break;

                case ServerOperation::OPERATION_REMOVE:
                    $this->removeTemplateFile($operation);
                    break;
            }

        }

    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function installTemplateFile($operation) {

        $config = $operation->getConfig();
        $class = get_class($config);

        $templateFile = $this->fileResolver->resolveFile("Config/templates/linux/" . self::TEMPLATES[$class]);
        $model = [
            "config" => $config,
            "globalConfig" => $this->configService->getAllParameters()
        ];

        $text = $this->templateParser->parseTemplateText(file_get_contents($templateFile), $model);

        $targetDirectory = Configuration::readParameter(self::TARGET_LOCATION_PARAM[$class]);
        file_put_contents($targetDirectory . "/conf.d/{$config->getIdentifier()}.conf", $text);

    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function removeTemplateFile($operation) {

        $config = $operation->getConfig();
        $configDir = self::TARGET_LOCATION_PARAM[get_class($config)];
        $path = Configuration::readParameter($configDir) . "/conf.d/{$config->getIdentifier()}.conf";

        if (file_exists($path)) {
            unlink($path);
        }

    }
}