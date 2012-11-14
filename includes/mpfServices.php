<?php
use MPF\ENV;
use MPF\Logger;

if (ENV::getType() !== ENV::TYPE_DEVELOPMENT) {
    error_reporting(0);
    init_set('display_errors', 'off');
}

// The services must always return in json format
header('Content-Type: application/json');

ob_start();

try {
    $data = MPF_ServiceData();
    list($serviceClass, $id, $action) = MPF_ServiceClass();
    Logger::Log('mpfService.php', 'Generated class: '. $serviceClass ."\n\tREQUEST_URI: ". $_SERVER['REQUEST_URI'] ."\n\t_SERVER[PWD]: ". $_SERVER['PWD'], Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);

    if (!class_exists($serviceClass)) {
        MPF\Service::setResponseCode(MPF\Service::HTTPCODE_NOT_FOUND);
        echo json_encode(array("error" => "Service (".$serviceClass.") not found"));
        exit;
    }

    $service = new $serviceClass($data);
    $service->execute($id, $action);

    ob_end_flush();
    $buffer = ob_get_contents();

    if (!$buffer) {
        echo '{}';
    }
} catch (Exception $e) {
    ob_end_clean();
    $json = json_encode(array('error' => $e->getMessage()));
    Logger::Log('mpfService.php', 'Response: '.$json, Logger::LEVEL_WARNING, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_SERVICE);
    echo $json;
}

ob_end_flush();


function MPF_ServiceData() {
    $data = array();
    parse_str(file_get_contents('php://input'), $data);

    if (empty($data)) {
        $data = $_REQUEST;
    }

    foreach ($data as $key => $value) {
        $data[ $key ] = filter_var($data[ $key ], FILTER_SANITIZE_SPECIAL_CHARS);
        $data[ $key ] = filter_var($data[ $key ], FILTER_SANITIZE_URL);
        $data[ $key ] = filter_var($data[ $key ], FILTER_SANITIZE_STRIPPED);
    }

    // we set the PWD for the MPF_Environment, in other to fetch the files at their proper locations
    if (array_key_exists('PWD', $data)) {
        $info = pathinfo($data['PWD']);
        $_SERVER['PWD'] = PATH_SITE .'http'. str_replace($info['dirname'], '', $data['PWD']);
        unset($data['PWD']);
        unset($_REQUEST['PWD']);
        unset($_POST['PWD']);
        unset($_GET['PWD']);
    }

    return $data;
}

function MPF_ServiceClass() {
    $requestUri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL);
    $servicePath = str_replace(array('?', $_SERVER['QUERY_STRING']), '', $requestUri);
    $options = preg_split("@\/@", $servicePath, -1, PREG_SPLIT_NO_EMPTY);
    $serviceClass = '\MPF\Service\\'.ucfirst(str_replace('mpf_', '', $options[0]));
    unset($options[0]);

    return array(
        $serviceClass,
        filter_var(@$options[1], FILTER_SANITIZE_STRING),
        filter_var(@$options[2], FILTER_SANITIZE_STRING)
    );
}