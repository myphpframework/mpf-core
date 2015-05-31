<?php

namespace MPF;

use MPF\Log\Category;
use MPF\Locale;

class Text extends \MPF\Base
{

    /**
     * Fetches the i18n file and returns the instantiated Text
     *
     * @static
     * @throws Exception\InvalidXml
     * @throws Text\Exception\FileNotFound
     * @param  $filename
     * @return This
     */
    public static function byXml($filename)
    {
        static $files = array();
        $logger = new \MPF\Log\Logger();

        if (!array_key_exists($filename, $files)) {
            $currentCountryAbrv2 = Locale::byENV()->getCountryCode();
            $lang = Locale::byENV()->getLanguageCode();

            $filename = $filename . '.xml';
            foreach (ENV::paths()->i18n() as $path) {
                $file = $path . $lang . '/' . $filename;
                if (file_exists($file)) {
                    $xmlFile = file_get_contents($file);
                    $xmlFile = preg_replace('/&/', '&amp;', $xmlFile);
                    $xml = @simplexml_load_string($xmlFile);
                    if (!$xml) {
                        $exception = new Exception\InvalidXml($filename);

                        $logger->warning($exception->getMessage(), array(
                            'category' => Category::FRAMEWORK | Category::TEXT, 
                            'className' => 'Text',
                            'exception' => $exception
                        ));
                        throw $exception;
                    }

                    $newText = new Text();
                    $texts = array();
                    foreach ($xml->country as $country) {
                        // TODO: Text, must compile all the Country tags of the same languages, if it does not exists in the current we must take the alternative
                        if ($country['code'] == $currentCountryAbrv2) {
                            foreach ($country->text as $text) {
                                $texts[(string) $text['id']] = (string) $text;
                            }
                        }
                    }
                    $newText->add($texts);

                    $files[$filename] = $newText;
                    break;
                }
            }

            // if we still haven't found the file we throw an exception
            if (!array_key_exists($filename, $files)) {
                $exception = new Text\Exception\FileNotFound($filename, ENV::paths()->i18n());

                $logger->emergency($exception->getMessage(), array(
                    'category' => Category::FRAMEWORK | Category::TEXT, 
                    'className' => 'Text',
                    'exception' => $exception
                ));
                throw $exception;
            }
        }

        return $files[$filename];
    }

    /**
     * Contains the texts as an array.
     *
     * @var array
     */
    private $texts = array();

    /**
     * All the test plugins we need to run for each parse.
     *
     * @var Text\Plugin[]
     */
    private static $plugins = null;

    /**
     * Holds the arguments for the plugins
     *
     * @var array
     */
    private $pluginArguments = array();

    private function __construct($pluginsArgs = array())
    {
        $this->pluginArguments = $pluginsArgs;
    }

    /**
     * @param Text[]
     */
    protected function add($texts)
    {
        $this->texts = $this->texts + $texts;
    }

    /**
     * Returns the text for the provided ID
     *
     * @throws Text\Exception\IdNotFound
     * @param string $id
     * @return string
     */
    public function get($id, $pluginsArgs = array())
    {
        if (!array_key_exists($id, $this->texts)) {
            $exception = new Text\Exception\IdNotFound($id, array_keys($this->texts));

            $this->getLogger()->warning($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::TEXT, 
                'className' => 'Text',
                'exception' => $exception
            ));
            $GLOBALS['userErrors'][] = $exception;
        }
        return $this->parseTextId($id, $pluginsArgs);
    }

    /**
     * Returns the text file in json format
     *
     * @return json
     */
    public function toJson()
    {
        return json_encode($this->texts);
    }

    /*
     * Returns the text file in array format
     *
     * @return array
     */

    public function toArray()
    {
        return $this->texts;
    }

    /**
     * Compiles a list of plugins for the text.
     * Populates the property plugins
     */
    private function loadPlugins()
    {
        if (null == self::$plugins) {
            self::$plugins = array();

            // The Text plugins reside in classes/Text/Plugin/, everything in there except the interface will be considered a potential Text plugin
            foreach (ENV::paths()->classes() as $path) {
                $dir = $path . '/MPF/Text/Plugin';
                if (file_exists($dir) && ($handle = opendir($dir))) {
                    while (false !== ($file = readdir($handle))) {
                        $file = substr($file, 0, strpos($file, '.'));
                        if ($file && !preg_match('/^\./', $file) && 'Intheface' != $file) {
                            $pluginClass = 'MPF\Text\Plugin\\' . $file;
                            if (class_exists($pluginClass)) {
                                $plugin = new $pluginClass($file);
                                self::$plugins[$plugin->getPriorityWeight()] = $plugin;
                            }
                        }
                    }
                    closedir($handle);
                }
            }
            ksort(self::$plugins);
        }
    }

    /**
     * Parse the value of the xml text
     *
     * @param string $id
     * @return string
     */
    private function parseTextId($id, $pluginsArgs)
    {
        $this->loadPlugins();

        if (!array_key_exists($id, $this->texts)) {
            return '';
        }

        $parsedTexts = $this->texts[$id];
        if (!empty(self::$plugins)) {
            foreach (self::$plugins as $plugin) {
                // if this plugin as something to parse we execute it
                if (($plugin instanceof Text\Plugin) && $plugin->detect($this->texts[$id])) {
                    $args = array();
                    if (array_key_exists($plugin->getName(), $pluginsArgs)) {
                        $args = $pluginsArgs[$plugin->getName()];
                    }
                    $parsedTexts = $plugin->parse($parsedTexts, $args);
                }
            }
        }

        return $parsedTexts;
    }

}
