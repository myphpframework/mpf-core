<?php
use MPF\ENV;
use MPF\ENV\Paths;

require_once(__DIR__.'/../../bootstrap.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Model.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/InvalidFieldName.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/FieldReadonly.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/FieldNotNull.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/InvalidFieldLength.php');
require_once(PATH_FRAMEWORK.'classes/MPF/Db/Exception/InvalidFieldType.php');
require_once(__DIR__.'/../../ModelExample.php');

class ModelTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return void
     */
    public function testGetField_InvalidFieldName()
    {
        $this->setExpectedException('MPF\Db\Exception\InvalidFieldName');
        $model = new ModelExample();
        $model->getField('!.invalid.!');
        $this->fail("The function getField should throw an exception if the fieldName does not exists in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_InvalidFieldName()
    {
        $this->setExpectedException('MPF\Db\Exception\InvalidFieldName');
        $model = new ModelExample();
        $model->setField('!.invalid.!', 'Bob');
        $this->fail("The function setField should throw an exception InvalidFieldName if the fieldName does not exists in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_ReadonlyField()
    {
        $this->setExpectedException('MPF\Db\Exception\FieldReadonly');
        $model = new ModelExample();
        $model->setField('id', 'Bob');
        $this->fail("The function setField should throw an exception FieldReadonly if the fieldName is readonly in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_FieldNotNull()
    {
        $this->setExpectedException('MPF\Db\Exception\FieldNotNull');
        $model = new ModelExample();
        $model->setField('email', null);
        $this->fail("The function setField should throw an exception FieldNotNull if the fieldName is does not support null in the Model");
    }

    /**
     * @return void
     */
    public function testSetField_FieldNull()
    {
        $model = new ModelExample();
        $model->setField('testLength', null);
    }

    /**
     * @return void
     */
    public function testSetField_InvalidFieldLength()
    {
        $this->setExpectedException('MPF\Db\Exception\InvalidFieldLength');
        $model = new ModelExample();
        $model->setField('testLength', '123456');
        $this->fail("The function setField should throw an exception InvalidFieldLength if the field value exceed length specified in the model");
    }

    /**
     * @return void
     */
    public function testSetField_InvalidFieldType()
    {
        $this->setExpectedException('MPF\Db\Exception\InvalidFieldType');
        $model = new ModelExample();
        $model->setField('testLength', (object)array('bob'=>1));
        $this->fail("The function setField should throw an exception InvalidFieldType if a basic type is not passed");
    }

    public function testToJson_valid()
    {
        $model = new ModelExample();
        $model->setField('testLength', "test");
        $json = $model->toJson();
        $this->assertEquals('{"id":null,"integerTest":null,"creationDate":null,"mydate":null,"mydatetime":null,"lastLogin":null,"email":null,"testLength":"test","color":null}', $json, "The function toJson should of returned the proper string for the test");
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

    public function testGetSaltType() {
        $model = new ModelExample();
        $model->setField('password', 'test');
    }

    public function testComparisionValues() {
        $model = new ModelExample();
        $model->setField('lastLogin', '2012-01-01 01:01:01');
        $field = $model->getField('lastLogin');
        $this->assertTrue($field->isGreaterThan('2000-01-01 01:01:01'));
        $this->assertTrue($field->isGreaterThanOrEqual('2012-01-01 01:01:01'));
        $this->assertTrue($field->isLessThan('2013-01-01 01:01:01'));
        $this->assertTrue($field->isLessThanOrEqual('2012-01-01 01:01:01'));
        $this->assertTrue($field->isEqual('2012-01-01 01:01:01'));

        $field->setOperator('>');
        $this->assertTrue($field->matches('2000-01-01 01:01:01'));
        $field->setOperator('>=');
        $this->assertTrue($field->matches('2012-01-01 01:01:01'));
        $field->setOperator('<');
        $this->assertTrue($field->matches('2013-01-01 01:01:01'));
        $field->setOperator('<=');
        $this->assertTrue($field->matches('2012-01-01 01:01:01'));
        $field->setOperator('=');
        $this->assertTrue($field->matches('2012-01-01 01:01:01'));

        $field = $model->getField('integerTest');
        $field->setValue(100);
        $this->assertTrue($field->isGreaterThan(99));
        $this->assertTrue($field->isGreaterThanOrEqual(100));
        $this->assertTrue($field->isLessThan(101));
        $this->assertTrue($field->isLessThanOrEqual(100));
        $this->assertTrue($field->isEqual(100));

        $field->setOperator('>');
        $this->assertTrue($field->matches(99));
        $field->setOperator('>=');
        $this->assertTrue($field->matches(100));
        $field->setOperator('<');
        $this->assertTrue($field->matches(101));
        $field->setOperator('<=');
        $this->assertTrue($field->matches(100));
        $field->setOperator('=');
        $this->assertTrue($field->matches(100));

        $this->assertEquals('', $field->getPasswordType());
        $this->assertEquals('', $field->getSaltType());

        $field = $model->getField('id');
        $this->assertTrue($field->isPrimaryKey());
        $this->assertFalse($field->isNullable());

        $field = $model->getField('email');
        $this->assertEquals('LIKE', $field->getOperator());

        $field = $model->getField('integerTest');
        $this->assertEquals('=', $field->getOperator());

        $field = $model->getField('color');
        $this->assertEquals('test', $field->getDefaultValue());

        $field = $model->getField('mydate');
        $this->assertEquals('2000-01-01', $field->getDefaultValue());

        //$field = $model->getField('mydatetime');
        //$this->assertEquals('2000-01-01 01:01:01', $field->getDefaultValue());
    }
}