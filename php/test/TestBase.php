<?php

namespace ResolverTest;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinikit\Persistence\Tools\TestDataInstaller;
use PHPUnit\Framework\TestCase;

class TestBase extends TestCase {

    public function setUp(): void {


        //     Container::instance()->get(Bootstrap::class)->setup();

        if (!file_exists("DB")) {
            mkdir("DB");
        }

        $testDataInstaller = Container::instance()->get(TestDataInstaller::class);
        $testDataInstaller->run(true, ["../src"]);

    }

}