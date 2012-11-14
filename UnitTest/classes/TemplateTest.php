<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Template;

require_once(__DIR__.'/../bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/Template/Marker/Intheface.php');
require_once(PATH_FRAMEWORK.'classes/Template/Marker.php');
require_once(PATH_FRAMEWORK.'classes/Template/Marker/Nocache.php');
require_once(PATH_FRAMEWORK.'classes/Template.php');

class TemplateTest extends PHPUnit_Framework_TestCase
{
    public function testGetText()
    {
        $template = Template::getFile('gettext');
        $text = $template->parse();
        $this->assertEquals('this is a get text', $text, 'Did not return the right text');
    }
    
    public function testSetContent()
    {
        $template = Template::getFile('setcontent');
        $template->setContent('bobid', 'this is content');
        $text = $template->parse();
        $this->assertEquals('this is content', $text, 'Did not return the right content');
    }
    
    public function testStartContent()
    {
        $level = ob_get_level();
        $template = Template::getFile('startcontent.phtml');
        $template->startContent();
        echo 'Test 1';
        $template->stopContent();
        $text = $template->parse();
        $this->assertEquals('Test 1', $text, 'Did not return the right content');
    }
    
    public function testGetMarkers_NoImplement()
    {
        $template = Template::getFile('noImplementMarkerTest');
        $markers = $template->getMarkers();
        $this->assertEmpty($markers, 'The markers should return an empty array since the marker did not implement the interface Template_Marker_Interface.');
    }

    public function testGetMarkers_UnknownMarker()
    {
        $template = Template::getFile('unknownMarkerTest');
        $markers = $template->getMarkers();
        $this->assertEmpty($markers, 'The markers should return an empty array since the marker\'s class could not be found.');
    }

    public function testGetMarkers_NoCache()
    {
        $template = Template::getFile('noCacheMarker');
        $markers = $template->getMarkers();
        foreach ($markers as $marker)
        {
            $this->assertTrue(($marker instanceof \MPF\Template\Marker), 'The markers should always extends the Marker class.');
        }
        $this->assertTrue(!empty($markers), 'The markers should not be empty.');
        $template->parse();
    }

    public function testGetFilename_doesNotChange()
    {
        $template = Template::getFile('unknownMarkerTest');
        $template->parse();
        $this->assertEquals('unknownMarkerTest.phtml', $template->getFilename(), 'The file name should not be changing.');
    }

    public function testGetTemplateId_doesNotChange()
    {
        $template = Template::getFile('unknownMarkerTest');
        $templateId = $template->getTemplateId();
        $template->parse();
        $this->assertEquals($templateId, $template->getTemplateId(), 'The template id should not be changing.');
    }
}
