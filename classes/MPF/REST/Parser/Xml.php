<?php

namespace MPF\REST\Parser;

class Xml extends \MPF\REST\Parser {

    public function toOutput($input) {
        if (!is_array($input)) {
            $input = array();
        }

        if (!array_key_exists('errors', $input)) {
            if ($this->action) {
                $input = array($this->action => $input);
            } else {
                $isListOfItems = true;
                foreach ($input as $key => $value) {
                    if (!is_int($key)) {
                        $isListOfItems = false;
                    }
                }

                if ($isListOfItems) {
                    $input = array($this->serviceName.'s' => $input);
                } else {
                    $input = array($this->serviceName => $input);
                }
            }
        }
        $response = $this->arrayToXml($input);

        header('Content-Type: text/xml');
        header('Content-Length: '.strlen($response));

        return $response;
    }

    /**
     * @param array $array the array to be converted
     * @param string? $rootElement if specified will be taken as root element, otherwise defaults to <root>
     * @param SimpleXMLElement? if specified content will be appended, used for recursion
     * @return string XML version of $array
     */
    private function arrayToXml($array, $rootElement = null, $xml = null) {
        $_xml = $xml;

        if ($_xml === null) {
            $_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE response SYSTEM "http://myphpframework.com/dtd/rest/response.dtd" ><response />');
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // if its a numeric index we take the parent index and remove the last character (the plurial "s")
                if (is_int($key)) {
                    $key = substr($rootElement, 0, -1);
                }

                $this->arrayToXml($value, $key, $_xml->addChild($key));
            } else {
                $_xml->addChild($key, $value);
            }
        }

        return $_xml->asXML();
    }

    public function toArray($output) {

    }

}
