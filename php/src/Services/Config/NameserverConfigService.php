<?php

namespace ResolverTest\Services\Config;

use Kinikit\Core\Configuration\ConfigFile;
use Kinikit\Core\Configuration\Configuration;

class NameserverConfigService extends ConfigFile {

    public function __construct() {
        parent::__construct(Configuration::readParameter("config.root") . "/resolvertest.nameservers.conf");
    }

    public function setNameservers($key, $value) {
        $this->addParameter($key, implode(",", $value));
        $this->save();
    }

    public function deleteNameservers($key) {
        $this->removeParameter($key);
    }

    public function getNameservers() {
        return array_map(function ($value) {
            return explode(",", $value);
        }, $this->getAllParameters());
    }

    public function getNameserversByKey($key) {
        return explode(",", $this->getParameter($key) ?? "");
    }

}