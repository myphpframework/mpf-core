<?php

namespace MPF\REST;

abstract class Parser
{

    /**
     * Converts input to proper output
     * @param array $input
     * @return string
     */
    abstract public function getOutput($input, $serviceName="", $actionName="");

    /**
     * Converts output to proper input array
     * @param string $output
     * @return array
     */
    abstract public function toArray($output);
}
