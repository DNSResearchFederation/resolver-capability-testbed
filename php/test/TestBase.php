<?php


use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinikit\Persistence\Tools\TestDataInstaller;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\Server\Server;

class TestBase extends TestCase {

    public static function setUpBeforeClass(): void {
        $testServer = MockObjectProvider::instance()->getMockInstance(Server::class);
        Container::instance()->addInterfaceImplementation("\\" . Server::class, "test", get_class($testServer));
        Container::instance()->set(get_class($testServer), $testServer);
    }

    public function setUp(): void {

        if (!file_exists("DB")) {
            mkdir("DB");
        }

        $testDataInstaller = Container::instance()->get(TestDataInstaller::class);
        $testDataInstaller->run(true, ["../src"]);

    }

}