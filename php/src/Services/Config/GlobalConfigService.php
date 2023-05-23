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

    public function setNameservers($value) {
        $this->addParameter("nameservers", $value);
        $this->save();
    }
}