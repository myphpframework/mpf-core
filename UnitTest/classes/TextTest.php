<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Config;
use MPF\Text;

require_once(__DIR__.'/../bootstrap.php');

class TextTest extends PHPUnit_Framework_TestCase
{
    public function testByXml_FileNotFound()
    {
        $this->setExpectedException('MPF\Text\Exception\FileNotFound');
        $text = Text::byXml('unknownfile');
        $this->fail('The exception "Exception_Text_FileNotFound" should be thrown if we cant find the file.');
    }

    public function testByXml_InvalidXml()
    {
        $this->setExpectedException('MPF\Exception\InvalidXml');
        $text = Text::byXml('invalidXml');
        $this->fail('The exception "Exception_InvalidXml" should be thrown if we cant parse the xml with simplexml_load_file.');
    }

    public function testByXml_returnText()
    {
        $text = Text::byXml('test');
        $this->assertInstanceOf('MPF\Text', $text, 'The function byXml should always return an object Text.');
    }

    public function testReplaceMarker()
    {
        $text = Text::byXml('test')->get('testReplace', array('Replace'=>array('name' => 'bob')));
        $this->assertEquals('test the bob plz!', $text, 'The marker @name@ should have been replaced by "bob" and wasnt.');
    }

    public function testGetId_validId()
    {
        $text = Text::byXml('test');
        $this->assertEquals('test value 2', $text->get('test2'), 'The value of the id "test2" should have been "test value 2".');
    }

    public function testBBCode_parse()
    {
        $text = Text::byXml('test');
        $test = $text->get('bbcodeTest');
        $this->assertEquals('test the <b>name</b> plz!', $test, 'The value of the id "bbcodeTest" should have been "test the <b>name</b> plz!" not "'.$test.'".');
    }

    public function testBBCode_parseComplexDate()
    {
        $text = Text::byXml('test');
        $test = $text->get('bbcodeTestDate');
        $this->assertEquals('this is an 1969-12-31 date...', $test, 'The value of the id "bbcodeTestDate" should have been "this is an 1969-12-31 date..." not "'.$test.'".');
    }

    public function testBBCode_parseComplexDiv()
    {
        $text = Text::byXml('test');
        $test = $text->get('bbcodeTestDiv');
        $this->assertEquals('this is an <div class="bob">hullo</div> div...', $test, 'The value of the id "bbcodeTestDiv" should have been "this is an <div class="bob">hullo</div> div..." not "'.$test.'".');
    }
    /*
    public function testBBCode_parseComplexInception()
    {
        $text = Text::byXml('test');
        $test = $text->get('bbcodeTestInception');
        $this->assertEquals('this is an <div class="bob">hm or<span class="inception">hullo</span>test</div> div...', $test, 'The value of the id "bbcodeTestInception" should have been "this is an <div class="bob">hm or<span class="inception">hullo</span>test</div> div..." not "'.$test.'".');
    }
     */
}