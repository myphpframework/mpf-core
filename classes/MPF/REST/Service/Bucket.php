<?php

namespace MPF\REST\Service;

use MPF\Session;
use MPF\ENV;
use MPF\Text;
use MPF\Logger;
use MPF\User as Usr;

class Bucket extends \MPF\REST\Service
{

    protected function options($id, $action)
    {
        $this->setResponseCode(self::HTTPCODE_OK);

        $options = '';
        header('Allow: ' . $options);
    }

    protected function update($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function delete($id)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function retrieve($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function create($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
        return;

        $this->validate(array('POST'), array('username', 'password'));

        try {
            $user = Usr::create($data['username']);
            $user->setPassword($data['password']);

            // if its the first user we add it to the Admin group
            if (Usr::getTotalEntries() == 1) {
                $user->addGroup(Group::ADMIN());
            }

            $user->save();

            $this->setResponseCode(self::HTTPCODE_CREATED);
            return $user->toArray();
        } catch (\MPF\Db\Exception\DuplicateEntry $e) {
            $this->setResponseCode(self::HTTPCODE_CONFLICT);
            return array('errors' => array(
                    array('code' => self::HTTPCODE_CONFLICT, 'msg' => Text::byXml('mpf_exception')->get('serviceUserAlreadyExists', array('Replace' => array('username' => $data['username']))))
            ));
        }

        $_SESSION['userId'] = $user->getId();
    }

}
