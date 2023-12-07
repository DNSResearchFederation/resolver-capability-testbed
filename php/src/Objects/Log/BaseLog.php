<?php

namespace ResolverTest\Objects\Log;

use ResolverTest\ValueObjects\TestType\TestTypeRules;

abstract class BaseLog {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var string
     */
    protected $date;

    /**
     * @param string $hostname
     * @param string $date
     */
    public function __construct($hostname, $date) {
        $this->hostname = $hostname;
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     */
    public function setHostname($hostname) {
        $this->hostname = $hostname;
    }

    /**
     * @return string
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate($date) {
        $this->date = $date;
    }


    /**
     * Resolve a relational key value for a given relational key
     *
     * @param $relationalKey
     * @return string
     */
    public function getRelationalKeyValue($relationalKey){

        switch ($relationalKey) {
            case TestTypeRules::RELATIONAL_KEY_HOSTNAME:
                return $this->getHostname();

            case TestTypeRules::RELATIONAL_KEY_IP_ADDRESS:
                $hostname = $this->getHostname();
                $components = explode(".", $hostname);
                $hostnameLeaf = implode(".", array_slice($components, -3));

                return $hostnameLeaf;

            default:
                return null;
        }
    }

}