<?php
namespace MPF\Text\Plugin;

interface Intheface
{
    /**
     * Every text plugin needs to have a way to detect if they need to run or not.
     * To detect if they need to parse this text or not
     * 
     * This is done by returning a Regexp
     * 
     * @param string $text
     * @return string/regexp
     */
    public function detect($text);

    /**
     * Parses the text according to the plugin
     *
     * @param array $args
     * @return string
     */
    public function parse($text, $args);

    /**
     * This returns the priority weight of which order the plugins are executed
     */
    public function getPriorityWeight();
}