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
    
    protected function setHeaders($input)
    {
        if (array_key_exists('errors', $input)) {
            $errorCode = $input['errors'][0]['code'];
            $message = $input['errors'][0]['msg'];
            header("HTTP/1.0 $errorCode $message");
        }
    }
}
