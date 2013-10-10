<?php

namespace MPF\REST\Parser;

class Json extends \MPF\REST\Parser {

    public function toOutput($input) {
        $response = (!$input ? '{}' : json_encode($input));

        header('Content-Type: application/json');
        //header('Content-Length: '.strlen($response));

        if (array_key_exists('callback', $_REQUEST)) {
            return $_REQUEST['callback'].'('.$response.');';
        }
        return $response;
    }

    public function toArray($output) {
        if (is_string($output)) {
            return json_decode($ouput);
        }

        return $ouput;
    }
}
