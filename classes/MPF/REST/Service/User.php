<?php

namespace MPF\REST\Service;

use MPF\Session;
use MPF\ENV;
use MPF\Text;
use MPF\Logger;
use MPF\User as Usr;
use MPF\User\Group;

class User extends \MPF\REST\Service {

    protected function options($id, $action) {
        $this->setResponseCode(self::HTTPCODE_OK);

        $options = '';
        header('Allow: '.$options);
    }

    protected function update($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function delete($id) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function retrieve($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function create($id, $data) {
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

    /**
     * Logs out the current session user
     *
     * @param string $id
     * @param array $data
     */
    protected function logout($id, $data) {
        Session::destroy();

        if (array_key_exists('redirect', $data)) {
            header('Location: '.urldecode($data['redirect']));
            exit;
        }

        header('Location: /');
        exit;
    }

   /**
     *
     * @throws \MPF\REST\Service\Exception\InvalidRequestMethod
     * @throws \MPF\REST\Service\Exception\MissingRequestFields
     * @throws \MPF\REST\Service\Exception\InvalidCredentials
     * @param string $id
     * @param array $data
     */
    protected function resetPassword($id, $data) {
        $this->validate(array('PUT'), array('reset_username'));
        $id = filter_var($id, FILTER_SANITIZE_STRING);

        $user = Usr::byUsername($id);
        if (!$user) {
            $this->setResponseCode(self::HTTPCODE_NOT_FOUND);
            return array('errors' => array(
                array('code' => self::HTTPCODE_NOT_FOUND, 'msg' => Text::byXml('mpf_user')->get('usernameNotFound', array('Replace' => array('username' => $id))))
            ));
        }

        if ($user->getPassword() !== null || $user->getId() == 1) {
            $this->setResponseCode(self::HTTPCODE_BAD_REQUEST);
            return array('errors' => array(
                array('code' => self::HTTPCODE_BAD_REQUEST, 'msg' => Text::byXml('mpf_user')->get('cannotResetPassword', array('Replace' => array('username' => $id))))
            ));
        }

        if (!isSet($data['reset_password'])) {
            $exception = new Service\Exception\MissingRequestFields('reset_password');
            Logger::Log('Service', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            throw $exception;
        }

        $this->setResponseCode(self::HTTPCODE_OK);
        $newPassword = filter_var($data['reset_password'], FILTER_SANITIZE_STRING);
        $user->setPassword($newPassword);
        $user->save();
    }

    /**
     *
     * @throws \MPF\REST\Service\Exception\InvalidRequestMethod
     * @throws \MPF\REST\Service\Exception\MissingRequestFields
     * @throws \MPF\REST\Service\Exception\InvalidCredentials
     * @param string $id
     * @param array $data
     */
    protected function login($id, $data) {
        $this->validate(array('PUT'), array('username', 'password'));
        $id = filter_var($id, FILTER_SANITIZE_STRING);

        $this->setResponseCode(self::HTTPCODE_OK);

        $user = Usr::byUsername($id);
        if ($user) {
            // if we found the user we save it to update the last login attempt to the current time
            $user->save();

            if ($user->verifyPassword($data['password'])) {
                Logger::Log('Service\User', Text::byXml('mpf_exception')->get('serviceUserSuccessfulLogin', array('Replace' => array('username' => $id, 'id' => $user->getId()))), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                $_SESSION['userId'] = $user->getId();
                return;
            }
        }

        $exception = new Exception\InvalidCredentials();
        $exception->restCode = self::HTTPCODE_UNAUTHORIZED;
        Logger::Log('Service\User', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
        throw $exception;
    }
}
