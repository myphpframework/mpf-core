<?php

use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;

require_once(__DIR__ . '/../bootstrap.php');

class ENVTest extends PHPUnit_Framework_TestCase {

    public function testENV_init() {
        $paths = ENV::init(ENV::TYPE_DEVELOPMENT);
        $this->assertTrue(($paths instanceof MPF\ENV\Paths), 'Init should be returning the paths (not really but I have to test it...)');
        $this->assertTrue(ENV::TYPE_DEVELOPMENT == ENV::getType(), 'Type should be development)');
    }

    public function testENV_clearAllCache() {
        while ($info = ENV::clearAllCache()) {
        }

        $this->assertFalse(file_exists(Config::get('settings')->template->cache->dir), 'After clearing all cache the template dir should be deleted in the cache directory.');
        $this->assertFalse(file_exists(Config::$cache_path), 'After clearing all cache the config dir should be deleted in the cache directory.');
    }

    public function testENV_boostrapExceptions() {
        $this->setExpectedException('MPF\Bootstrap\Exception\UnsupportedType');
        ENV::bootstrap('badType');
        $this->fail('The exception "Bootstrap\Exception\UnsupportedType" should be thrown if we cant find the file.');
    }

    public function testENV_boostrapTemplate() {
        $bootstrap = ENV::bootstrap(ENV::TEMPLATE);
        $this->assertTrue($bootstrap->isInitialized(), 'Bootstraping the template environment should be initiated.');
    }

    public function testPathsAdd_Duplicates() {
        ENV::paths()->add(Paths::FOLDER_CONFIG, PATH_SITE . 'config/');
        ENV::paths()->add(Paths::FOLDER_CONFIG, PATH_SITE . 'config/');

        // Loop thru the paths and verify that there is no duplicates
        $pathsCount = array();
        foreach (ENV::paths()->configs() as $path) {
            if (!array_key_exists($path, $pathsCount)) {
                $pathsCount[$path] = 0;
            }
            $pathsCount[$path]++;
        }

        foreach ($pathsCount as $path => $count) {
            $this->assertLessThan(2, $count, 'The paths should never contain duplicates, path "' . $path . '" as ' . $count . ' duplicates.');
            $this->assertEquals(1, $count, "The path should have been found atleast once.");
        }
    }

    public function testPaths_Classes_Duplicates() {
        $pathsCount = array();
        foreach (ENV::paths()->classes() as $path) {
            if (!array_key_exists($path, $pathsCount)) {
                $pathsCount[$path] = 0;
            }
            $pathsCount[$path]++;
        }

        foreach ($pathsCount as $path => $count) {
            $this->assertLessThan(2, $count, 'The paths should never contain duplicates, path "' . $path . '" as ' . $count . ' duplicates.');
            $this->assertEquals(1, $count, "The path should have been found atleast once.");
        }
    }

    public function testPaths_I18n_Duplicates() {
        $pathsCount = array();
        foreach (ENV::paths()->i18n() as $path) {
            if (!array_key_exists($path, $pathsCount)) {
                $pathsCount[$path] = 0;
            }
            $pathsCount[$path]++;
        }

        foreach ($pathsCount as $path => $count) {
            $this->assertLessThan(2, $count, 'The paths should never contain duplicates, path "' . $path . '" as ' . $count . ' duplicates.');
            $this->assertEquals(1, $count, "The path should have been found atleast once.");
        }
    }

    public function testPaths_Includes_Duplicates() {
        $pathsCount = array();
        foreach (ENV::paths()->includes() as $path) {
            if (!array_key_exists($path, $pathsCount)) {
                $pathsCount[$path] = 0;
            }
            $pathsCount[$path]++;
        }

        foreach ($pathsCount as $path => $count) {
            $this->assertLessThan(2, $count, 'The paths should never contain duplicates, path "' . $path . '" as ' . $count . ' duplicates.');
            $this->assertEquals(1, $count, "The path should have been found atleast once.");
        }
    }

    public function testPaths_Templates_Duplicates() {
        $pathsCount = array();
        foreach (ENV::paths()->templates() as $path) {
            if (!array_key_exists($path, $pathsCount)) {
                $pathsCount[$path] = 0;
            }
            $pathsCount[$path]++;
        }

        foreach ($pathsCount as $path => $count) {
            $this->assertLessThan(2, $count, 'The paths should never contain duplicates, path "' . $path . '" as ' . $count . ' duplicates.');
            $this->assertEquals(1, $count, "The path should have been found atleast once.");
        }
    }

    public function testReturnTypes() {
        $this->assertTrue(is_array(ENV::paths()->buckets()), 'The functions of the object Paths should always return an array.');
        $this->assertTrue(is_array(ENV::paths()->classes()), 'The functions of the object Paths should always return an array.');
        $this->assertTrue(is_array(ENV::paths()->configs()), 'The functions of the object Paths should always return an array.');
        $this->assertTrue(is_array(ENV::paths()->i18n()), 'The functions of the object Paths should always return an array.');
        $this->assertTrue(is_array(ENV::paths()->templates()), 'The functions of the object Paths should always return an array.');
    }

    public function testAddPath() {
        ENV::paths()->add(ENV\Paths::FOLDER_INCLUDE, '/tmp');

        $foundAddedPath = false;
        foreach (ENV::paths()->includes() as $path) {
            if ($path == '/tmp') {
                $foundAddedPath = true;
            }
        }
        $this->assertTrue($foundAddedPath, 'We should find the path we juste added.');
    }

    public function testAddInvalidPath() {
        $this->setExpectedException('\Exception');
        ENV::paths()->add(ENV\Paths::FOLDER_BUCKET, 'invalidpath');
        $this->fail('Invalid paths should throw an exception');
    }

    public function testAddInvalidTypePath() {
        $this->setExpectedException('\Exception');
        ENV::paths()->add('invalidtype', '/');
        $this->fail('Invalid path TYPE should throw an exception');
    }

    public function testNewPaths() {
        ENV\Paths::$paths = array();
        $paths = new ENV\Paths();
        $includePaths = ENV::paths()->includes();
        $this->assertTrue(PATH_MPF_CORE.'includes/' == $includePaths[0], 'After refetching all paths core include should technically be the only path in includes');
    }

    public function testAllPath() {
        ENV::paths()->add(ENV\Paths::FOLDER_INCLUDE, '/tmp');
        ENV::paths()->addAll(PATH_MPF_CORE);
        $includePaths = ENV::paths()->includes();
        $this->assertTrue(PATH_MPF_CORE.'includes/' == $includePaths[0], 'If all paths from core were added successfully the path of core should be first in the list');
    }

    public function testSetNullType() {
        ENV::setType(null);
        $this->setExpectedException('\Exception');
        ENV::getType();
        $this->fail('If the evironment is null getType should throw an exception.');
    }

}
