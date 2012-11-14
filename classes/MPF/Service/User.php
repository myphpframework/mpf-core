<?php

namespace MPF\Service;

use MPF\Session;
use MPF\ENV;
use MPF\Text;
use MPF\Logger;
use MPF\User as Usr;

class User extends \MPF\Service {
    protected function update($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function delete($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function retrieve($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function reset_password($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function create($data) {
        $this->validate(array('POST'), array('email', 'password'));

        try {
            $user = Usr::create(\MPF\Email::byString($data['email']));
            $user->setPassword($data['password']);
            $user->save();

            $this->setResponseCode(self::HTTPCODE_CREATED);
            echo $user->toJson();
        } catch (\MPF\Db\Exception\DuplicateEntry $e) {
            $this->setResponseCode(self::HTTPCODE_CONFLICT);
            echo json_encode(array('error' => Text::byXml('mpf_exception')->get('serviceUserAlreadyExists', array('Replace' => array('email' => $data['email'])))));
            return;
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
            header('Location: '.$data[$redirect]);
            exit;
        }

        header('Location: /');
        exit;
    }

    /**
     *
     * @throws \MPF\Service\Exception\InvalidRequestMethod
     * @throws \MPF\Service\Exception\MissingRequestFields
     * @throws \MPF\Service\Exception\InvalidCredentials
     * @param string $id
     * @param array $data
     */
    protected function login($id, $data) {
        $this->validate(array('PUT'), array('email', 'password'));
        $id = filter_var($id, FILTER_SANITIZE_EMAIL);

        $this->setResponseCode(self::HTTPCODE_OK);

        $user = Usr::byEmail(\MPF\Email::byString($id));
        if ($user && $user->verifyPassword($data['password'])) {
            Logger::Log('Service\User', Text::byXml('mpf_exception')->get('serviceUserSuccessfulLogin', array('Replace' => array('email' => $id, 'id' => $user->getId()))), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            $_SESSION['userId'] = $user->getId();
            return;
        }

        $exception = new Exception\InvalidCredentials();
        Logger::Log('Service\User', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
        throw $exception;
    }
}
