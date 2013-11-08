<?php
use MPF\ENV;
use MPF\ENV\Paths;
use MPF\Status;
use MPF\User;
use MPF\Db;

require_once(__DIR__.'/../../bootstrap.php');
require_once(__DIR__.'/../../ModelExample.php');

ENV::bootstrap(ENV::DATABASE, array('filename' => 'dbTest'));
//$test = Db::byName('test');

class ModelStatusTest extends PHPUnit_Framework_TestCase
{
    public function testSetStatus() {
        $model = new ModelExample();
        $model->setStatus(Status::create($model, ModelExample::STATUS_ACTIVE, User::USERID_SYSTEM));
        $model->setStatus(Status::create($model, ModelExample::STATUS_NOTAPPROVED, User::USERID_SYSTEM));
        $model->setStatus(Status::create($model, ModelExample::STATUS_SUSPENDED, User::USERID_SYSTEM));
        $this->assertEquals(ModelExample::STATUS_SUSPENDED, $model->getCurrentStatus()->getValue());
    }

    public function testGetStatus() {
        $model = new ModelExample();
        $statuses = $model->getStatuses();
        $this->assertEmpty($statuses);
    }
}