<?php

namespace MPF;

use MPF\Logger;
use MPF\Template\Marker;
use MPF\ENV;
use MPF\Text;

class Template
{

    /**
     * Contains an array of templates output to overwrite stuff
     *
     * @var text/html[]
     */
    protected $contents = array();

    /**
     * Actual file name of the current template
     *
     * @var string
     */
    protected $filename = '';

    /**
     * The path of the nearest filename
     * This is to avoid searching for it more than once.
     *
     * @var string
     */
    protected $nearestPath = '';

    /**
     * By default the template is cachable
     */
    protected $isCacheable = true;

    /**
     * Content of the file template Not Parsed
     *
     * @var string
     */
    public $rawTemplate = '';

    /**
     * Parent template
     *
     * @var Template
     */
    public $parent = null;

    /**
     *
     * @var Marker[]
     */
    public $markers = array();

    /**
     * When the template system is bootstrapped this is
     * set as global exception handler
     *
     * @param Exception $e
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $GLOBALS['userErrors'][] = new \Exception($errstr, $errno);
    }

    /**
     * Instantiate the template by its file name
     *
     * @param string $filename
     * @param Template $parent
     * @param bool $exitIfCached
     * @return Template
     */
    public static function getFile($filename, $parent = null, $exitIfCached = false)
    {
        $template = new Template($filename, $parent);
        if ($template->isCached() && $exitIfCached) {
            Logger::Log('Template(' . $filename . ')', 'isCached, echo parse and returning null', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);
            echo $template->parse();
            exit;
        }
        return $template;
    }

    public static function clearCache()
    {
        if (null === shell_exec('rm -rf ' . escapeshellarg(Config::get('settings')->template->cache->dir) . '  && echo "success"')) {
            return false;
        }
        return true;
    }

    private function __construct($filename, Template $parent = null)
    {
        $this->parent = $parent;
        $this->filename = $filename;

        if ($this->isCached()) {
            $this->rawTemplate = $this->retrieveCache();
            $this->initMarkers();
        } elseif (!$this->parent || !array_key_exists($this->filename, $this->parent->contents)) {
            $filePath = $this->getNearestFilePath();
            if ($filePath && $this->getFilename() != $filePath) {
                $this->rawTemplate = file_get_contents($filePath);
                $this->initMarkers();
            }
        }
    }

    /**
     * Retrieves the text for the template
     *
     * @param string $id
     * @param string $filename
     * @return string
     */
    protected function getText($id, $filename = '')
    {
        $filename = ($filename ? $filename : $this->filename);
        return Text::byXml($filename)->get($id);
    }

    /**
     * Initiate the markers for the template
     */
    protected function initMarkers()
    {
        $this->markers = $this->getMarkers();
        foreach ($this->markers as $marker) {
            $marker->init($this, $this->rawTemplate);
        }
    }

    /**
     *
     *
     * @param string $content
     * @param string $id
     */
    public function setContent($content, $id = 'content')
    {
        $this->contents[$id] = $content;
    }

    /**
     * ob_start()
     */
    public function startContent()
    {
        ob_start();
    }

    /**
     * ob_get_contents and puts it in the content
     */
    public function stopContent()
    {
        $this->setContent(ob_get_contents());
        ob_end_clean();
        return $this;
    }

    public function evalError($errno, $errstr, $errfile, $errline)
    {
        if (ENV::getType() == 'development') {
            $GLOBALS['userErrors'][] = new \Exception($errstr . ' in ' . $this->getFilename() . " on line " . $errline, $errno);
        }
    }

    /**
     * Compiles the templates and returns its output
     *
     * @return string
     */
    public function parse()
    {
        if ($this->parent && array_key_exists($this->filename, $this->parent->contents)) {
            return $this->parent->contents[$this->filename];
        }

        $oldErrorHandler = set_error_handler(array($this, 'evalError'));

        if ($this->isCached() && !preg_match("/\<\?/", $this->rawTemplate)) {
            ob_start();
            eval("?>" . $this->rawTemplate . "<?");
            $templateOutput = ob_get_contents();
            ob_end_clean();
            return $templateOutput;
        }

        // TODO: in developpement mode (ENV::getType() or ENV::AnalyzeTemplate()) we must analyse the template raw code to enforce a strict php coding standards in templates
        // First eval of the code which can include sub templates
        ob_start();
        eval("?>" . $this->rawTemplate . "<?");
        $templateOutput = ob_get_contents();
        ob_end_clean();

        $templateCache = $templateOutput;
        foreach ($this->markers as $marker) {
            $marker->execute($this, $templateOutput, $templateCache);
        }

        // Second eval of the code which execute the sub templates code (If there was a "no cache" on certain pieces)
        ob_start();
        eval("?>" . $templateOutput . "<?");
        $templateOutput = ob_get_contents();
        ob_end_clean();

        if ($this->isCacheable() && $templateCache) {
            $this->cacheOutput($templateCache);
        }

        if ($oldErrorHandler) {
            set_error_handler($oldErrorHandler);
        } else {
            set_error_handler(function () {
                
            });
        }

        return $templateOutput;
    }

    /**
     * Return the filename of the template
     *
     * @return string
     */
    public function getFilename()
    {
        $pathinfo = pathinfo($this->filename);
        if (array_key_exists('extension', $pathinfo)) {
            return $this->filename;
        }
        return $this->filename . '.phtml';
    }

    /**
     * Determines if the template is cached
     *
     * @return boolean
     */
    public function isCached()
    {
        static $isCached = null;

        if (null === $isCached) {
            $file = $this->getCachePath() . $this->getTemplateId();
            if (Config::get('settings')->template->cache->enabled && file_exists($file)) {
                $isCached = true;
            } else {
                $isCached = false;
            }
        }
        return $isCached;
    }

    /**
     * Sets if the template is cachable, it is by default
     */
    public function setCacheable($isCacheable)
    {
        $this->isCacheable = $isCacheable;
    }

    /**
     * Verifies if the template is cacheable
     *
     * @return bool
     */
    public function isCacheable()
    {
        $isCacheable = ($this->isCacheable && Config::get('settings')->template->cache->enabled && !$this->isCached());
        if (!$isCacheable) {
            Logger::Log('Template(' . $this->filename . ')', ' template is not cacheable: ' . "\n"
                    . 'Property: ' . ($this->isCacheable ? 'TRUE' : 'FALSE') . "\n"
                    . 'Config: ' . (Config::get('settings')->template->cache->enabled ? 'TRUE' : 'FALSE') . "\n"
                    . 'Already cached: ' . ($this->isCached() ? 'TRUE' : 'FALSE') . "\n"
                    , Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE
            );
        }
        return $isCacheable;
    }

    /**
     * Return the path where to cache templates
     *
     * @return string
     */
    protected function getCachePath()
    {
        return Config::get('settings')->template->cache->dir . Session::getLocale() . '/';
    }

    /**
     * Returns the id of the template
     *
     * @return string
     */
    public function getTemplateId()
    {
        return md5(ENV::paths()->getCurrentDir() . $this->getNearestFilePath());
    }

    /**
     * Caches the template on the file system
     */
    protected function cacheOutput($output)
    {
        $file = $this->getCachePath();
        if (!file_exists($file)) {
            @mkdir($file);
        }
        $file .= $this->getTemplateId();
        @file_put_contents($file, $output);
        Logger::Log('Template(' . $this->filename . ')', ' caching template at "' . $file . '"', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);
    }

    /**
     * Retrieves the cached output
     *
     * @return string
     */
    protected function retrieveCache()
    {
        $file = $this->getCachePath() . $this->getTemplateId();
        return file_get_contents($file);
    }

    /**
     * Verifies the provided content for markers and returns the markers
     * associated with em.
     *
     * @return Marker[]
     */
    public function getMarkers()
    {
        $markers = array();

        // Match all markers that have closing tags first
        preg_match_all("#\{([\w]+)(.*?)\}(.*?)\{/\\1\}#s", $this->rawTemplate, $rawMatchs);
        foreach ($rawMatchs[1] as $i => $marker) {
            $markerName = 'MPF\Template\Marker\\' . ucfirst(strtolower($marker));
            if (class_exists($markerName) && in_array('MPF\Template\Marker\Intheface', class_implements($markerName))) {
                $markers[] = new $markerName($rawMatchs[0][$i], $rawMatchs[2][$i], $rawMatchs[3][$i]);
            }
        }

        // Match all the single markers
        preg_match_all("#\{([\w]+)(.*?)/\}#s", $this->rawTemplate, $rawMatchs);
        foreach ($rawMatchs[1] as $i => $marker) {
            // Single markers are not repeatable, if its the same arguments its the same output
            if (Marker::isMarkerUnique($rawMatchs[0][$i], $markers)) {
                $markerName = 'MPF\Template\Marker\\' . ucfirst(strtolower($marker));
                if (class_exists($markerName) && in_array('MPF\Template\Marker\Intheface', class_implements($markerName))) {
                    $markers[] = new $markerName($rawMatchs[0][$i], $rawMatchs[2][$i]);
                }
            }
        }

        return $markers;
    }

    /**
     * Retrieves the nearest content of a given file name
     * based on the ENV:paths->templates(), Returns the file name of the
     * template if the template is not found
     *
     * @return string
     */
    public function getNearestFilePath()
    {
        // if we already found the nearest path for the file name we return it
        if ($this->nearestPath) {
            return $this->nearestPath . $this->getFilename();
        }

        foreach (ENV::paths()->templates() as $path) {
            if (file_exists($path . $this->getFilename())) {
                $this->nearestPath = $path;
                return $path . $this->getFilename();
            }
        }
        return $this->getFilename();
    }

    /**
     * Same as parse()
     *
     * @see parse
     * @return string
     */
    public function __toString()
    {
        Logger::Log('Template(' . $this->filename . ')', '__toString() thus parsing template', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);
        return $this->parse();
    }

}
