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

    /**
     * @var string
     */
    private $sudoPrefix;


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
     * @param string $sudoPrefix
     */
    public function __construct($configService, $templateParser, $fileResolver, $sudoPrefix = "sudo") {
        $this->configService = $configService;
        $this->templateParser = $templateParser;
        $this->fileResolver = $fileResolver;
        $this->sudoPrefix = $sudoPrefix;
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

        $targetUser = Configuration::readParameter("server.bind.service.user");
        $this->installTemplateFile($operation, "bind-zonefile.txt", Configuration::readParameter("server.bind.config.dir"), $targetUser);

        $model = ["domainName" => $operation->getConfig()->getIdentifier()];
        $templateFile = $this->fileResolver->resolveFile("Config/templates/linux/bind-zones-entry.txt");
        $text = $this->templateParser->parseTemplateText(file_get_contents($templateFile), $model);
        file_put_contents(Configuration::readParameter("server.bind.zones.path"), $text, FILE_APPEND);

        // Reload bind
        $serviceCommand = Configuration::readParameter("server.bind.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand reload");
    }

    // Uninstall bind
    private function uninstallBind($operation) {

        $this->removeTemplateFile($operation, Configuration::readParameter("server.bind.config.dir"));

        $remainingZones = preg_replace("/zone \"" . $operation->getConfig()->getDomainName() ."\"[a-zA-Z0-9\s;\.\"{]+};/", "", file_get_contents(Configuration::readParameter("server.bind.zones.path")));
        file_put_contents(Configuration::readParameter("server.bind.zones.path"), $remainingZones);

        // Reload bind
        $serviceCommand = Configuration::readParameter("server.bind.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand reload");
    }

    // Install httpd
    private function installHttpd($operation) {

        $config = $operation->getConfig();
        $targetUser = Configuration::readParameter("server.httpd.service.user");

        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/" . $config->getHostname();
        if (!file_exists($contentDir)) {
            passthru("{$this->sudoPrefix} mkdir -p $contentDir");
        }

        $this->sudoWriteFile($contentDir . "/index.html", $config->getContent(), $targetUser);

        $model = [
            "serverWebRoot" => Configuration::readParameter("server.httpd.webroot.dir")
        ];

        $this->installTemplateFile($operation, "httpd-virtualhost.txt", Configuration::readParameter("server.httpd.config.dir"), $targetUser, $model);

        // Reload httpd
        $serviceCommand = Configuration::readParameter("server.httpd.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand reload");
    }

    private function uninstallHttpd($operation) {

        $config = $operation->getConfig();
        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/" . $config->getHostname();

        passthru("{$this->sudoPrefix} rm -rf $contentDir");

        $this->removeTemplateFile($operation, Configuration::readParameter("server.httpd.config.dir"));

        // Reload httpd
        $serviceCommand = Configuration::readParameter("server.httpd.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand reload");
    }


    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function installTemplateFile($operation, $template, $targetDirectory, $targetUser, $model = []) {

        $config = $operation->getConfig();

        $templateFile = $this->fileResolver->resolveFile("Config/templates/linux/$template");
        $model = array_merge($model, [
            "operationConfig" => $config,
            "resolverConfig" => $this->configService
        ]);

        $text = $this->templateParser->parseTemplateText(file_get_contents($templateFile), $model);

        $this->sudoWriteFile($targetDirectory . "/{$config->getIdentifier()}.conf", $text, $targetUser);

    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function removeTemplateFile($operation, $targetDirectory) {

        $config = $operation->getConfig();

        $path = $targetDirectory . "/{$config->getIdentifier()}.conf";

        if (file_exists($path)) {
            $this->sudoRemoveFile($path);
        }

    }

    // Write a file as sudo
    private function sudoWriteFile($filePath, $content, $targetUser) {
        $tmpFile = tempnam(sys_get_temp_dir(), "file-");
        file_put_contents($tmpFile, $content);
        passthru("{$this->sudoPrefix} mv $tmpFile $filePath");
        if (Configuration::readParameter("server.selinux")) {
            passthru("{$this->sudoPrefix} restorecon -v $filePath >/dev/null");
        }
        passthru("{$this->sudoPrefix} chown $targetUser $filePath");
        passthru("{$this->sudoPrefix} chmod 755 $filePath");
    }

    // Remove a file aas sudo
    private function sudoRemoveFile($filePath) {
        passthru("{$this->sudoPrefix} rm -rf $filePath");
    }
}