<?php

namespace MPF\Log\Storage;

use MPF\Log\Category;
use MPF\Config;

class File extends \MPF\Base implements \MPF\Log\Storage\Intheface
{
    public function isReady()
    {
        
    }
    
    public function save($message, $level, $category=0, $className="", $timestamp=null)
    {
        if (!Config::get('settings')->logger->file) {
            throw new \Exception('Missing config file at '.PATH_SITE.'/config/settings.ini');
        }
        
        if (!is_writable(Config::get('settings')->logger->file)) {
            throw new \Exception('Log file "'.Config::get('settings')->logger->file.'" not writable');
        }

        if ($timestamp === null) {
            $microseconds = explode('.', sprintf('%0.4f', microtime(true)));
            $timestamp = date('Y-m-d H:i:s.' . $microseconds[1]);
        }
        
        $line  = '[' . $timestamp . ']';
        $line .= '[' . getmypid() . ']';
        $line .= '[' . str_pad(strtoupper($level), 10) . ']';
        if ($category && Category::NONE !== $category) {
            $line .= '[' . $this->getCategoryText($category) . ']';
        }
        
        if ($className) {
            $line .= '[' . $className . ']';
        }
        $line .= ' '.str_replace("\n", ' ', $message);
        $line .= PHP_EOL;
        
        file_put_contents(Config::get('settings')->logger->file, $line, FILE_APPEND);
    }

    /**
     * Converts the bit into text for the log
     *
     * @param  bit $category
     * @return string
     */
    private function getCategoryText($category)
    {
        $categoryText = array();
        if (Category::FRAMEWORK == (Category::FRAMEWORK & $category)) {
            $categoryText[] = 'FRAMEWORK';
        }

        if (Category::DATABASE == (Category::DATABASE & $category)) {
            $categoryText[] = 'DATABASE';
        }

        if (Category::TEMPLATE == (Category::TEMPLATE & $category)) {
            $categoryText[] = 'TEMPLATE';
        }

        if (Category::ENVIRONMENT == (Category::ENVIRONMENT & $category)) {
            $categoryText[] = 'ENVIRONMENT';
        }

        if (Category::TEXT == (Category::TEXT & $category)) {
            $categoryText[] = 'TEXT';
        }

        if (Category::CONFIG == (Category::CONFIG & $category)) {
            $categoryText[] = 'CONFIG';
        }

        if (Category::SERVICE == (Category::SERVICE & $category)) {
            $categoryText[] = 'SERVICE';
        }
        return implode('.', $categoryText);
    }

}
