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
        
        $this->setHeaders($input);
        header('Content-Type: text/html');
        header('Content-Length: ' . strlen($html));

        return $html;
    }

    public function toArray($output)
    {
        
    }

}
