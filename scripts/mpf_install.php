<?php

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
    return array('success' => true);
}

function downloadMPF() {
    if (defined('PATH_MPF_CORE')) {
        return array('success' => true);
    }

    $zipFile = '/tmp/mpf-core-'.$_GET['version'].'.zip';
    if (!stream_resolve_include_path($zipFile)) {
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

    if (!stream_resolve_include_path(realpath('../').'/mpf-core')) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            if (!@$zip->extractTo(realpath('../'))) {
                $zip->close();
                return array('success' => false, 'error' => 'Extract <span class="path">'.$zipFile.'</span> to "<span class="path">'.realpath('../').'/</span><span class="filename">mpf-core/</span>".');
            }
            $zip->close();
        }
    }

    if (stream_resolve_include_path(realpath('../').'/mpf-core-'.$_GET['version'])) {
        shell_exec('mv '.realpath('../').'/mpf-core-'.$_GET['version'].' '.realpath('../').'/mpf-core ');
        shell_exec('find '.realpath('../').'/mpf-core -type d -exec chmod 755 {} +');
        shell_exec('find '.realpath('../').'/mpf-core -type f -exec chmod 644 {} +');
    }

    return array('success' => true);
}

function bootstrap() {
    $bootstrapFile = realpath('../').'/bootstrap.php';
    if (!stream_resolve_include_path($bootstrapFile)) {
        if (null === shell_exec('cp '.realpath('../').'/mpf-core/scripts/bootstrap.php '.$bootstrapFile.' && echo "succes"')) {
            return array('success' => false, 'error' => 'Copy <span class="filename">bootstrap.php</span> from <span class="path">mpf-core/scripts/</span> to "<span class="path">'.realpath('../').'/</span><span class="filename">bootstrap.php</span>".');
        }
    }

    return array('success' => true);
}

function htaccess() {
    $htacessFile = realpath('.').'/.htaccess';
    if (!stream_resolve_include_path($htacessFile)) {
        if (null === shell_exec('cp '.realpath('../').'/mpf-core/scripts/.htaccess '.$htacessFile.'  && echo "succes"')) {
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
        if (!isset($_ENV['suPHPInstalled'])) {
            return array('success' => false, 'error' => 'Add the following line <span class="filename">php_value auto_prepend_file "'.$bootstrapFile.'"</span> to the top of the file <span class="path">'.$htacessFile.'</span>.');
        } else {
            $file = str_replace("#suPHP_ConfigPath {path}", "suPHP_ConfigPath ".realpath('../')."/", $file);
            @file_put_contents($htacessFile, $file);
            if (!stream_resolve_include_path($phpIniFile)) {
                @file_put_contents($phpIniFile, 'auto_prepend_file = "'.$bootstrapFile.'"'."\n\n");
            } else {
                $phpIni = @file_get_contents($phpIniFile);
                if (!preg_match('/auto_prepend_file/i', $phpIni)) {
                    @file_put_contents($phpIniFile, 'auto_prepend_file = "'.$bootstrapFile.'"'."\n\n".$phpIni);
                }
            }
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

    if (!defined('PATH_MPF_CORE') || PATH_MPF_CORE == '{PATH_MPF_CORE}' || !stream_resolve_include_path(PATH_MPF_CORE.'init.php')) {
        return array('success' => false, 'error' => 'The constant <span class="filename">PATH_MPF_CORE</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. Make sure the <u>absolute path</u> finishes with a slash "/".');
    }

    if (!defined('PATH_SITE') || PATH_SITE == '{PATH_SITE}' || !stream_resolve_include_path(PATH_SITE.'bootstrap.php')) {
        return array('success' => false, 'error' => 'The constant <span class="filename">PATH_SITE</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. Make sure the <u>absolute path</u> finishes with a slash "/".');
    }

    if (!defined('MPF_ENV') || MPF_ENV == '{MPF_ENV}') {
        return array('success' => false, 'error' => 'The constant <span class="filename">MPF_ENV</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. This constant reflects the current environment the server is in, usual choices are: development,testing,staging,production');
    }

    return array('success' => true);
}

function webadmin() {
    $htacessFile = realpath('.').'/mpf-admin/';
    if (!stream_resolve_include_path($htacessFile)) {
        if (null === shell_exec('cp '.realpath('../').'/mpf-core/scripts/.htaccess '.$htacessFile.'  && echo "succes"')) {
            return array('success' => false, 'error' => 'Copy <span class="filename">.htaccess</span> from <span class="path">mpf-core/scripts/</span> to <span class="path">'.realpath('./').'/</span><span class="filename">.htaccess</span>.');
        }
    }

    return array('success' => true);
}

if (isset($_GET['ajax'])) {
    $result = array('success' => false);
    switch ($_GET['ajax']) {
        case 'dependencies':
            $result = dependencies();
            break;
        case 'downloadMPF':
            $result = downloadMPF();
            break;
        case 'bootstrap':
            $result = bootstrap();
            break;
        case 'htaccess':
            $result = htaccess();
            break;
        case 'configBootstrap':
            $result = configBootstrap();
            break;
        case 'configHtaccess':
            $result = configHtaccess();
            break;
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
    <meta name="description" content="Web interface that lets your administer certain portion of the MyPhpFramework"/>
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
        body > ul > li {
            display: block;
            border-bottom: 1px dotted gray;
            padding: 8px;
            clear:both;
        }
        body > ul > li > div {
            float: left;
            font-size: 28px;
            width: 27px;
            margin-top: -8px;
        }
        body > ul > li > label {
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

        #form_mysql {

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
        <ul>
            <li><input type="radio" name="db_type" checked="checked" value="mysql" id="mysql" /><label for="mysql">MySQL</label></li>
            <li><input type="radio" name="db_type" disabled="disabled" value="postgres" id="postgres" /><label for="postgres">Postgres</label></li>
            <li><input type="radio" name="db_type" disabled="disabled" value="sqlite" id="sqlite" /><label for="sqlite">SQLite</label></li>
        </ul>
        <ul>
            <li id="form_mysql">
                <input type="text" name="db_host" value="localhost" />
                <input type="text" name="db_port" value="3306" />
                <input type="text" name="db_name" value="database name"/>
                <input type="text" name="db_login" value="username" />
                <input type="text" name="db_pwd" value="Password" />
                <input type="button" name="db_test" value="Test Connection" />
            </li>
        </ul>
    </li>
    <li>
        <div id="createUserTables">&#183;</div>
        <label>Creating user and its status tables</label>
    </li>
    <li>
        <div id="databaseInfo3">&#183;</div>
        <label>Configuring bootstrap</label>
    </li>
    <li>
        <div id="databaseInfo4">&#183;</div>
        <label>Configuring bootstrap</label>
    </li>
    <li>
        <div id="webadminDownload">&#183;</div>
        <label>Downloading &amp; Extracting the Web Admin</label>
    </li>
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

function getParameterByName(name)
{
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.search);
  if(results == null)
    return "";
  else
    return decodeURIComponent(results[1].replace(/\+/g, " "));
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
    //https://api.github.com/repos/Bwen/NoobHTTP/git/refs/tags
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
            $configHtaccess = $('#configHtaccess'),
            $success = $('li.success'),
            check = '&#10003;',
            spinOpts = {color: "#000",lines: 10, length: 3, radius: 3, width: 1};

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

        function showError($element, error) {
            $element.addClass('error');
            $('div', $element.closest('li')).html('X');
            $('<li class="error">'+error+'</li>').insertAfter($element.closest('li'));
        }

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

                                $success.show();
                                $('#installWebInterface').show();
                                $('html, body').animate({scrollTop: $(document).height()}, 'slow');
                                var newURL = location.href + (location.search ? "&" : "?") + "webadmin_walkthrough=1";
                                history.replaceState({}, '', newURL);
                            });
                        });
                    });
                });
            });
        });
    });

    $('input[name="webadmin"]').click(function (event, options) {
        $(this).hide();
        var $databaseInfo = $('#databaseInfo'),
            //$success = $('li.success'),
            check = '&#10003;',
            spinOpts = {color: "#000",lines: 10, length: 3, radius: 3, width: 1};

        $databaseInfo.html('&#183;').removeClass('error');
        //$success.hide();
        $('li.error').remove();

        if (options && options.hasOwnProperty('instant')) {
            $('#overview').hide();
            $('#webadmin').show();
        } else {
            $('#overview').slideUp(500, function () {
                $('#webadmin').slideDown(500, function () {
                    $('html, body').animate({scrollTop: $(document).height()}, 'slow');
                });
            });
        }

        function showError($element, error) {
            $element.addClass('error');
            $('div', $element.closest('li')).html('X');
            $('<li class="error">'+error+'</li>').insertAfter($element.closest('li'));
        }

    });

    $('input[name="db_pwd"]').focus(function () {
        var $input = $(this);
        if ($input.prop('type') != 'password') {
            $input.prop('type', 'password');
            $input.val('');
        }
    });
    $('input[name="db_pwd"]').blur(function () {
        var $input = $(this);
        if ($input.val() == "") {
            $input.prop('type', 'text');
            $input.val('Password');
        }
    });

    $('input[name="db_host"]').blur(function () {
        var $input = $(this);
        if ($input.val() == "") {
            $input.val('localhost');
        }
    });

    $('input[name="db_port"]').blur(function () {
        var $input = $(this);
        if ($input.val() == "") {
            $input.val('3306');
        }
    });

    $('input[name="db_name"]').blur(function () {
        var $input = $(this);
        if ($input.val() == "") {
            $input.val('database name');
        }
    });

    $('input[name="db_login"]').blur(function () {
        var $input = $(this);
        if ($input.val() == "") {
            $input.val('username');
        }
    });

    if (getParameterByName('webadmin_walkthrough')) {
        $('input[name="webadmin"]').trigger('click', {instant: true});
    }
});
</script>
</body>
</html>