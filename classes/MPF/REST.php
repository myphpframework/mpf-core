<?php

namespace MPF;

use MPF\REST\Service;
use MPF\Rest\Parser;
use MPF\ENV;

class REST
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
            header('Access-Control-Allow-Origin: *');
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
        self::$basePath = '/' . preg_replace('/^\/|\/$/i', '', $basePath) . '/';

        if (ENV::getType() !== ENV::TYPE_DEVELOPMENT) {
            error_reporting(0);
            ini_set('display_errors', 'off');
        }

        ob_start();

        try {
            $data = self::getData();
            list($serviceClass, $id, $action, $parser) = self::getParts();

            Logger::Log('\MPF\REST', 'Generated class: ' . $serviceClass . "\n\tREQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n", Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);

            if (!class_exists($serviceClass)) {
                Service::setResponseCode(Service::HTTPCODE_NOT_FOUND);
                echo $parser->toOutput(array("errors" => array(
                        array("code" => Service::HTTPCODE_NOT_FOUND, "msg" => "Service (" . $serviceClass . ") not found"))
                ));
                exit;
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
        } catch (Exception\InvalidRequestAction $e) {
            $response = array('errors' => array(
                    array("code" => Service::HTTPCODE_BAD_REQUEST, "msg" => $e->getMessage())
            ));
            Logger::Log('\MPF\REST', 'Response: ' . print_r($response, true), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            $service->output($response);
        } catch (Service\Exception\MissingRequestFields $e) {
            $response = array('errors' => array(
                    array("code" => Service::HTTPCODE_BAD_REQUEST, "msg" => $e->getMessage())
            ));
            Logger::Log('\MPF\REST', 'Response: ' . print_r($response, true), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            $service->output($response);
        } catch (Service\Exception\InvalidRequestMethod $e) {
            $response = array('errors' => array(
                    array("code" => Service::HTTPCODE_METHOD_NOT_ALLOWED, "msg" => $e->getMessage())
            ));
            Logger::Log('\MPF\REST', 'Response: ' . print_r($response, true), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            $service->output($response);
        } catch (\MPF\REST\Service\Exception $e) {
            $errorCode = (property_exists($e, 'restCode') ? $e->restCode : Service::HTTPCODE_INTERNAL_ERROR);
            $response = array('errors' => array(
                    array("code" => $errorCode, "msg" => $e->getMessage())
            ));
            Logger::Log('\MPF\REST', 'Response: ' . print_r($response, true), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
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
            Logger::Log('\MPF\REST', 'Response: ' . print_r($response, true), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            $service->output($response);
        }
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
        $serviceName = str_replace(array('.json', '.xml', '.html'), '', $options[0]);
        $serviceClass = '\MPF\REST\Service\\' . ucfirst(@$options[0]);
        unset($options[0]);

        $id = filter_var(urldecode(@$options[1]), FILTER_SANITIZE_STRING);
        $action = filter_var(urldecode(@$options[2]), FILTER_SANITIZE_STRING);

        preg_match('/\.json|\.html|\.xml$/i', $id, $matches);
        if (empty($matches)) {
            preg_match('/\.json|\.html|\.xml$/i', $action, $matches);
            $format = strtolower(@$matches[0]);
            $action = str_replace(@$matches[0], '', $action);
            if (empty($matches) && !$id) {
                $id = null;
                $action = null;
                preg_match('/\.json|\.html|\.xml$/i', $serviceClass, $matches);
                $format = strtolower(@$matches[0]);
                $serviceClass = str_replace(@$matches[0], '', $serviceClass);
            }
        } elseif (!empty($matches)) {
            $format = strtolower(@$matches[0]);
            $id = str_replace(@$matches[0], '', $id);
        }

        switch ($format) {
            case '.html':
                $parser = new REST\Parser\Html($serviceName, $action);
                break;
            default:
            case '.json':
                $parser = new REST\Parser\Json($serviceName, $action);
                break;
            case '.xml':
                $parser = new REST\Parser\Xml($serviceName, $action);
                break;
        }

        return array($serviceClass, $id, $action, $parser);
    }

    private function __construct()
    {
        
    }

}
