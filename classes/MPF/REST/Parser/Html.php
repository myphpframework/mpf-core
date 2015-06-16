<?php

namespace MPF\REST\Parser;

use MPF\Template;

class Html extends \MPF\REST\Parser
{

    public function getOutput($input, $serviceName="", $actionName="")
    {
        $response = Template::getFile('rest-parser');

        $response->response = $input;
        $html = $response->parse();
        
        if (array_key_exists('callback', $_REQUEST)) {
            header('Content-Type: application/javascript');
            return $_REQUEST['callback'] . '(' . $response . ');';
        }
        
        header('Content-Length: ' . strlen($html));
        header('Content-Type: text/html');
        return $html;
    }

    public function toArray($output)
    {
        
    }

}
