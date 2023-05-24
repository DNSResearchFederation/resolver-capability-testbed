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
        WebServerVirtualHost::class => "server.httpd.config.dir"
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
                    switch (get_class($operation->getConfig())) {
                        case DNSZone::class:
                            $this->installBind($operation);
                            break;

                        case WebServerVirtualHost::class:
                            $this->installHttpd($operation);
                            break;
                    }
                    break;

                case ServerOperation::OPERATION_REMOVE:
                    switch (get_class($operation->getConfig())) {
                        case DNSZone::class:
                            $this->uninstallBind($operation);
                            break;

                        case WebServerVirtualHost::class:
                            $this->uninstallHttpd($operation);
                            break;
                    }
                    break;
            }

        }

    }

    // Install bind
    private function installBind($operation) {
        $this->installTemplateFile($operation, "bind-zonefile.txt", Configuration::readParameter("server.bind.config.dir"));
    }

    // Uninstall bind
    private function uninstallBind($operation) {
        $this->removeTemplateFile($operation, Configuration::readParameter("server.bind.config.dir"));
    }

    // Install httpd
    private function installHttpd($operation) {

        $config = $operation->getConfig();

        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/" . $config->getHostname();
        if (!file_exists($contentDir)) {
            mkdir($contentDir);
        }
        file_put_contents($contentDir . "/index.html", $config->getContent());

        $model = [
            "serverWebRoot" => Configuration::readParameter("server.httpd.webroot.dir")
        ];

        $this->installTemplateFile($operation, "httpd-virtualhost.txt", Configuration::readParameter("server.httpd.config.dir"), $model);
    }

    private function uninstallHttpd($operation) {

        $config = $operation->getConfig();
        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/" . $config->getHostname();
        passthru("rm -rf $contentDir");

        $this->removeTemplateFile($operation, Configuration::readParameter("server.httpd.config.dir"));
    }


    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function installTemplateFile($operation, $template, $targetDirectory, $model = []) {

        $config = $operation->getConfig();

        $templateFile = $this->fileResolver->resolveFile("Config/templates/linux/$template");
        $model = array_merge($model, [
            "operationConfig" => $config,
            "resolverConfig" => $this->configService
        ]);

        $text = $this->templateParser->parseTemplateText(file_get_contents($templateFile), $model);

        file_put_contents($targetDirectory . "/{$config->getIdentifier()}.conf", $text);

    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function removeTemplateFile($operation, $targetDirectory) {

        $config = $operation->getConfig();

        $path = $targetDirectory . "/{$config->getIdentifier()}.conf";

        if (file_exists($path)) {
            unlink($path);
        }

    }
}