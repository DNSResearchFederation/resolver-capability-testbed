<?php

namespace ResolverTest\ValueObjects\TestType;

class TestType {

    /**
     * @var string
     * @required
     */
    private $type;

    /**
     * @var string
     * @required
     */
    private $description;

    /**
     * @var TestTypeConfig
     * @required
     */
    private $config;

    /**
     * @var TestTypeRules
     * @required
     */
    private $rules;

    /**
     * @var string[string]
     */
    private $computedLogFields = [];

    /**
     * @param string $type
     * @param string $description
     * @param TestTypeConfig $config
     * @param TestTypeRules $rules
     * @param array $computedLogFields
     */
    public function __construct($type, $description, $config, $rules, $computedLogFields) {
        $this->type = $type;
        $this->description = $description;
        $this->config = $config;
        $this->rules = $rules;
        $this->computedLogFields = $computedLogFields;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return TestTypeConfig
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param TestTypeConfig $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }

    /**
     * @return TestTypeRules
     */
    public function getRules() {
        return $this->rules;
    }

    /**
     * @param TestTypeRules $rules
     */
    public function setRules($rules) {
        $this->rules = $rules;
    }

    /**
     * @return array
     */
    public function getComputedLogFields() {
        return $this->computedLogFields;
    }

    /**
     * @param array $computedLogFields
     */
    public function setComputedLogFields($computedLogFields) {
        $this->computedLogFields = $computedLogFields;
    }

}