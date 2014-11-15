<?php

namespace MPF\REST;

use MPF\ENV;
use MPF\Text;
use MPF\Logger;

abstract class Service
{

    public static $errors = array();

    const HTTPCODE_CONTINUE = 100;
    const HTTPCODE_SWITCH_PROTOCOL = 101;
    const HTTPCODE_OK = 200;
    const HTTPCODE_CREATED = 201;
    const HTTPCODE_ACCEPTED = 202;
    const HTTPCODE_NON_AUTH_INFO = 203;
    const HTTPCODE_NO_CONTENT = 204;
    const HTTPCODE_RESET_CONTENT = 205;
    const HTTPCODE_PARTIAL_CONTENT = 206;
    const HTTPCODE_MULTIPLE_CHOICES = 300;
    const HTTPCODE_MOVED_PERMANENTLY = 301;
    const HTTPCODE_MOVED_TEMPORARILY = 302;
    const HTTPCODE_SEE_OTHER = 303;
    const HTTPCODE_NOT_MODIFIED = 304;
    const HTTPCODE_USE_PROXY = 305;
    const HTTPCODE_BAD_REQUEST = 400;
    const HTTPCODE_UNAUTHORIZED = 401;
    const HTTPCODE_PAYMENT_REQUIRED = 402;
    const HTTPCODE_FORBIDDEN = 403;
    const HTTPCODE_NOT_FOUND = 404;
    const HTTPCODE_METHOD_NOT_ALLOWED = 405;
    const HTTPCODE_NOT_ACCEPTABLE = 406;
    const HTTPCODE_PROXY_AUTH_REQUIRED = 407;
    const HTTPCODE_REQUEST_TIMED_OUT = 408;
    const HTTPCODE_CONFLICT = 409;
    const HTTPCODE_GONE = 410;
    const HTTPCODE_LENGTH_REQUIRED = 411;
    const HTTPCODE_PRECONDITION_FAILED = 412;
    const HTTPCODE_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTPCODE_REQUEST_URI_TOO_LARGE = 414;
    const HTTPCODE_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTPCODE_INTERNAL_ERROR = 500;
    const HTTPCODE_NOT_IMPLEMENTED = 501;
    const HTTPCODE_BAD_GATEWAY = 502;
    const HTTPCODE_SERVICE_UNAVAILABLE = 503;
    const HTTPCODE_GATEWAY_TIMED_OUT = 504;
    const HTTPCODE_HTTP_VERSION_NOT_SUPPORTED = 505;

    abstract protected function update($id, $data);

    abstract protected function create($id, $data);

    abstract protected function delete($id);

    abstract protected function retrieve($id, $data);

    abstract protected function options($id, $action);

    private $data = array();

    /**
     *
     * @var \MPF\User $user
     */
    protected $user;

    /**
     *
     * @var \MPF\REST\Parser
     */
    private $parser = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     *
     * @throws Service\Exception\InvalidRequestAction
     * @throws Service\Exception\InvalidRequestMethod
     * @param mixed $id
     * @param string $action
     */
    public function execute($id, $action)
    {
        $method = strtoupper(filter_var($_SERVER['REQUEST_METHOD'], \FILTER_SANITIZE_STRING));
        Logger::Log('Service', $method . ' :: ' . $id . ' :: ' . $action, Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);

        // if we have an action we validate it and call the proper function
        if ($action) {
            if (!method_exists($this, $action)) {
                self::setResponseCode(self::HTTPCODE_BAD_REQUEST);
                $exception = new Service\Exception\InvalidRequestAction($action);
                Logger::Log('Service', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                throw $exception;
            }

            Logger::Log('Service', get_called_class() . '->' . $action . '(' . $id . ')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            return $this->$action($id, $this->data);
        }

        switch ($method) {
            case 'GET':
                Logger::Log('Service', get_called_class() . '->retrieve(' . $id . ')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                return $this->retrieve($id, $this->data);
                break;
            case 'POST':
                Logger::Log('Service', get_called_class() . '->create(' . $id . ')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                return $this->create($id, $this->data);
                break;
            case 'PUT':
                Logger::Log('Service', get_called_class() . '->update(' . $id . ')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                return $this->update($id, $this->data);
                break;
            case 'DELETE':
                Logger::Log('Service', get_called_class() . '->delete(' . $id . ')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                return $this->delete($id);
                break;
            case 'OPTIONS':
                Logger::Log('Service', get_called_class() . '->options(' . $id . ')', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                return $this->options($id, $action);
                break;
            default:
                self::setResponseCode(self::HTTPCODE_METHOD_NOT_ALLOWED);
                $exception = new Service\Exception\InvalidRequestMethod($method);
                Logger::Log('Service', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
                throw $exception;
                break;
        }
    }

    /**
     * Sets the proper response http code header
     *
     * @param type $code
     */
    public static function setResponseCode($code)
    {
        $protocol = (isSet($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        $text = 'Unknown http status code';
        switch ((int) $code) {
            case self::HTTPCODE_CONTINUE: $text = 'Continue';
                break;
            case self::HTTPCODE_SWITCH_PROTOCOL: $text = 'Switching Protocols';
                break;
            case self::HTTPCODE_OK: $text = 'OK';
                break;
            case self::HTTPCODE_CREATED: $text = 'Created';
                break;
            case self::HTTPCODE_ACCEPTED: $text = 'Accepted';
                break;
            case self::HTTPCODE_NON_AUTH_INFO: $text = 'Non-Authoritative Information';
                break;
            case self::HTTPCODE_NO_CONTENT: $text = 'No Content';
                break;
            case self::HTTPCODE_RESET_CONTENT: $text = 'Reset Content';
                break;
            case self::HTTPCODE_PARTIAL_CONTENT: $text = 'Partial Content';
                break;
            case self::HTTPCODE_MULTIPLE_CHOICES: $text = 'Multiple Choices';
                break;
            case self::HTTPCODE_MOVED_PERMANENTLY: $text = 'Moved Permanently';
                break;
            case self::HTTPCODE_MOVED_TEMPORARILY: $text = 'Moved Temporarily';
                break;
            case self::HTTPCODE_SEE_OTHER: $text = 'See Other';
                break;
            case self::HTTPCODE_NOT_MODIFIED: $text = 'Not Modified';
                break;
            case self::HTTPCODE_USE_PROXY: $text = 'Use Proxy';
                break;
            case self::HTTPCODE_BAD_REQUEST: $text = 'Bad Request';
                break;
            case self::HTTPCODE_UNAUTHORIZED: $text = 'Unauthorized';
                break;
            case self::HTTPCODE_PAYMENT_REQUIRED: $text = 'Payment Required';
                break;
            case self::HTTPCODE_FORBIDDEN: $text = 'Forbidden';
                break;
            case self::HTTPCODE_NOT_FOUND: $text = 'Not Found';
                break;
            case self::HTTPCODE_METHOD_NOT_ALLOWED: $text = 'Method Not Allowed';
                break;
            case self::HTTPCODE_NOT_ACCEPTABLE: $text = 'Not Acceptable';
                break;
            case self::HTTPCODE_PROXY_AUTH_REQUIRED: $text = 'Proxy Authentication Required';
                break;
            case self::HTTPCODE_REQUEST_TIMED_OUT: $text = 'Request Time-out';
                break;
            case self::HTTPCODE_CONFLICT: $text = 'Conflict';
                break;
            case self::HTTPCODE_GONE: $text = 'Gone';
                break;
            case self::HTTPCODE_LENGTH_REQUIRED: $text = 'Length Required';
                break;
            case self::HTTPCODE_PRECONDITION_FAILED: $text = 'Precondition Failed';
                break;
            case self::HTTPCODE_REQUEST_ENTITY_TOO_LARGE: $text = 'Request Entity Too Large';
                break;
            case self::HTTPCODE_REQUEST_URI_TOO_LARGE: $text = 'Request-URI Too Large';
                break;
            case self::HTTPCODE_UNSUPPORTED_MEDIA_TYPE: $text = 'Unsupported Media Type';
                break;
            case self::HTTPCODE_INTERNAL_ERROR: $text = 'Internal Server Error';
                break;
            case self::HTTPCODE_NOT_IMPLEMENTED: $text = 'Not Implemented';
                break;
            case self::HTTPCODE_BAD_GATEWAY: $text = 'Bad Gateway';
                break;
            case self::HTTPCODE_SERVICE_UNAVAILABLE: $text = 'Service Unavailable';
                break;
            case self::HTTPCODE_GATEWAY_TIMED_OUT: $text = 'Gateway Time-out';
                break;
            case self::HTTPCODE_HTTP_VERSION_NOT_SUPPORTED: $text = 'HTTP Version not supported';
                break;
        }

        if ((int) $code >= 400) {
            self::$errors[] = array('code' => $code, "msg" => "$protocol $code $text");
        }
        header('Access-Control-Allow-Origin: *');
        header($protocol . ' ' . $code . ' ' . $text, true, $code);
    }

    /**
     * Sets the login from the authentication scheme
     *
     * @param \MPF\User $user
     */
    final public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Sets the parser for the request
     *
     * @param \MPF\REST\Parser $parser
     */
    final public function setParser(\MPF\REST\Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     *
     * @param array $response
     */
    final public function output($response = '')
    {
        echo $this->parser->toOutput($response);
    }

    /**
     * Validates the params and request method,
     * also sanitize inputs
     *
     * @throws Service\Exception\InvalidRequestMethod
     * @throws Service\Exception\MissingRequestFields
     * @param array $acceptedMethods
     * @param array $requiredFields
     */
    protected function validate($acceptedMethods, $requiredFields)
    {
        $method = strtoupper(filter_var($_SERVER['REQUEST_METHOD'], \FILTER_SANITIZE_STRING));

        if (!in_array($method, $acceptedMethods)) {
            self::setResponseCode(self::HTTPCODE_METHOD_NOT_ALLOWED);
            $exception = new Service\Exception\InvalidRequestMethod($method);
            Logger::Log('Service', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            throw $exception;
        }

        $missingFields = array();
        foreach ($requiredFields as $name) {
            if (!array_key_exists($name, $this->data)) {
                $missingFields[] = $name;
            }
        }

        if (!empty($missingFields)) {
            self::setResponseCode(self::HTTPCODE_BAD_REQUEST);
            $exception = new Service\Exception\MissingRequestFields($missingFields);
            Logger::Log('Service', $exception->getMessage(), Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
            throw $exception;
        }
    }

}
