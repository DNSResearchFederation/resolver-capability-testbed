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
        return $this->whois->loadDomainInfo($domain)->getNameServers();
    }

}