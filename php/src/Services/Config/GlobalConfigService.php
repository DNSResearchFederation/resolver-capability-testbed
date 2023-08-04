<?php

namespace ResolverTest\Services\Config;

use Kinikit\Core\Configuration\ConfigFile;
use Kinikit\Core\Configuration\Configuration;

class GlobalConfigService extends ConfigFile {

    public function __construct() {
        parent::__construct(Configuration::readParameter("config.root") . "/resolvertest.conf");
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
        return explode(",", $this->getParameter("nameservers"));
    }

    public function getFirstNameserver() {
        return explode(",", $this->getParameter("nameservers"))[0];
    }

    public function setNameservers($value) {
        $this->addParameter("nameservers", $value);
        $this->save();
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
        if ($this->getIPv4Address() && $this->getIPv6Address() && $this->getNameservers()) {
            return true;
        } else {
            return false;
        }
    }
}