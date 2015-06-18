<?php

namespace MPF\REST\Parser;

class Json extends \MPF\REST\Parser
{

    public function getOutput($input, $serviceName="", $actionName="")
    {
        $response = (!$input ? '{}' : json_encode($input));

        if (!$response) {
            throw new \Exception(\MPF\Text::byXml('mpf_exception')->get('serviceJsonParser', array('Replace' => array('errorNumber' => json_last_error()))), 500);
        }
        
        if (array_key_exists('callback', $_REQUEST)) {
            header('Content-Type: application/javascript');
            return $_REQUEST['callback'] . '(' . $response . ');';
        }
        
        header('Content-Type: application/json');
        header('Content-Length: '.strlen($response));
        return $response;
    }

    public function toArray($output)
    {
        if (is_string($output)) {
            return json_decode($ouput);
        }

        return $ouput;
    }

}
