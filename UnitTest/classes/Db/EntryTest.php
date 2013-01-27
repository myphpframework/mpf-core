<?php
use MPF\ENV;
use MPF\ENV\Paths;

require_once(__DIR__.'/../../bootstrap.php');
require_once(PATH_MPF_CORE.'classes/MPF/Db/Model.php');
require_once(PATH_MPF_CORE.'classes/MPF/Db/Exception/InvalidFieldName.php');
require_once(PATH_MPF_CORE.'classes/MPF/Db/Exception/FieldReadonly.php');
require_once(PATH_MPF_CORE.'classes/MPF/Db/Exception/FieldNotNull.php');
require_once(PATH_MPF_CORE.'classes/MPF/Db/Exception/InvalidFieldLength.php');
require_once(PATH_MPF_CORE.'classes/MPF/Db/Exception/InvalidFieldType.php');
require_once(__DIR__.'/../../ModelExample.php');

class EntryTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize() {
        $entry = new \MPF\Db\Entry(array(
            'field1' => 1,
            'field2' => 'test2',
            'field3' => 'test3'
        ));

        $entry['field4'] = 'test4';

        $this->assertEquals(1, $entry['field1']);
        $this->assertEquals('test2', $entry['field2']);

        $md5 = $entry->getMD5();
        $data = $entry->serialize();
        $entry->unserialize($data);

        $this->assertEquals($md5, $entry->getMD5());
        foreach ($entry as $name => $value) {
            $this->assertEquals($entry[$name], $value);
        }

        if (!array_key_exists('field5', $entry)) {
            $entry->set('field5', $entry['field4']);
            $this->assertEquals('test4', $entry['field5']);
        }

        unset($entry['field5']);
        $this->assertFalse($entry->offsetExists('field5'));
    }
}