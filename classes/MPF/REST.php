<?php

namespace MPF;

use MPF\REST\Service;
use MPF\Rest\Parser;
use MPF\ENV;
use MPF\Log\Category;

class REST extends \MPF\Base
{

    protected static $basePath = '';

    /**
     *
     * @var \MPF\User $loggedInUser
     */
    protected static $loggedInUser;

    public static function basicAuth($login, $password, $realm = 'MPF-REST')
    {
        if (!$login || !$password || !self::authenticate($login, $password)) {
            header('WWW-Authenticate: Basic realm="' . $realm . '"');
            header('HTTP/1.0 401 Unauthorized');
            return false;
        }
        return true;
    }

    public static function verifySignature()
    {
        
    }

    protected static function authenticate($login, $password)
    {
        $user = \MPF\User::byUsername($login);
        if (!$user) {
            return false;
        }

        if (!$user->verifyPassword($password)) {
            return false;
        }

        self::$loggedInUser = $user;
        return true;
    }

    public static function execute($basePath = '')
    {
        $logger = new \MPF\Log\Logger();
        self::$basePath = '/' . preg_replace('/^\/|\/$/i', '', $basePath) . '/';

        if (ENV::getType() !== ENV::TYPE_DEVELOPMENT) {
            error_reporting(0);
            ini_set('display_errors', 'off');
        }

        ob_start();

        try {
            $data = self::getData();
            $parser = self::getParser(getallheaders());
            @list($serviceClass, $id, $action) = self::getParts();

            $logger->debug("Generated class: {name}\n\tREQUEST_URI: {uri}\n", array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'uri' => $_SERVER['REQUEST_URI'],
                'name' => $serviceClass
            ));

            if (!class_exists($serviceClass)) {
                throw new \MPF\REST\Service\Exception\InvalidService($serviceClass);
            }

            $service = new $serviceClass($data);
            $service->setParser($parser);

            if (self::$loggedInUser) {
                $service->setUser(self::$loggedInUser);
            }

            $response = $service->execute($id, $action);

            #ob_end_flush();
            #$buffer = ob_get_contents();
            ob_end_clean();

            if (!$response) {
                if (!empty($service::$errors)) {
                    $service->output(array('errors' => $service::$errors));
                } else {
                    $service->output();
                }
            } else {
                $service->output($response);
            }
        } catch (Service\Exception\InvalidCredentials $e) {
            $response = array('errors' => array(array("code" => Service::HTTPCODE_UNAUTHORIZED, "msg" => $e->getMessage())));
            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));

            $service = new Service\Error($data);
            $service->setResponseCode(Service::HTTPCODE_UNAUTHORIZED);
            $service->setParser($parser);
            $service->output($response);
        } catch (Service\Exception\InvalidService $e) {
            $response = array('errors' => array(
                array("code" => Service::HTTPCODE_NOT_FOUND, "msg" => $e->getMessage())
            ));

            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));
            $service = new Service\Error($data);
            $service->setResponseCode(Service::HTTPCODE_NOT_FOUND);
            $service->setParser($parser);
            $service->output($response);
        } catch (Exception\InvalidRequestAction $e) {
            $response = array('errors' => array(
                array("code" => Service::HTTPCODE_BAD_REQUEST, "msg" => $e->getMessage())
            ));

            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));
            $service->setResponseCode(Service::HTTPCODE_BAD_REQUEST);
            $service->output($response);
        } catch (Service\Exception\MissingRequestFields $e) {
            $response = array('errors' => array(
                array("code" => Service::HTTPCODE_BAD_REQUEST, "msg" => $e->getMessage())
            ));

            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));
            $service->setResponseCode(Service::HTTPCODE_BAD_REQUEST);
            $service->output($response);
        } catch (Service\Exception\InvalidRequestMethod $e) {
            $response = array('errors' => array(
                array("code" => Service::HTTPCODE_METHOD_NOT_ALLOWED, "msg" => $e->getMessage())
            ));

            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));
            $service->setResponseCode(Service::HTTPCODE_METHOD_NOT_ALLOWED);
            $service->output($response);
        } catch (\MPF\REST\Service\Exception $e) {
            $errorCode = (property_exists($e, 'restCode') ? $e->restCode : Service::HTTPCODE_INTERNAL_ERROR);
            $response = array('errors' => array(
                array("code" => $errorCode, "msg" => $e->getMessage())
            ));

            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));
            $service->setResponseCode(Service::HTTPCODE_INTERNAL_ERROR);
            $service->output($response);
        } catch (\Exception $e) {
            $errorCode = (property_exists($e, 'restCode') ? $e->restCode : Service::HTTPCODE_INTERNAL_ERROR);
            $msg = 'Internal Server Error';
            if (ENV::getType() != ENV::TYPE_PRODUCTION) {
                $msg = $e->getMessage();
            }

            $response = array('errors' => array(
                array("code" => $errorCode, "msg" => $msg)
            ));

            $logger->warning('Response: {response}', array(
                'category' => Category::FRAMEWORK | Category::SERVICE, 
                'className' => 'REST',
                'response' => str_replace(' ', '', print_r($response, true)),
                'exception' => $e
            ));
            $service->setResponseCode(Service::HTTPCODE_INTERNAL_ERROR);
            $service->output($response);
        }
    }
    
    /**
     * Return the proper parser for the request content-type
     * 
     * @return \MPF\REST\Parser
     */
    private static function getParser($headers)
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        
        $contentType = (array_key_exists('content-type', $headers) ? $headers['content-type'] : 'application/json');
        switch ($contentType) {
            case 'text/html':
                $parser = new REST\Parser\Html();
                break;
            case 'text/xml':
                $parser = new REST\Parser\Xml();
                break;
            default:
            case 'application/json':
                $parser = new REST\Parser\Json();
                break;
        }

        return $parser;
    }

    private static function getData()
    {
        $a_data = array();
        $input = file_get_contents('php://input');

        $json = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $a_data = $json;
            $input = null;
        }

        if (!$input && empty($a_data)) {
            $a_data = $_REQUEST;
        }

        if (empty($a_data)) {
            preg_match('/boundary=(.*)$/', @$_SERVER['CONTENT_TYPE'], $matches);
            if (!count($matches)) {
                parse_str(urldecode($input), $a_data);
            } else {
                $boundary = $matches[1];
                $a_blocks = preg_split("/-+$boundary/", $input);
                array_pop($a_blocks);
                foreach ($a_blocks as $id => $block) {
                    if (empty($block)) {
                        continue;
                    }

                    if (strpos($block, 'application/octet-stream') !== false) {
                        preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
                        $a_data['files'][$matches[1]] = $matches[2];
                    } else {
                        preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                        $a_data[$matches[1]] = $matches[2];
                    }
                }
            }
        }

        function sanitize(&$data)
        {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = sanitize($value);
                } else {
                    $data[$key] = urldecode($data[$key]);
                    $data[$key] = filter_var($data[$key], FILTER_SANITIZE_SPECIAL_CHARS);
                    $data[$key] = filter_var($data[$key], FILTER_SANITIZE_STRIPPED);
                }
            }

            return $data;
        }

        return sanitize($a_data);
    }

    private static function getParts()
    {
        $requestUri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $requestUri = str_replace(self::$basePath, '', $requestUri);
        $servicePath = str_replace(array('?', $_SERVER['QUERY_STRING']), '', $requestUri);
        $options = preg_split("@\/@", $servicePath, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($options)) {
            throw new \MPF\REST\Service\Exception\InvalidService("");
        }
        
        $serviceClass = '\MPF\REST\Service\\' . ucfirst($options[0]);
        unset($options[0]);
        
        if (!class_exists($serviceClass)) {
            return array();
        }

        $id = filter_var(urldecode(@$options[1]), FILTER_SANITIZE_STRING);
        $action = filter_var(urldecode(@$options[2]), FILTER_SANITIZE_STRING);

        return array($serviceClass, $id, $action);
    }

    private function __construct()
    {
    }

}
