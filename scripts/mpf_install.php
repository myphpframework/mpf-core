<?php

session_start();

define('REQUIRED_PHP_MAJOR_VERSION', 5);
define('REQUIRED_PHP_MINOR_VERSION', 3);
define('REQUIRED_PHP_RELEASE_VERSION', 0);

function dependencies() {
    // PHP Version
    if (!defined('PHP_MAJOR_VERSION')) {
        return array('success' => false, 'error' => "MPF requires PHP v".REQUIRED_PHP_MAJOR_VERSION.".".REQUIRED_PHP_MINOR_VERSION.".".REQUIRED_PHP_RELEASE_VERSION." minimum, sorry");
    }

    if (PHP_MAJOR_VERSION < REQUIRED_PHP_MAJOR_VERSION ||
       (PHP_MAJOR_VERSION >= REQUIRED_PHP_MAJOR_VERSION && PHP_MINOR_VERSION < REQUIRED_PHP_MINOR_VERSION) ||
       (PHP_MAJOR_VERSION >= REQUIRED_PHP_MAJOR_VERSION && PHP_MINOR_VERSION >= REQUIRED_PHP_MINOR_VERSION && PHP_RELEASE_VERSION < REQUIRED_PHP_RELEASE_VERSION)) {
        return array('success' => false, 'error' => "MPF requires PHP v".REQUIRED_PHP_MAJOR_VERSION.".".REQUIRED_PHP_MINOR_VERSION.".".REQUIRED_PHP_RELEASE_VERSION." minimum, sorry");
    }

    if (!function_exists('mysqli_connect')) {
        return array('success' => false, 'error' => "MPF requires mysqli support in order to function properly, sorry.");
    }

    return array('success' => true);
}

function downloadMPF() {
    if (defined('PATH_MPF_CORE')) {
        return array('success' => true);
    }

    $zipFile = '/tmp/mpf-core-'.$_GET['version'].'.zip';
    if (!file_exists($zipFile)) {
        $file = fopen($zipFile, 'w');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'https://github.com/myphpframework/mpf-core/archive/'.$_GET['version'].'.zip');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_exec($ch);
        curl_close($ch);
        fclose($file);
    }

    if (!file_exists(realpath('../').'/mpf-core')) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            if (!@$zip->extractTo(realpath('../'))) {
                $zip->close();
                return array('success' => false, 'error' => 'Extract <span class="path">'.$zipFile.'</span> to <span class="path">'.realpath('../').'/</span><span class="filename">mpf-core/</span>.');
            }
            $zip->close();
        }
    }

    if (file_exists(realpath('../').'/mpf-core-'.$_GET['version'])) {
        shell_exec('mv '.realpath('../').'/mpf-core-'.$_GET['version'].' '.realpath('../').'/mpf-core ');
        shell_exec('find '.realpath('../').'/mpf-core -type d -exec chmod 755 {} +');
        shell_exec('find '.realpath('../').'/mpf-core -type f -exec chmod 644 {} +');
    }

    return array('success' => true);
}

function bootstrap() {
    $bootstrapFile = realpath('../').'/bootstrap.php';
    if (!file_exists($bootstrapFile)) {
        if (null === shell_exec('cp '.realpath('../').'/mpf-core/scripts/bootstrap.php '.$bootstrapFile.' && echo "success"')) {
            return array('success' => false, 'error' => 'Copy <span class="filename">bootstrap.php</span> from <span class="path">mpf-core/scripts/</span> to <span class="path">'.realpath('../').'/</span><span class="filename">bootstrap.php</span>.');
        }
    }

    return array('success' => true);
}

function htaccess() {
    $htacessFile = realpath('.').'/.htaccess';
    if (!file_exists($htacessFile)) {
        if (null === shell_exec('cp '.realpath('../').'/mpf-core/scripts/.htaccess '.$htacessFile.'  && echo "success"')) {
            return array('success' => false, 'error' => 'Copy <span class="filename">.htaccess</span> from <span class="path">mpf-core/scripts/</span> to <span class="path">'.realpath('./').'/</span><span class="filename">.htaccess</span>.');
        }
    }

    return array('success' => true);
}

function configHtaccess() {
    $bootstrapFile = realpath('../').'/bootstrap.php';
    $htacessFile = realpath('.').'/.htaccess';
    $phpIniFile = realpath('../').'/php.ini';

    if (!defined('PATH_MPF_CORE')) {
        $file = @file_get_contents($htacessFile);
        if (isSet($_ENV['suPHPInstalled']) || isSet($_SERVER["suPHPInstalled"])) {
            $file = str_replace("#suPHP_ConfigPath {path}", "suPHP_ConfigPath ".realpath('../')."/", $file);
            @file_put_contents($htacessFile, $file);
            if (!file_exists($phpIniFile)) {
                @file_put_contents($phpIniFile, 'auto_prepend_file = "'.$bootstrapFile.'"'."\n\n");
            } else {
                $phpIni = @file_get_contents($phpIniFile);
                if (!preg_match('/auto_prepend_file/i', $phpIni)) {
                    @file_put_contents($phpIniFile, 'auto_prepend_file = "'.$bootstrapFile.'"'."\n\n".$phpIni);
                }
            }
        } else {
            return array('success' => false, 'error' => 'Add the following line <span class="filename">php_value auto_prepend_file "'.$bootstrapFile.'"</span> to the top of the file <span class="path">'.$htacessFile.'</span>.');
        }
    }

    return array('success' => true);
}

function configBootstrap() {
    $bootstrapFile = realpath('../').'/bootstrap.php';

    if (isset($_ENV['suPHPInstalled'])) {
        $file = @file_get_contents($bootstrapFile);
        $file = str_replace('{PATH_MPF_CORE}', realpath('../').'/mpf-core/', $file);
        $file = str_replace('{PATH_SITE}', realpath('../').'/', $file);
        $file = str_replace('{URL_SITE}', 'http://'.$_SERVER['HTTP_HOST'].'/', $file);
        $file = str_replace('{MPF_ENV}', 'development', $file);
        @file_put_contents($bootstrapFile, $file);
        return array('success' => true);
    }

    if (!defined('PATH_MPF_CORE') || PATH_MPF_CORE == '{PATH_MPF_CORE}' || !file_exists(PATH_MPF_CORE.'init.php')) {
        return array('success' => false, 'error' => 'The constant <span class="filename">PATH_MPF_CORE</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. Make sure the <u>absolute path</u> finishes with a slash "/".');
    }

    if (!defined('PATH_SITE') || PATH_SITE == '{PATH_SITE}' || !file_exists(PATH_SITE.'bootstrap.php')) {
        return array('success' => false, 'error' => 'The constant <span class="filename">PATH_SITE</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. Make sure the <u>absolute path</u> finishes with a slash "/".');
    }

    if (!defined('MPF_ENV') || MPF_ENV == '{MPF_ENV}') {
        return array('success' => false, 'error' => 'The constant <span class="filename">MPF_ENV</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. This constant reflects the current environment the server is in, usual choices are: development,testing,staging,production');
    }

    return array('success' => true);
}

function changeFrameworkSalt() {
    $frameworkSalt = \MPF\Config::get('settings')->framework->salt;

    if (!isSet($_GET['change']) && $frameworkSalt == 'myPhPfR@M3w0rk') {
        return array('success' => false, 'error' => 'The framework salt is still "myPhPfR@M3w0rk", it needs to be changed.');
    }

    if (isSet($_GET['change'])) {
        if (isSet($_ENV['suPHPInstalled']) || isSet($_SERVER["suPHPInstalled"])) {
            $newSalt = rand_string(24);
            if ($_GET['change'] == 'framework') {
                $frameworkSettings = file_get_contents(PATH_MPF_CORE.'config/settings.ini');
                file_put_contents(PATH_MPF_CORE.'config/settings.ini', str_replace('framework.salt = myPhPfR@M3w0rk', 'framework.salt = '.$newSalt, $frameworkSettings));
                return array('success' => true);
            } else if ($_GET['change'] == 'site') {
                @shell_exec('mkdir -p '.realpath('../').'/config');
                @shell_exec('find '.realpath('../').'/config -type d -exec chmod 755 {} +');
                file_put_contents(realpath('../').'/config/settings.ini', "[production]
framework.salt = $newSalt

[staging:production]
[testing : production]
[development : production]
");
                @shell_exec('find '.realpath('.').'/config -type f -exec chmod 644 {} +');
                return array('success' => true);
            }
        } else {
            if ($_GET['change'] == 'framework') {
                return array('success' => false, 'error' => 'You will have to change the framework salt in <span class="filename">'.PATH_MPF_CORE.'config/settings.ini</span>.');
            } else if ($_GET['change'] == 'site') {
                return array('success' => false, 'error' => 'You will have to change the framework salt in <span class="filename">'.realpath('../').'/config/settings.ini</span>.');
            }
        }
    }

    return array('success' => true);
}

function rand_string( $length ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

  $str = '';
	$size = strlen( $chars );
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
}

function testDbConnection() {
    error_reporting(0);
    ini_set('display_errors', 'off');

    if ($_REQUEST === $_SESSION) {
        try {
            \MPF\User::SYSTEM();
            return array('success' => true);
        } catch (Exception $e) {}
    }

    if (isset($_REQUEST['db_type']) && in_array($_REQUEST['db_type'], array('mysqli'))) {

        if ($_REQUEST['db_type'] == 'mysqli') {
            if (!isSet($_REQUEST['db_port'])) {
                $_REQUEST['db_port'] = 3306;
            }

            $mysqli = new mysqli($_REQUEST['db_host'], $_REQUEST['db_login'], $_REQUEST['db_pwd'], $_REQUEST['db_name'], $_REQUEST['db_port']);
            if ($mysqli->connect_errno || $mysqli->connect_error) {
                $mysqli->close();
                return array('success' => false, 'error' => $mysqli->connect_error);
            }
            $mysqli->close();

            $_SESSION = $_REQUEST;
            return array('success' => true);
        }
    }

    return array('success' => false, 'error' => 'Unsupported database type');
}

function createDbConfig() {
    $databaseFile = realpath('../').'/config/dbs/default.xml';
    if (!file_exists($databaseFile)) {
        $databaseXML = @simplexml_load_file(PATH_MPF_CORE.'scripts/database.xml');
        $databaseXML->engine = $_SESSION['db_type'];
        $databaseXML->name = $_SESSION['db_name'];
        $databaseXML->server->host = $_SESSION['db_host'];
        $databaseXML->server->port = $_SESSION['db_port'];
        $databaseXML->server->access->login = $_SESSION['db_login'];
        $databaseXML->server->access->password = $_SESSION['db_pwd'];

        @shell_exec('mkdir -p '.realpath('../').'/config/dbs/');
        @shell_exec('find '.realpath('../').'/config -type d -exec chmod 755 {} +');

        $result = @$databaseXML->asXML($databaseFile);
        if (!$result) {
            return array('success' => false, 'error' => 'Please create the following file: <span class="path">'.realpath('./').'/config/dbs/</span><span class="filename">default.xml</span> with the following content:<xmp>'.$databaseXML->asXML().'</xmp>');
        }
    }

    return array('success' => true);
}

function createUserTables() {
    try {
        $systemUser = \MPF\User::SYSTEM();
        if ($systemUser) {
            return array('success' => true);
        }
    } catch (Exception $e) {}

    if ($_SESSION['db_type'] == 'mysqli') {
        $mysqli = new mysqli($_SESSION['db_host'], $_SESSION['db_login'], $_SESSION['db_pwd'], 'myphpframework', $_SESSION['db_port']);
        if ($mysqli->connect_error) {
            return array('success' => false, 'error' => $mysqli->connect_error);
        }

        $mysqli->autocommit(FALSE);
        $mysqli->query('START TRANSACTION;');
        $errors = '';

        // ##### USER
        $userTable = @file_get_contents(PATH_MPF_CORE.'sql/'.$_SESSION['db_type'].'/user.sql');
        if ($mysqli->multi_query($userTable)) {
            do {
                if (!$mysqli->next_result()) {
                    $errors .= $mysqli->error."<br />\n";
                    break;
                }
            } while ($mysqli->more_results());
        } else {
            $errors .= $mysqli->error."<br />\n";
        }

        // ##### USER STATUS
        $statusTable = @file_get_contents(PATH_MPF_CORE.'sql/'.$_SESSION['db_type'].'/user_status.sql');
        if (!$mysqli->query($statusTable)) {
            $errors .= $mysqli->error."<br />\n";
        }

        // ##### USER PERMISSION
        $userPermissionTable = @file_get_contents(PATH_MPF_CORE.'sql/'.$_SESSION['db_type'].'/user_permission.sql');
        if (!$mysqli->query($userPermissionTable)) {
            $errors .= $mysqli->error."<br />\n";
        }

        // ##### USER GROUP
        $userGroupTable = @file_get_contents(PATH_MPF_CORE.'sql/'.$_SESSION['db_type'].'/user_group.sql');
        if ($mysqli->multi_query($userGroupTable)) {
            do {
                if (!$mysqli->next_result()) {
                    $errors .= $mysqli->error."<br />\n";
                    break;
                }
            } while ($mysqli->more_results());
        } else {
            $errors .= $mysqli->error."<br />\n";
        }

        // ##### USER GROUP LINK
        $userGroupLinkTable = @file_get_contents(PATH_MPF_CORE.'sql/'.$_SESSION['db_type'].'/user_group_link.sql');
        if (!$mysqli->query($userGroupLinkTable)) {
            $errors .= $mysqli->error."<br />\n";
        }

        // ##### USER GROUP PERMISSION
        $userGroupPermissionTable = @file_get_contents(PATH_MPF_CORE.'sql/'.$_SESSION['db_type'].'/user_group_permission.sql');
        if (!$mysqli->query($userGroupPermissionTable)) {
            $errors .= $mysqli->error."<br />\n";
        }

        if ($errors) {
            $mysqli->rollback();
            $mysqli->close();
            return array('success' => false, 'error' => $errors);
        }
        $mysqli->commit();
        $mysqli->close();

        return array('success' => true);
    }

    return array('success' => false, 'error' => 'Unsupport database type');
}

function webadminDownload() {
    $_GET['version'] = 'master';
    $zipFile = '/tmp/mpf-admin-'.$_GET['version'].'.zip';
    if (!file_exists($zipFile)) {
        $file = fopen($zipFile, 'w');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'https://github.com/myphpframework/mpf-admin/archive/'.$_GET['version'].'.zip');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_exec($ch);
        curl_close($ch);
        fclose($file);
    }

    $pathinfo = pathinfo(realpath('.'));
    if (!file_exists(PATH_SITE.$pathinfo['basename'].'/mpf-admin')) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            if (!@$zip->extractTo(PATH_SITE.$pathinfo['basename'].'/')) {
                $zip->close();
                return array('success' => false, 'error' => 'Extract <span class="path">'.$zipFile.'</span> to <span class="path">'.realpath('.').'/</span><span class="filename">mpf-admin/</span>.');
            }
            $zip->close();
        }
    }

    if (file_exists(realpath('.').'/mpf-admin-'.$_GET['version'])) {
        shell_exec('mv '.realpath('.').'/mpf-admin-'.$_GET['version'].' '.realpath('.').'/mpf-admin ');
        shell_exec('find '.realpath('.').'/mpf-admin -type d -exec chmod 755 {} +');
        shell_exec('find '.realpath('.').'/mpf-admin -type f -exec chmod 644 {} +');
    }

    return array('success' => true);
}

if (isset($_REQUEST['ajax'])) {
    $result = array('success' => false, 'error' => 'Ajax function not found');
    switch ($_REQUEST['ajax']) {
        case 'dependencies':     $result = dependencies();     break;
        case 'downloadMPF':      $result = downloadMPF();      break;
        case 'bootstrap':        $result = bootstrap();        break;
        case 'htaccess':         $result = htaccess();         break;
        case 'configBootstrap':  $result = configBootstrap();  break;
        case 'configHtaccess':   $result = configHtaccess();   break;
        case 'changeFrameworkSalt':   $result = changeFrameworkSalt();   break;
        case 'testDbConnection': $result = testDbConnection(); break;
        case 'createDbConfig':   $result = createDbConfig();   break;
        case 'createUserTables': $result = createUserTables(); break;
        case 'webadminDownload': $result = webadminDownload(); break;
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
} ?><!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
    <title>MPF Installation walkthrough</title>
    <link rel="icon" type="image/ico" href="/images/favicon.ico"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf8"/>
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="description" content="Web interface that lets your administer certain portion of MyPhpFramework"/>
    <meta name="keywords" content="PHP,framework,admin,mpf,myphpframework"/>
    <meta name="author" content="Philippe Guilbault"/>
    <meta name="copyright" content="Yes plz."/>
    <meta name="robots" content="NOINDEX, NOFOLLOW"/>
    <style type="text/css" media="screen" title="Default Style">
        html {
            background-color: #DBDDE5;
        }

        body {
            font-family: "Helvetica Neue", Arial,  Helvetica, sans-serif;
            font-size: 14px;
            background-color: white;
            width: 450px;
            margin: 0 auto;
            border: 1px solid #AAA;
            margin-top: 12px;
            padding: 8px;
        }

        img {
            vertical-align: middle;
            margin-right: 10px;
        }
        h2 {
            text-align: center;
            border-bottom: 1px dotted gray;
            padding-bottom: 18px;
            margin-bottom: 0px;
            font-size: 22px;
        }
        xmp {
            color: #1F2690;
            font-size: 10px;
            white-space: pre-wrap;
        }
        u {
            color: darkred;
        }
        p {
            color: #777;
        }

        ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        body ul > li {
            display: block;
            border-bottom: 1px dotted gray;
            padding: 8px;
            clear:both;
        }
        body ul > li > div {
            float: left;
            font-size: 28px;
            width: 27px;
            margin-top: -8px;
        }
        body ul > li > label {
            font-size: 14px;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
        }

        .filename {
            font-style: italic;
            font-weight: normal;
            color: green;
        }

        .path {
            font-style: italic;
            font-weight: normal;
        }

        #installWebInterface,
        #webadmin {
            display:none;
        }

        #form_mysqli {

        }

        #db_forms,
        #db_types {
            clear:both;
            display:block;
            text-align: center;
            padding: 4px;
        }
        #db_types li {
            display: inline;
        }

        #db_forms li input[type="text"], #db_forms li input[type="password"] {
            width: 120px;
            border: 1px solid;
            border-color: #666 #DDD #DDD #666;
            padding: 2px;

            background-color: #FEE;
            color: #777;
        }

        #db_forms li input[type="text"].changed, #db_forms li input[type="password"].changed {
            background-color: #EFE;
            color: #333;
        }

        #db_forms li input[type="submit"] {
            width: 126px;
        }

        input[disabled]{
            background-color: #EEE !important;
        }

        .errorGlow {
            -webkit-transition: -webkit-box-shadow 0.5s ease-out;
            -moz-transition: -moz-box-shadow 0.5s ease-out;
            transition: box-shadow 0.5s ease-out;
            -webkit-box-shadow: 0px 0px 3px red;
            -moz-box-shadow: 0px 0px 3px red;
            box-shadow: 0px 0px 3px red;
        }
        p.note {
            background-color: #FFFFE3;
            background: #FFFFE3 -webkit-gradient(linear, left top, left bottom, from(#FFF), to(#FFFFE3)) no-repeat;
            background: #FFFFE3 -moz-linear-gradient(top, #FFF, #FFFFE3) no-repeat;
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#FFF, endColorstr=#FFFFE3) no-repeat;
            -ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#FFF, endColorstr=#FFFFE3)" no-repeat;

            padding: 16px;
            border: 1px solid;
            border-color: #F9F9F9 #B2B24F #B2B24F #F9F9F9;
            -moz-border-radius: 8px;
            -webkit-border-radius: 8px;
            -khtml-border-radius: 8px;
            border-radius: 8px;
            color: #646400;

        }
        #saltExplanation {
            display: none;
        }
    </style>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <script type="text/javascript" src="//fgnass.github.com/spin.js/dist/spin.min.js"></script>
</head>
<body>
<h2><img src="http://myphpframework.self/images/logo_small_blue.png" alt="myPhpFramework Logo" width="87" height="43"/>Web Installation Walkthrough</h2>
<ul id="overview">
    <li>
        <p>The walkthrough will try to <u>download the framework and configure it on its own</u> and if it cannot it will guide you through the rest of the mpf-core installation.</p>
        <p>The version of the framework of your choosing will be downloaded as a zip to <span class="filename">/tmp/mpf-core.zip</span> and extracted to <span class="filename"><?= realpath('../').'/mpf-core/'; ?></span> if permissions (SuPHP, phpSuExec) let us do so.</p>
    </li>
    <li>
        <select name="versions">
            <option value="master">Latest (Unstable)</option>
        </select>
        <input type="button" id="start" value="Begin" /><br />
    </li>
    <li>
        <div id="dependencies">&#183;</div>
        <label>Checking Dependencies</label>
    </li>
    <li>
        <div id="downloadMPF">&#183;</div>
        <label>Downloading &amp; Extracting MyPhpFramework</label>
    </li>
    <li>
        <div id="bootstrap">&#183;</div>
        <label>Copying Framework Bootstrap</label>
    </li>
    <li>
        <div id="htaccess">&#183;</div>
        <label>Copying Framework .htaccess</label>
    </li>
    <li>
        <div id="configHtaccess">&#183;</div>
        <label>Configuring .htaccess</label>
    </li>
    <li>
        <div id="configBootstrap">&#183;</div>
        <label>Configuring bootstrap</label>
    </li>
    <li>
        <div id="changeFrameworkSalt">&#183;</div>
        <label>Changing the framework salt</label>
    </li>
    <li id="saltExplanation">
        <p>There is two options available for changing the framework salt:</p>
        <ul>
            <li>
                <div id="changeSaltFramework">&#183;</div>
                <label>Change it within the core.&nbsp; <input type="button" value="Change" /></label>
            </li>
            <li>
                <div id="changeSaltSite">&#183;</div>
                <label>Overwrite it only for this web site. <input type="button" value="Change" /></label>
            </li>
        </ul>
        <p>The framework salt is used in hashing various things such has the user password. The user password is not only hashed by their own unique salt but by the framework's salt as well.</p>
        <p>If your are planning on using only 1 installation of the mpf-core for many sites it is recommended to overwrite it in every site, in the <span class="filename">config/settings.ini</span> at the root of your site.</p>
    </li>
    <li class="success" style="display:none;" >Installation of the core is completed</li>
    <li id="installWebInterface">
        <p><span class="filename">The core of the framework is fully installed and ready to use.</span> If you would like to install the web admin a database configuration is required. <u>It is strongly suggested to run this part in HTTPS.</u></p>
        <input type="button" name="webadmin" value="Install Web Admin" />
    </li>
</ul>
<ul id="webadmin">
    <li>
        <p><span class="filename">The core of the framework is fully installed and ready to use.</span> If you would like to install the web admin a database configuration is required. <u>It is strongly suggested to run this part in HTTPS.</u></p>
    </li>
    <li>
        <div id="databaseInfo">&#183;</div>
        <label>Database Connection</label>
        <ul id="db_types">
            <li><input type="radio" name="db_type" checked="checked" value="mysqli" id="mysql" /><label for="mysql">MySQL</label></li>
            <li><input type="radio" name="db_type" disabled="disabled" value="postgres" id="postgres" /><label for="postgres">Postgres</label></li>
            <li><input type="radio" name="db_type" disabled="disabled" value="sqlite" id="sqlite" /><label for="sqlite">SQLite</label></li>
        </ul>
        <ul id="db_forms">
            <li id="form_mysqli">
                <form name="mysql" method="get">
                    <input type="text" name="db_host" value="<?= @$_SESSION['db_host'] ?>" placeholder="dp host" />
                    <input type="text" name="db_port" value="<?= @$_SESSION['db_port'] ?>" placeholder="db port 3306" />
                    <input type="text" name="db_name" value="<?= @$_SESSION['db_name'] ?>" placeholder="db name" />
                    <input type="text" name="db_login" value="<?= @$_SESSION['db_login'] ?>" placeholder="username" />
                    <input type="password" name="db_pwd" value="<?= @$_SESSION['db_pwd'] ?>" placeholder="password" />
                    <input type="submit" value="Test Connection" />
                </form>
            </li>
        </ul>
    </li>
    <li>
        <div id="createDbConfig">&#183;</div>
        <label>Creating config/dbs/default.xml</label>
    </li>
    <li>
        <div id="createUserTables">&#183;</div>
        <label>Creating user system tables</label>
    </li>
    <li>
        <div id="webadminDownload">&#183;</div>
        <label>Downloading &amp; Extracting the Web Admin</label>
    </li>
    <li class="success" style="display:none;" >MyPhpFramework Admin is installed successfully you can access it <a href="/mpf-admin/">here</a>.</li>
</ul>
<script type="text/javascript">
// Make sure the function "hasOwnProperty" works
if (!Object.prototype.hasOwnProperty) {
    Object.prototype.hasOwnProperty = function(prop) {
        var proto = obj.__proto__ || obj.constructor.prototype;
        return (prop in this) && (!(prop in proto) || proto[prop] !== this[prop]);
    };
}

// Make sure the function "indexOf" works
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function(obj){
        for(var i=0; i<this.length; i++){
            if(this[i]==obj){
                return i;
            }
        }
        return -1;
    }
}

if (!Array.prototype.forEach) {
    Array.prototype.forEach = function(fun)
    {
        var len = this.length;
        if (typeof fun != "function")
            throw new TypeError();

        var thisp = arguments[1];
        for (var i = 0; i < len; i++)
        {
            if (i in this)
                fun.call(thisp, this[i], i, this);
        }
    };
}

if (!Object.keys) {
    Object.keys = function(o) {
        if (o !== Object(o))
            throw new TypeError('Object.keys called on a non-object');
        var k=[],p;
        for (p in o) if (Object.prototype.hasOwnProperty.call(o,p)) k.push(p);
        return k;
    }
}

$.fn.spin = function(opts) {
  this.each(function() {
    var $this = $(this),
        data = $this.data();

    if (data.spinner) {
      data.spinner.stop();
      delete data.spinner;
    }
    if (opts !== false) {
      data.spinner = new Spinner($.extend({color: $this.css('color')}, opts)).spin(this);
    }
  });
  return this;
};

function getParameterByName(name) {
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.search);
  if(results == null) {
    return "";
  } else {
    return decodeURIComponent(results[1].replace(/\+/g, " "));
  }
}

function ajax(url, querystring, method, callback) {
    var args = Array.prototype.slice.call(arguments),
        callback = args.pop(),
        url = args.shift(),
        querystring = (args.length == 0 ? '' : args.shift()),
        method = (args.length == 0 ? 'POST' : args.shift());

    $.ajax({
        type: method,
        url: url,
        dataType: 'jsonp',
        data: querystring,
        error: function (error) {
            if (error.readyState != 0) {
                try {
                    error = JSON.parse(error.responseText).error;
                } catch (e) {}
                callback(error, null);
            }
        },
        success: function(response) {
            if (!response) {
                callback('Unexpected error', response);
            }

            if (response.data.hasOwnProperty('success') && response.data.success) {
                callback(null, response.data);
            } else if (response.data.hasOwnProperty('error')) {
                callback(response.data.error, response.data);
            } else {
                callback('Unexpected error', response.data);
            }
        }
    });
};

$(document).ready(function () {
    var check = '&#10003;',
        spinOpts = {color: "#000",lines: 10, length: 3, radius: 3, width: 1};

    ajax('https://api.github.com/repos/myphpframework/mpf-core/git/refs/tags', 'GET', function (error, result) {
        if ($.isArray(result)) {
            var $versions = $('select[name="versions"]'), versions = [];

            for (var index in result) {
                if (result.hasOwnProperty(index)) {
                    versions.unshift(result[index].ref.replace('refs/tags/',''));
                }
            }

            for (var i in versions) {
                $versions.append('<option>'+versions[i]+'</option>');
            }
        }
    });

    $('#start').click(function() {
        $(this).val('Continue');
        var $dependencies = $('#dependencies'),
            $downloadMPF = $('#downloadMPF'),
            $bootstrap = $('#bootstrap'),
            $htaccess = $('#htaccess'),
            $configBootstrap = $('#configBootstrap'),
            $changeFrameworkSalt = $('#changeFrameworkSalt'),
            $configHtaccess = $('#configHtaccess'),
            $success = $('li.success');

        // reset ui
        $downloadMPF.html('&#183;').removeClass('error');
        $bootstrap.html('&#183;').removeClass('error');
        $htaccess.html('&#183;').removeClass('error');
        $configBootstrap.html('&#183;').removeClass('error');
        $configHtaccess.html('&#183;').removeClass('error');
        $success.hide();
        $('#installWebInterface').hide();
        $('.webadmin').hide();
        $('li.error').remove();

        $dependencies.html('&nbsp;').spin(spinOpts);
        ajax('mpf_install.php', 'ajax=dependencies', 'GET', function (error, result) {
            $dependencies.spin(false);
            if (error) {
                showError($dependencies, error);
                return;
            }

            $dependencies.addClass('success');
            $('div', $dependencies.closest('li')).html(check);
            $downloadMPF.html('&nbsp;').spin(spinOpts);
            ajax('mpf_install.php', 'ajax=downloadMPF&version='+$('[name="versions"]').val(), 'GET', function (error, result) {
                $downloadMPF.spin(false);
                if (error) {
                    showError($downloadMPF, error);
                    return;
                }

                $downloadMPF.addClass('success');
                $('div', $downloadMPF.closest('li')).html(check);
                $bootstrap.html('&nbsp;').spin(spinOpts);
                ajax('mpf_install.php', 'ajax=bootstrap', 'GET', function (error, result) {
                    $bootstrap.spin(false);
                    if (error) {
                        showError($bootstrap, error);
                        return;
                    }

                    $bootstrap.addClass('success');
                    $('div', $bootstrap.closest('li')).html(check);
                    $htaccess.html('&nbsp;').spin(spinOpts);
                    ajax('mpf_install.php', 'ajax=htaccess', 'GET', function (error, result) {
                        $htaccess.spin(false);
                        if (error) {
                            showError($htaccess, error);
                            return;
                        }

                        $htaccess.addClass('success');
                        $('div', $htaccess.closest('li')).html(check);
                        $configHtaccess.html('&nbsp;').spin(spinOpts);
                        ajax('mpf_install.php', 'ajax=configHtaccess', 'GET', function (error, result) {
                            $configHtaccess.spin(false);
                            if (error) {
                                showError($configHtaccess, error);
                                return;
                            }

                            $configHtaccess.addClass('success');
                            $('div', $configHtaccess.closest('li')).html(check);
                            $configBootstrap.html('&nbsp;').spin(spinOpts);
                            ajax('mpf_install.php', 'ajax=configBootstrap', 'GET', function (error, result) {
                                $configBootstrap.spin(false);
                                if (error) {
                                    showError($configBootstrap, error);
                                    return;
                                }

                                $configBootstrap.addClass('success');
                                $('div', $configBootstrap.closest('li')).html(check);

                                $("#saltExplanation").hide();
                                $changeFrameworkSalt.html('&nbsp;').spin(spinOpts);
                                ajax('mpf_install.php', 'ajax=changeFrameworkSalt', 'GET', function (error, result) {
                                    $changeFrameworkSalt.spin(false);
                                    if (error) {
                                        $("#saltExplanation").show();
                                        $('html, body').animate({scrollTop: $(document).height()}, 'slow');
                                        showError($changeFrameworkSalt, error);
                                        return;
                                    }

                                    $changeFrameworkSalt.addClass('success');
                                    $('div', $changeFrameworkSalt.closest('li')).html(check);

                                    $success.show();
                                    $('#installWebInterface').show();
                                    $('html, body').animate({scrollTop: $(document).height()}, 'slow');
                                    var newURL = location.href.replace('webadmin_walkthrough=1', '') + (location.search ? "&" : "?") + "webadmin_walkthrough=1";
                                    history.replaceState({}, '', newURL);
                                });
                            });
                        });
                    });
                });
            });
        });
    });

    var $changeSaltSite = $('#changeSaltSite').closest('li');
    $changeSaltSite.find('[type=button]').click(function () {
        $changeSaltSite.html('&nbsp;').spin(spinOpts);
        ajax('mpf_install.php', 'ajax=changeFrameworkSalt&change=site', 'GET', function (error, result) {
            if (error) {
                $('html, body').animate({scrollTop: $(document).height()}, 'slow');
                showError($changeSaltSite, error);
                return;
            }

            $changeSaltSite.addClass('success');
            $('div', $changeSaltSite.closest('li')).html(check);

            $("#saltExplanation").hide();
            $('#start').click();
        });
    });

    var $changeSaltFramework = $('#changeSaltFramework').closest('li');
    $changeSaltFramework.find('[type=button]').click(function () {
        $changeSaltFramework.html('&nbsp;').spin(spinOpts);
        ajax('mpf_install.php', 'ajax=changeFrameworkSalt&change=framework', 'GET', function (error, result) {
            if (error) {
                $('html, body').animate({scrollTop: $(document).height()}, 'slow');
                showError($changeSaltFramework, error);
                return;
            }

            $changeSaltFramework.addClass('success');
            $('div', $changeSaltFramework.closest('li')).html(check);

            $("#saltExplanation").hide();
            $('#start').click();
        });
    });

    $('input[name="webadmin"]').click(function (event, options) {
        $(this).hide();
        var $databaseInfo = $('#databaseInfo')
            //$success = $('li.success');

        $databaseInfo.html('&#183;').removeClass('error');
        //$success.hide();
        $('li.error').remove();

        if (options && options.hasOwnProperty('instant')) {
            $('#overview').hide();
            $('#webadmin').show();
        } else {
            $('#overview').slideUp(500, function () {
                $('#webadmin').slideDown(500, function () {
                    $('html, body').animate({scrollTop: $(document).height()}, 'slow', function () {
                    });
                });
            });
        }
    });

    $('input[name="db_host"]').blur(function () {
        if ($(this).val() == 'localhost' || $(this).val() == '127.0.0.1') {
            $('input[name="db_port"]').prop('disabled', 'disabled');
        } else {
            $('input[name="db_port"]').removeProp('disabled');
        }
    });

    $('input[name="db_pwd"],input[name="db_login"],input[name="db_host"],input[name="db_name"],input[name="db_port"]').blur(function () {
        var $input = $(this);
        if ($input.val() == "" || $input.val() == $input.attr('data-default')) {
            $input.val($input.attr('data-default'));
            $input.removeClass('changed');
        } else {
            $input.addClass('changed');
        }
    });

    $('form[name="mysql"]').submit(function () {
        var $databaseInfo = $('#databaseInfo'),
            db_type = $('input[name="db_type"]').val(),
            data = '';
            //$success = $('li.success');

        $('#form_'+db_type+' input:not(:disabled)').each(function (index, element) {
            var $input = $(element);
            if ($input.val() == "" || $input.val() == $input.attr('data-default')) {
                return false;
            }

            $input.addClass('changed');
        });

        if ($('#form_'+db_type+' input[type="text"]:not(.changed):not(:disabled),[type="password"]:not(.changed):not(:disabled)').length > 0) {
            $('#form_'+db_type+' input[type="text"]:not(.changed):not(:disabled),[type="password"]:not(.changed):not(:disabled)').addClass('errorGlow');
            setTimeout(function () {
                $('#form_'+db_type+' input[type="text"]').removeClass('errorGlow');
                $('#form_'+db_type+' input[type="password"]').removeClass('errorGlow');
            }, 1000);
            return false;
        }

        $('#form_'+db_type+' input:not(:disabled)').each(function (index, element) {
           data += $(element).prop('name') +'='+ $(element).val() +'&';
        });
        data += 'db_type='+db_type+'&';

        $('li.error').remove();
        $databaseInfo.html('&nbsp;').spin(spinOpts);
        ajax('mpf_install.php', data + 'ajax=testDbConnection', 'POST', function (error, result) {
            $databaseInfo.spin(false);
            if (error) {
                showError($databaseInfo, error);
                return;
            }

            $databaseInfo.addClass('success');
            $('div', $databaseInfo.closest('li')).html(check);
            $('#db_forms', $databaseInfo.closest('li')).slideUp(function () {
                $('#db_types', $databaseInfo.closest('li')).slideUp(200, function () {
                    continueDbInstallation();
                });
            });
        });

        function continueDbInstallation() {
            var $createDbConfig = $('#createDbConfig'),
                $createUserTables = $('#createUserTables'),
                $webadminDownload = $('#webadminDownload'),
                $success = $('li.success');

            $createDbConfig.html('&nbsp;').spin(spinOpts);
            ajax('mpf_install.php', 'ajax=createDbConfig', 'GET', function (error, result) {
                $createDbConfig.spin(false);
                if (error) {
                    showError($createDbConfig, error);
                    return;
                }

                $createDbConfig.addClass('success');
                $('div', $createDbConfig.closest('li')).html(check);
                $createUserTables.html('&nbsp;').spin(spinOpts);
                ajax('mpf_install.php', 'ajax=createUserTables', 'POST', function (error, result) {
                    $createUserTables.spin(false);
                    if (error) {
                        showError($createUserTables, error);
                        return;
                    }

                    $createUserTables.addClass('success');
                    $('div', $createUserTables.closest('li')).html(check);
                    $webadminDownload.html('&nbsp;').spin(spinOpts);
                    ajax('mpf_install.php', 'ajax=webadminDownload', 'POST', function (error, result) {
                        $webadminDownload.spin(false);
                        if (error) {
                            showError($webadminDownload, error);
                            return;
                        }

                        $webadminDownload.addClass('success');
                        $('div', $webadminDownload.closest('li')).html(check);
                        $success.show();
                    });
                });
            });
        }

        return false;
    });

    function showError($element, error) {
        $element.addClass('error');
        $('div', $element.closest('li')).html('X');
        $('<li class="error">'+error+'</li>').insertAfter($element.closest('li'));
    }

    if (getParameterByName('webadmin_walkthrough') && <?= (defined('PATH_MPF_CORE') ? 'true' : 'false') ?>) {
        $('#form_'+$('input[name="db_type"]').val()+' input:not(:disabled)').each(function (index, element) {
            var $input = $(element);
            if ($input.val() == "" || $input.val() == $input.attr('data-default')) {
                return;
            }

            $input.addClass('changed');
        });

        $('input[name="webadmin"]').trigger('click', {instant: true});
    }
});
</script>
</body>
</html>