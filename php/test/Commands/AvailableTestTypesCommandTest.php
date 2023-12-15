<?php

namespace Commands;

use MathieuViossat\Util\ArrayToTextTable;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\AvailableTestTypesCommand;

include_once "autoloader.php";

class AvailableTestTypesCommandTest extends TestCase {

    public function testDoesListTheTestTypesCorrectly() {

        $command = new AvailableTestTypesCommand();

        ob_start();
        $command->handleCommand();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = new ArrayToTextTable([
            [
                "name" => "aggressive-nsec",
                "description" => "Test whether resolvers observe NSEC record responses such that a request for two consecutive records results in no further request for one in the middle."
            ],
            [
                "name" => "dnssec-unsigned",
                "description" => "Test whether resolvers resolve records when a zone is not signed using DNSSEC but is validated with DS records at the Registrar"
            ],
            [
                "name" => "dnssec-unvalidated",
                "description" => "Test whether resolvers resolve records when a zone is signed using DNSSEC but not validated with DS records at the Registrar"
            ],
            [
                "name" => "dnssec",
                "description" => "Test whether resolvers resolve records when a zone is signed using DNSSEC and validated with DS records at the Registrar"
            ],
            [
                "name" => "ipv6",
                "description" => "Test whether resolvers lookup records for a nameserver which only has IPv6 addresses assigned for routing"
            ],
            [
                "name" => "minimum-ttl",
                "description" => "Test whether resolvers observe TTL values supplied in DNS records and don't make additional requests"
            ],

            [
                "name" => "qname-minimisation",
                "description" => "Test whether resolvers are QName minimising for a defined subdomain"
            ],
            [
                "name" => "tcp-fallback",
                "description" => "Test whether resolvers are falling back to TCP for large responses returned over UDP - where TC=1 is returned by nameserver"
            ]
        ]);

        $this->assertEquals($expected->getTable(), $output);

    }

}