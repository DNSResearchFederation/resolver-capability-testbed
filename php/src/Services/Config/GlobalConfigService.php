<?php

namespace ResolverTest\Services\Config;

use Kinikit\Core\Configuration\ConfigFile;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use function PHPUnit\Framework\returnValue;

class GlobalConfigService extends ConfigFile {

    /**
     * @var NameserverConfigService $nameserverConfig
     */
    private $nameserverConfig;

    public function __construct() {
        parent::__construct(Configuration::readParameter("config.root") . "/resolvertest.conf");
        $this->nameserverConfig = Container::instance()->get(NameserverConfigService::class);
    }

    public function getIPv4Address() {
        return $this->getParameter("ipv4.address");
    }

    public function setIPv4Address($value) {
        $this->addParameter("ipv4.address", $value);
        $this->save();
    }

    public function getIPv6Address() {
        return $this->getParameter("ipv6.address");
    }

    public function setIPv6Address($value) {
        $this->addParameter("ipv6.address", $value);
        $this->save();
    }

    public function getNameservers() {
        return $this->nameserverConfig->getNameservers();
    }

    public function getNameserversByKey($key) {
        return $this->nameserverConfig->getNameserversByKey($key);
    }

    public function setNameservers($key, $value) {
        $this->nameserverConfig->setNameservers($key, $value);
    }

    public function isClientIpAddressLogging() {
        return $this->getParameter("client.ip.address.logging");
    }

    public function setClientIpAddressLogging($value) {
        $this->addParameter("client.ip.address.logging", $value);
        $this->save();
    }

    public function getDapApiKey() {
        return $this->getParameter("dap.api.key");
    }

    public function setDapApiKey($value) {
        $this->addParameter("dap.api.key", $value);
        $this->save();
    }

    public function getDapApiSecret() {
        return $this->getParameter("dap.api.secret");
    }

    public function setDapApiSecret($value) {
        $this->addParameter("dap.api.secret", $value);
        $this->save();
    }

    public function isValid() {
        if ($this->getIPv4Address() && $this->getIPv6Address()) {
            return true;
        } else {
            return false;
        }
    }
}