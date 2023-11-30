<?php

namespace ResolverTest\Services\Server;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Template\MustacheTemplateParser;
use ResolverTest\Objects\Log\BaseLog;
use ResolverTest\Objects\Log\NameserverLog;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\ValueObjects\TestType\Config\DNSZone;
use ResolverTest\ValueObjects\TestType\Config\WebServerVirtualHost;

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


    /**
     * Perform the supplied operations and return optionally an array of additional information strings
     *
     * @param $operations
     * @return string[]
     */
    public function performOperations($operations) {

        $additionalInfo = [];

        foreach ($operations as $operation) {

            switch ($operation->getMode()) {
                case ServerOperation::OPERATION_ADD:
                    switch (get_class($operation->getConfig())) {
                        case DNSZone::class:
                            $additionalInfo = array_merge($additionalInfo, $this->installBind($operation));
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

        return $additionalInfo;

    }

    /**
     * @param string $logString
     * @param string $service
     * @return BaseLog
     */
    public function processLog($logString, $service) {
        switch ($service) {
            case Server::SERVICE_NAMESERVER:
                $components = explode(" ", $logString);

                $date = date_create($components[0] . " " . $components[1]);
                $ipAddress = explode("#", $components[5])[0];
                $port = explode("#", $components[5])[1];
                $hostname = trim($components[6], "():");
                $request = implode(" ", array_slice($components, 8, 3));
                $recordType = $components[10];
                $flags = $components[11];

                return new NameserverLog($hostname, $date, $ipAddress, $port, $request, $recordType, $flags);

            case Server::SERVICE_WEBSERVER:
                $components = explode(" ", $logString);

                $hostname = trim(array_shift($components), "\"");
                $ipAddress = array_shift($components);
                $date = date_create(trim(array_shift($components) . " " . array_shift($components), "[]"));
                $userAgent = array_pop($components);

                while (strpos($userAgent, "\"") > 0) {
                    $userAgent = array_pop($components) . " " . $userAgent;
                }

                $userAgent = trim($userAgent, "\"\t\n\r\0\x0B");
                $statusCode = intval(array_pop($components));

                return new WebserverLog($hostname, $date, $ipAddress, $userAgent, $statusCode);
        }


    }

    /**
     * @param ServerOperation $operation
     * @return string[]
     */
    private function installBind($operation) {

        $additionalInfo = [];

        /**
         * @var DNSZone $config
         */
        $config = $operation->getConfig();

        $dnssecConfig = $config->getDnsSecConfig();

        $model = [];

        $domainName = $config->getDomainName();

        // If doing DNS Sec, make the keys
        $dnsSECDir = null;
        if ($dnssecConfig) {
            $dnsSECDir = Configuration::readParameter("server.bind.config.dir") . "/dnssec/" . $domainName;
            passthru($this->sudoPrefix . " mkdir -m 777 -p " . $dnsSECDir);

            $keyGenArgs = " -L 3600 -a " . $dnssecConfig->getAlgorithmKey();

            // Add strength if supplied
            $keyStrength = $dnssecConfig->getKeyStrength();
            if ($keyStrength) {
                $keyGenArgs .= " -b $keyStrength";
            }

            // Add zone info
            $keyGenArgs .= " -n ZONE " . $domainName;

            // Build the key gen command
            $keyGenCommand = Configuration::readParameter("server.dnssec.keygen.command") . " -K " . $dnsSECDir;

            // If NSEC3 in use, add a -3 to the command
            if ($dnssecConfig->isNsec3()) {
                $keyGenCommand .= " -3";
            }

            // Create the zone signing key (ZSK)
            passthru($this->sudoPrefix . " " . $keyGenCommand . $keyGenArgs);

            // Create the key signing key (KSK)
            passthru($this->sudoPrefix . " " . $keyGenCommand . " -f KSK" . $keyGenArgs);

            // Grab the key data and map to DNSKEY records
            ob_start();
            passthru($this->sudoPrefix . " ls $dnsSECDir");
            $files = explode("\n", ob_get_contents());
            ob_end_clean();

            $dnsSECRecords = [];
            $dsRecords = [];
            foreach ($files as $file) {
                if (str_ends_with($file, ".key")) {
                    ob_start();
                    passthru($this->sudoPrefix . " cat $dnsSECDir/$file");
                    $dnsSECRecords[] = ob_get_contents();
                    ob_end_clean();
                }
                if (str_starts_with($file, "dsset")) {
                    ob_start();
                    passthru($this->sudoPrefix . " cat $dnsSECDir/$file");
                    $dsRecords = ob_get_contents();
                    ob_end_clean();
                }
            }

            if ($dsRecords && $dnssecConfig->isGenerateDSRecords()) {
                $additionalInfo[] = "Please add the following DS records for " . $domainName . " via your Registrar\n\n" . $dsRecords;
            }

            // If we are signing the zone, add the dnssec records
            if ($dnssecConfig->isSignZone())
                $model["dnsSECRecords"] = $dnsSECRecords;

        }


        $targetUser = Configuration::readParameter("server.bind.service.user");
        $this->installTemplateFile($operation, "bind-zonefile.txt", Configuration::readParameter("server.bind.config.dir"), $targetUser, $model);

        $model = ["domainName" => $operation->getConfig()->getIdentifier()];
        $templateFile = $this->fileResolver->resolveFile("Config/templates/linux/bind-zones-entry.txt");
        $text = $this->templateParser->parseTemplateText(file_get_contents($templateFile), $model);
        file_put_contents(Configuration::readParameter("server.bind.zones.path"), $text, FILE_APPEND);


        // If dnssec config and no web virtual hosts, sign the zone file now
        if ($dnssecConfig && $dnssecConfig->isSignZone() && !$config->getHasWebVirtualHost()) {
            $this->signZoneForDNSSEC($domainName, $dnssecConfig->isNsec3());
        } else {
            // Restart bind
            $serviceCommand = Configuration::readParameter("server.bind.service.command");
            passthru("{$this->sudoPrefix} $serviceCommand restart");
        }


        return $additionalInfo;

    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function uninstallBind($operation) {

        /**
         * @var DNSZone $config
         */
        $config = $operation->getConfig();


        $bindConfigDir = Configuration::readParameter("server.bind.config.dir");
        if ($config->getDnsSecConfig()) {
            $this->removeTemplateFile($operation, $bindConfigDir, ".unsigned");
            passthru("{$this->sudoPrefix} rm -rf $bindConfigDir/dnssec/" . $config->getIdentifier());
        }
        $this->removeTemplateFile($operation, $bindConfigDir);

        $remainingZones = preg_replace(" /zone \"" . $operation->getConfig()->getDomainName() . "\"[a-zA-Z0-9\-\s;\/\.\"{]+};/", "", file_get_contents(Configuration::readParameter("server.bind.zones.path")));
        file_put_contents(Configuration::readParameter("server.bind.zones.path"), $remainingZones);

        // Reload bind
        $serviceCommand = Configuration::readParameter("server.bind.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand reload");
    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function installHttpd($operation) {

        /**
         * @var WebServerVirtualHost $config
         */
        $config = $operation->getConfig();
        $targetUser = Configuration::readParameter("server.httpd.service.user");

        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/" . $config->getDomainName();
        if (!file_exists($contentDir)) {
            passthru("{$this->sudoPrefix} mkdir -p $contentDir");
        }

        $this->sudoWriteFile($contentDir . "/index.html", $config->getContent(), $targetUser);

        $secure = boolval($config->getSslCertPrefixes());

        $model = [
            "secure" => $secure,
            "serverWebRoot" => Configuration::readParameter("server.httpd.webroot.dir"),
            "serverAliases" => array_map(function ($elt) use ($config) {
                return $elt . "." . $config->getIdentifier();
            }, $config->getSslCertPrefixes())
        ];

        $this->installTemplateFile($operation, "httpd-virtualhost.txt", Configuration::readParameter("server.httpd.config.dir"), $targetUser, $model);

        // SSL Certificate
        if ($secure) {
            foreach ($config->getSslCertPrefixes() as $prefix) {
                $sslDomains[] = $prefix . "." . $config->getIdentifier();
            }
            $sslDomainsString = implode(",", $sslDomains);
            $authHook = Configuration::readParameter("config.root") . "/../src/resolvertest/scripts/certbot-certificate-install.sh";
            passthru("{$this->sudoPrefix} certbot certonly --webroot-path $contentDir --manual --preferred-challenges=dns --server https://acme-v02.api.letsencrypt.org/directory --agree-tos --register-unsafely-without-email --manual-auth-hook $authHook -d $sslDomainsString");
        }
        // Reload httpd
        $serviceCommand = Configuration::readParameter("server.httpd.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand reload");

        // If DNSSEC signed, sign now
        if ($config->getDNSSecConfig()) {
            $this->signZoneForDNSSEC($config->getDomainName(),$config->getDNSSecConfig());
        }
    }

    /**
     * @param ServerOperation $operation
     * @return void
     */
    private function uninstallHttpd($operation) {

        $config = $operation->getConfig();
        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/" . $config->getDomainName();

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
    private function installTemplateFile($operation, $template, $targetDirectory, $targetUser, $model = [], $suffix = "") {

        $config = $operation->getConfig();

        $templateFile = $this->fileResolver->resolveFile("Config/templates/linux/$template");
        $model = array_merge($model, [
            "operationConfig" => $config,
            "resolverConfig" => $this->configService,
        ]);

        $text = $this->templateParser->parseTemplateText(file_get_contents($templateFile), $model);

        $this->sudoWriteFile($targetDirectory . "/{$config->getIdentifier()}.conf" . $suffix, $text, $targetUser);

    }

    /**
     * @param ServerOperation $operation
     * @param string $targetDirectory
     * @return void
     */
    private function removeTemplateFile($operation, $targetDirectory, $suffix = "") {

        $config = $operation->getConfig();

        $path = $targetDirectory . "/{$config->getIdentifier()}.conf" . $suffix;

        $this->sudoRemoveFile($path);
    }

    /**
     * @param string $filePath
     * @param string $content
     * @param string $targetUser
     * @return void
     */
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

    /**
     * @param string $filePath
     * @return void
     */
    private function sudoRemoveFile($filePath) {
        passthru("{$this->sudoPrefix} rm -rf $filePath");
    }

    /**
     * Sign a zone for DNSSec using keys in supplied directory and domain name
     *
     * @param string $domainName
     * @return void
     */
    private function signZoneForDNSSEC($domainName, $nsec3): void {

        // Get zone config dir
        $configDir = Configuration::readParameter("server.bind.config.dir");
        $dnsSECDir = $configDir . "/dnssec/" . $domainName;

        // Build the sign zone command
        $signZoneCommand = Configuration::readParameter("server.dnssec.signzone.command") . " -K " . $dnsSECDir . " -d " . $dnsSECDir;
        $signZoneCommand .= " -N INCREMENT -o " . $domainName . ($nsec3 ? " -3" : "") . " " . $configDir . "/$domainName.conf";

        passthru($this->sudoPrefix . " " . $signZoneCommand);

        // Move signed file back to the main zone
        passthru($this->sudoPrefix . " mv " . $configDir . "/$domainName.conf " . $configDir . "/$domainName.conf.unsigned");
        passthru($this->sudoPrefix . " mv " . $configDir . "/$domainName.conf.signed " . $configDir . "/$domainName.conf");

        // Restart bind
        $serviceCommand = Configuration::readParameter("server.bind.service.command");
        passthru("{$this->sudoPrefix} $serviceCommand restart");

    }
}