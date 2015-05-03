<?php

namespace MPF\Log\Storage;

interface Intheface
{
    /**
     * Verifies if the storage media is usable at this time
     * 
     * @return bool
     */
    public function isReady();
    
    /**
     * 
     * @param string $message
     * @param string $level
     * @param bit $category
     * @param string $className
     * @param int $timestamp
     * 
     * @return void
     */
    public function save($message, $level, $category=0, $className="", $timestamp=null);
}
