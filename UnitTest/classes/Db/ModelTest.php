<?php
use MPF\ENV;
use MPF\ENV\Paths;

require_once(__DIR__.'/../../bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/Db/Model.php');
require_once(PATH_FRAMEWORK.'classes/Db/Exception/InvalidFieldName.php');
require_once(PATH_FRAMEWORK.'classes/Db/Exception/FieldReadonly.php');
require_once(PATH_FRAMEWORK.'classes/Db/Exception/FieldNotNull.php');
require_once(PATH_FRAMEWORK.'classes/Db/Exception/InvalidFieldLength.php');
require_once(PATH_FRAMEWORK.'classes/Db/Exception/InvalidFieldType.php');
require_once(__DIR__.'/../../ModelExample.php');

class ModelTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testGetField_InvalidFieldName()
    {
        $model = new ModelExample();
        try {
            $model->getField('!.invalid.!');
        }
        catch (MPF\Db\Exception\InvalidFieldName $e) {
            return true;
        }
        $this->fail("The function getField should throw an exception if the fieldName does not exists in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_InvalidFieldName()
    {
        $model = new ModelExample();
        try {
            $model->setField('!.invalid.!', 'Bob');
        }
        catch (MPF\Db\Exception\InvalidFieldName $e) {
            return true;
        }
        $this->fail("The function setField should throw an exception InvalidFieldName if the fieldName does not exists in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_ReadonlyField()
    {
        $model = new ModelExample();
        try {
            $model->setField('id', 'Bob');
        }
        catch (MPF\Db\Exception\FieldReadonly $e) {
            return true;
        }
        $this->fail("The function setField should throw an exception FieldReadonly if the fieldName is readonly in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_FieldNotNull()
    {
        $model = new ModelExample();
        try {
            $model->setField('email', null);
        }
        catch (MPF\Db\Exception\FieldNotNull $e) {
            return true;
        }
        $this->fail("The function setField should throw an exception FieldNotNull if the fieldName is does not support null in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_InvalidFieldLength()
    {
        $model = new ModelExample();
        try {
            $model->setField('testLength', '123456');
        }
        catch (MPF\Db\Exception\InvalidFieldLength $e) {
            return true;
        }
        $this->fail("The function setField should throw an exception InvalidFieldLength if the field value exceed length specified in the model");
    }

    /**
     * @return void
     */
    public function testSetField_InvalidFieldType()
    {
        $model = new ModelExample();
        try {
            $model->setField('testLength', (object)array('bob'=>1));
        }
        catch (MPF\Db\Exception\InvalidFieldType $e) {
            return true;
        }
        $this->fail("The function setField should throw an exception InvalidFieldType if a basic type is not passed");
    }

    public function testToJson_valid()
    {
        $model = new ModelExample();
        $model->setField('testLength', "test");
        $json = $model->toJson();
        $this->assertEquals('{"id":null,"creationDate":null,"lastLogin":null,"email":null,"testLength":"test","color":null,"className":"ModelExample"}', $json, "The function toJson should of returned the proper string for the test");
    }

    public function testFromJson()
    {
        $model = new ModelExample();
        $model->setField('testLength', "test");
        $json = $model->toJson();
        $model = ModelExample::fromJson($json);
        $newJson = $model->toJson();
        $this->assertEquals($newJson, $json, "The function fromJson should of returned the proper string for the test");
    }
}