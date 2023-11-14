<?php

namespace ResolverTest\Services\Whois;

use Iodev\Whois\Factory;
use Iodev\Whois\Whois;

class WhoisService {

    /**
     * @var Whois
     */
    private $whois;

    public function __construct() {
        $this->whois = Factory::get()->createWhois();
    }

    /**
     * @param string $domain
     * @return array
     */
    public function getNameservers($domain) {
        $domainInfo = $this->whois->loadDomainInfo($domain);
        if ($domainInfo) {
        return $domainInfo->getNameServers();
            } else {
            throw new \Exception("Unknown domain\n");
        }
    }

}