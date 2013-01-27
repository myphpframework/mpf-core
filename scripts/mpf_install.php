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

function bootstrap() {
    $bootstrapFile = realpath('../').'/bootstrap.php';
    if (!stream_resolve_include_path($bootstrapFile)) {
        return array('success' => false, 'error' => 'Copy <span class="filename">bootstrap.php</span> from <span class="path">mpf-core/scripts/</span> to "<span class="path">'.realpath('../').'/</span><span class="filename">bootstrap.php</span>".');
    }

    return array('success' => true);
}

function htaccess() {
    $htacessFile = realpath('.').'/.htaccess';
    if (!stream_resolve_include_path($htacessFile)) {
        return array('success' => false, 'error' => 'Copy <span class="filename">.htaccess</span> from <span class="path">mpf-core/scripts/</span> to <span class="path">'.realpath('../').'/</span><span class="filename">.htaccess</span>.');
    }

    return array('success' => true);
}

function configHtaccess() {
    $bootstrapFile = realpath('../').'/bootstrap.php';
    $htacessFile = realpath('.').'/.htaccess';
    if (!defined('PATH_MPF_CORE') || PATH_MPF_CORE == '') {
        return array('success' => false, 'error' => 'Add the following line <span class="filename">php_value auto_prepend_file "'.$bootstrapFile.'"</span> to the top of the file <span class="path">'.$htacessFile.'</span>.');
    }

    return array('success' => true);
}

function configBootstrap() {
    $bootstrapFile = realpath('../').'/bootstrap.php';
    if (!defined('PATH_MPF_CORE') || PATH_MPF_CORE == '' || !stream_resolve_include_path(PATH_MPF_CORE.'init.php')) {
        return array('success' => false, 'error' => 'The constant <span class="filename">PATH_MPF_CORE</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. Make sure the <u>absolute path</u> finishes with a slash "/".');
    }

    if (!defined('PATH_SITE') || PATH_SITE == '' || !stream_resolve_include_path(PATH_SITE.'bootstrap.php')) {
        return array('success' => false, 'error' => 'The constant <span class="filename">PATH_SITE</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. Make sure the <u>absolute path</u> finishes with a slash "/".');
    }

    if (!defined('MPF_ENV') || MPF_ENV == '') {
        return array('success' => false, 'error' => 'The constant <span class="filename">MPF_ENV</span> is not properly set in <span class="path">'.$bootstrapFile.'</span>. This constant reflects the current environment the server is in, usual choices are: development,testing,staging,production');
    }

    return array('success' => true);
}

if (isset($_GET['ajax'])) {
    $result = array('success' => false);
    switch ($_GET['ajax']) {
        case 'dependencies':
            $result = dependencies();
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
        body {
            width: 450px;
            margin: 0 auto;
            border: 1px solid #AAA;
            margin-top: 12px;
            padding: 8px;
        }

        img {
            vertical-align: middle;
            margin-right: 24px;
        }
        h3 {
            text-align: center;
        }
        ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        li {
            display: block;
            border-bottom: 1px dotted gray;
            padding: 8px;
            clear:both;
        }
        li > div {
            float: left;
            width: 20px;
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
        #webadmin {
            color: #888;
        }
    </style>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <script type="text/javascript" src="//fgnass.github.com/spin.js/dist/spin.min.js"></script>
</head>
<body>
<h3><img src="http://myphpframework.self/images/logo_small_blue.png" alt="myPhpFramework Logo" width="87" height="43"/>Installation Walkthrough</h3>
<ul id="overview">
    <li>
        <div id="dependencies">&nbsp;</div>
        <label>Checking Dependencies</label>
    </li>
    <li>
        <div id="bootstrap">&nbsp;</div>
        <label>Copying Framework Bootstrap</label>
    </li>
    <li>
        <div id="htaccess">&nbsp;</div>
        <label>Copying Framework .htaccess</label>
    </li>
    <li>
        <div id="configHtaccess">&nbsp;</div>
        <label>Configuring .htaccess</label>
    </li>
    <li>
        <div id="configBootstrap">&nbsp;</div>
        <label>Configuring bootstrap</label>
    </li>
    <li id="webadmin">
        <div>?</div>
        <label>To install the web admin just copy the folder <span class="filename">mpf-admin</span> from <span class="filename">mpf-core/scripts/</span> to any public accessible directory.</label>
    </li>
    <li class="success" style="display:none;" >Installation Completed</li>
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

function ajax(url, querystring, method, callback) {
    var args = Array.prototype.slice.call(arguments),
        callback = args.pop(),
        url = args.shift(),
        querystring = (args.length == 0 ? '' : args.shift()),
        method = (args.length == 0 ? 'POST' : args.shift());

    $.ajax({
        type: method,
        url: url,
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

            if (response.hasOwnProperty('success') && response.success) {
                callback(null, response);
            } else if (response.hasOwnProperty('error')) {
                callback(response.error, response);
            } else {
                callback('Unexpected error', response);
            }
        }
    });
};

$(document).ready(function () {
    var $dependencies = $('#dependencies'),
        $bootstrap = $('#bootstrap'),
        $htaccess = $('#htaccess'),
        $configBootstrap = $('#configBootstrap'),
        $configHtaccess = $('#configHtaccess'),
        $success = $('li.success'),
        check = '&#10003;',
        spinOpts = {color: "#000",lines: 10, length: 3, radius: 3, width: 1};

    function showError($element, error) {
        $element.addClass('error');
        $('div', $element.closest('li')).html('X');
        $('<li class="error">'+error+'</li>').insertAfter($element.closest('li'));
    }

    $dependencies.spin(spinOpts);
    ajax('mpf_install.php', 'ajax=dependencies', 'GET', function (error, result) {
        $dependencies.spin(false);
        if (error) {
            showError($dependencies, error);
            return;
        }

        $dependencies.addClass('success');
        $('div', $dependencies.closest('li')).html(check);
        $bootstrap.spin(spinOpts);
        ajax('mpf_install.php', 'ajax=bootstrap', 'GET', function (error, result) {
            $bootstrap.spin(false);
            if (error) {
                showError($bootstrap, error);
                return;
            }

            $bootstrap.addClass('success');
            $('div', $bootstrap.closest('li')).html(check);
            $htaccess.spin(spinOpts);
            ajax('mpf_install.php', 'ajax=htaccess', 'GET', function (error, result) {
                $htaccess.spin(false);
                if (error) {
                    showError($htaccess, error);
                    return;
                }

                $htaccess.addClass('success');
                $('div', $htaccess.closest('li')).html(check);
                $configHtaccess.spin(spinOpts);
                ajax('mpf_install.php', 'ajax=configHtaccess', 'GET', function (error, result) {
                    $configHtaccess.spin(false);
                    if (error) {
                        showError($configHtaccess, error);
                        return;
                    }

                    $configHtaccess.addClass('success');
                    $('div', $configHtaccess.closest('li')).html(check);
                    $configBootstrap.spin(spinOpts);
                    ajax('mpf_install.php', 'ajax=configBootstrap', 'GET', function (error, result) {
                        $configBootstrap.spin(false);
                        if (error) {
                            showError($configBootstrap, error);
                            return;
                        }

                        $configBootstrap.addClass('success');
                        $('div', $configBootstrap.closest('li')).html(check);

                        $success.show();
                    });
                });
            });
        });
    });

});
</script>
</body>
</html>