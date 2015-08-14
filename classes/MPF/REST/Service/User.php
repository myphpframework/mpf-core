<?php

namespace MPF\REST\Service;

use MPF\Session;
use MPF\ENV;
use MPF\Text;
use MPF\Log\Category;
use MPF\User as Usr;
use MPF\User\Group;

class User extends \MPF\REST\Service
{

    protected function options($id, $action)
    {
        $this->setResponseCode(self::HTTPCODE_OK);
        $response = array('OPTIONS' => array());
        if ($action == 'login') {
            $response['PUT'] = array('password' => array('required' => true));
        } elseif ($action == 'logout') {
            $response['GET'] = array();
        } elseif ($action == 'resetPassword') {
            $response['GET'] = array();
        } elseif ($id) {
            $response['GET'] = array();
            $response['PUT'] = array();
            $response['POST'] = array(
                'password' => array('required' => true),
            );
        }

        header('Allow: '.implode(',', array_keys($response)));
        return $response;
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
        try {
            $user = Usr::create($data['username']);
            $user->setPassword($data['password']);

            // if its the first user we add it to the Admin group
            if (Usr::getTotalEntries() == 1) {

                $this->getLogger()->notice('First user creation in system, adding to ADMIN group & Active status.', array(
                    'category' => Category::FRAMEWORK | Category::SERVICE, 
                    'className' => 'Service\User'
                ));

                $user->addGroup(Group::ADMIN());
                $user->setStatus(\MPF\Status::create($user, \MPF\User::STATUS_ACTIVE, \MPF\User::SYSTEM()->getId()));
            }
            $user->save();

            $this->setResponseCode(self::HTTPCODE_CREATED);
            return $user->toArray();
        } catch (\MPF\Db\Exception\DuplicateEntry $e) {
            $this->setResponseCode(self::HTTPCODE_CONFLICT);
            return array('httpcode' => self::HTTPCODE_CONFLICT,
                'messsage' => Text::byXml('mpf_exception')->get('serviceUserAlreadyExists', array('Replace' => array('username' => $data['username']))),
                'fields' => array()
            );
        }

        $_SESSION['userId'] = $user->getId();
    }

    /**
     * Logs out the current session user
     *
     * @param string $id
     * @param array $data
     */
    protected function logout($id, $data)
    {
        Session::destroy();

        if (array_key_exists('redirect', $data)) {
            header('Location: ' . urldecode($data['redirect']));
            exit;
        }

        header('Location: /');
        exit;
    }

    /**
     *
     * @throws \MPF\REST\Service\Exception\InvalidCredentials
     * @param string $id
     * @param array $data
     */
    protected function resetPassword($id, $data)
    {
        $user = Usr::byUsername($id);
        if (!$user) {
            $this->setResponseCode(self::HTTPCODE_NOT_FOUND);
            return array('httpcode' => self::HTTPCODE_CONFLICT,
                'messsage' => Text::byXml('mpf_user')->get('usernameNotFound', array('Replace' => array('username' => $id))),
                'fields' => array()
            );
        }

        if ($user->getPassword() !== null || $user->getId() == 1) {
            $this->setResponseCode(self::HTTPCODE_BAD_REQUEST);
            return array('httpcode' => self::HTTPCODE_CONFLICT,
                'messsage' => Text::byXml('mpf_user')->get('cannotResetPassword', array('Replace' => array('username' => $id))),
                'fields' => array()
            );
        }

        if (!isSet($data['reset_password'])) {
            $exception = new Service\Exception\MissingRequestFields('reset_password');

            $this->getLogger()->warning($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'Service\User',
                'exception' => $exception
            ));
            throw $exception;
        }

        $this->setResponseCode(self::HTTPCODE_OK);
        $newPassword = filter_var($data['reset_password'], FILTER_SANITIZE_STRING);
        $user->setPassword($newPassword);
        $user->save();
    }

    /**
     *
     * @throws \MPF\REST\Service\Exception\InvalidCredentials
     * @param string $id
     * @param array $data
     */
    protected function login($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_OK);

        $user = Usr::byUsername($id);
        if ($user) {
            // if we found the user we save it to update the last login attempt to the current time
            $user->save();

            if ($user->verifyPassword($data['password'])) {
                $this->getLogger()->info('User (#{id}) {username} successfully logged in', array(
                    'category' => Category::FRAMEWORK | Category::SERVICE, 
                    'className' => 'Service\User',
                    'id' => $user->getId(),
                    'username' => $id
                ));
                $_SESSION['userId'] = $user->getId();
                return;
            }
        }

        $exception = new Exception\InvalidCredentials();
        $exception->restCode = self::HTTPCODE_UNAUTHORIZED;

        $this->getLogger()->warning($exception, array(
            'category' => Category::FRAMEWORK | Category::SERVICE, 
            'className' => 'Service\User',
            'exception' => $exception
        ));
        throw $exception;
    }

}
