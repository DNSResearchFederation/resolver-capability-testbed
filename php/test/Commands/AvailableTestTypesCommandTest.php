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
                "name" => "dnssec",
                "description" => "Test DNSSEC Algorithm"
            ],
            [
                "name" => "ipv6",
                "description" => "IPv6 Records Defined Only for domain name"
            ],
            [
                "name" => "minimum-ttl",
                "description" => "Minimum TTL Allowed"
            ],
            [
                "name" => "nsec",
                "description" => "Test NSEC is acknowledged"
            ],
            [
                "name" => "qname-minimisation",
                "description" => "Test QName Minimisation"
            ],
            [
                "name" => "tcp-fallback",
                "description" => "UDP falls back to TCP for large responses"
            ]
        ]);

        $this->assertEquals($expected->getTable(), $output);

    }

}