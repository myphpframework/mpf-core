<?php

namespace MPF\REST;

abstract class Parser {
    /**
     * Converts input to proper output
     * @param array $input
     * @return string
     */
    abstract public function toOutput($input);

    /**
     * Converts output to proper input array
     * @param string $output
     * @return array
     */
    abstract public function toArray($output);

    protected $serviceName;
    protected $action;

    public function __construct($serviceName, $action) {
        $this->serviceName = $serviceName;
        $this->action = $action;
    }
}
