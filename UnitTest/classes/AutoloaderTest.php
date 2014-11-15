<?php

use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;

require_once(__DIR__ . '/../bootstrap.php');

class AutoloaderTest extends PHPUnit_Framework_TestCase {
    
    public function testPathPriority() {
        $autoloader = new AutoloaderTester();
        
        $autoloader->addPath('/path/to/project/classes/');
        $autoloader->addPath('/path/to/project/subfolder1/classes/');
        $autoloader->addPath('/path/to/project/subfolder1/subfolder2/classes/');

        $autoloader->addFile('/path/to/project/classes/Doctrine/Common/IsolatedClassLoader.php');
        $autoloader->addFile('/path/to/project/subfolder1/classes/Doctrine/Common/IsolatedClassLoader.php');
        $autoloader->addFile('/path/to/project/subfolder1/subfolder2/classes/Doctrine/Common/IsolatedClassLoader.php');
        
        $className = '\Doctrine\Common\IsolatedClassLoader';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/project/subfolder1/subfolder2/classes/Doctrine/Common/IsolatedClassLoader.php';
        $this->assertSame($actual, $expect);
    }
    
    /**
     * Supports PSR-0 except for the convertion of _ into a DIRECTORY_SEPARATOR
     */
    public function testPSR0() {
        $autoloader = new AutoloaderTester();
        
        $autoloader->addPath('/path/to/project/lib/vendor/');

        $autoloader->addFile('/path/to/project/lib/vendor/Doctrine/Common/IsolatedClassLoader.php');
        $autoloader->addFile('/path/to/project/lib/vendor/Symfony/Core/Request.php');
        $autoloader->addFile('/path/to/project/lib/vendor/Zend/Acl.php');
        $autoloader->addFile('/path/to/project/lib/vendor/Zend/Mail/Message.php');
        
        $className = '\Doctrine\Common\IsolatedClassLoader';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/project/lib/vendor/Doctrine/Common/IsolatedClassLoader.php';
        $this->assertSame($actual, $expect);
        $actual = $autoloader->loadClass(substr($className, 1));
        $this->assertSame($actual, $expect);

        $className = '\Symfony\Core\Request';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/project/lib/vendor/Symfony/Core/Request.php';
        $this->assertSame($actual, $expect);
        
        $className = '\Zend\Acl';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/project/lib/vendor/Zend/Acl.php';
        $this->assertSame($actual, $expect);
        
        $className = '\Zend\Mail\Message';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/project/lib/vendor/Zend/Mail/Message.php';
        $this->assertSame($actual, $expect);
    }
    
    /*
     * PSR-4 only partly supported because of unwanted complexity of prefix lookups
     */
    public function testPSR4() {
        $autoloader = new AutoloaderTester();
        
        $autoloader->addPath('/path/to/');
        $autoloader->addPath('/vendor/');
        $autoloader->addPath('/usr/includes/');

        $autoloader->addFile('/path/to/acme-log-writer/lib/File_Writer.php');
        $autoloader->addFile('/path/to/aura-web/src/Response/Status.php');
        $autoloader->addFile('/vendor/Symfony/Core/Request.php');
        $autoloader->addFile('/usr/includes/Zend/Acl.php');

/* Magical character - which adds an unwanted complexity of prefix/namespace lookup
        $className = '\Acme\Log\Writer\File_Writer';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/acme-log-writer/lib/File_Writer.php';
        $this->assertSame($actual, $expect);

        $className = '\Aura\Web\Response\Status';
        $actual = $autoloader->loadClass($className);
        $expect = '/path/to/aura-web/src/Response/Status.php';
        $this->assertSame($actual, $expect);
*/
        
        $className = '\Symfony\Core\Request';
        $actual = $autoloader->loadClass($className);
        $expect = '/vendor/Symfony/Core/Request.php';
        $this->assertSame($actual, $expect);
        
        $className = '\Zend\Acl';
        $actual = $autoloader->loadClass($className);
        $expect = '/usr/includes/Zend/Acl.php';
        $this->assertSame($actual, $expect);
    }
}

class AutoloaderTester extends \MPF\Autoloader {
    protected $files = array();
    
    public function addFile($file) {
        $this->files[] = $file;
    }
    
    protected function loadFile($filepath) {
        if (in_array($filepath, $this->files)) {
            return true;
        }
        return false;
    }
}
