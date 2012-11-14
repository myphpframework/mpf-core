var requiredFiles = {};

if (typeof jQuery == 'undefined') {
    alert('MPF requires JQuery library in other to work properly.');
}

// old browsers do not define console
if (console == undefined) {
    var console = {
        log: function () {},
        error: function () {}
    }
}

window.mpf = {};
window.mpf.configs = {};
window.mpf.widgets = {};
window.socketio = {};

function mpf_require(files, callback) {
  var head, script, files, allRequiredAlready = 0, indexesRequired = [], requireCallback, requireTimeout = null;

  // make it so we can accept an array or a single string
  files = [].concat( files );

  for (var i=0; i < files.length; i++) {
    if (requiredFiles.hasOwnProperty(files[i])) {
      allRequiredAlready++;
    }
  }

  if (files.length == allRequiredAlready && typeof callback == 'function') {
    callback();
    return;
  }

  requireTimeout = setTimeout(function () {
    var failedFiles = [];
    for (var i=0; i < files.length; i++) {
      if (indexesRequired.indexOf(i) === -1) {
        failedFiles.push(files[i]);
      }
    }
    console.error('Could not require the following files:', failedFiles);
  }, 3000);

  requireCallback = function (index) {
    indexesRequired.push(index);
    if (files.length == indexesRequired.length) {
      if (typeof callback == 'function') {
        callback();
      }
      clearTimeout(requireTimeout);
    }
  };

  for (var i=0; i < files.length; i++) {

    // if we already required this class we callback right away and continue,
    // avoid duplicate <script> tags for the same file
    if (requiredFiles.hasOwnProperty(files[i])) {
        requireCallback(i);
       continue;
    }

    head = document.getElementsByTagName('head')[0],
    script = document.createElement('script');
    script.type = 'text/javascript';

    (function (index) {
      script.onreadystatechange = function () {
        if (this.readyState == 'complete') {
          requireCallback(index);
        }
      };
    })(i);
    
    (function (index) {
      script.onload = function () {
        requireCallback(index);
      }
    })(i);

    script.src = files[i]+'.js';
    head.appendChild(script);

    requiredFiles[ files[i] ] = true;
  }
}

var scriptRequire = $('[data-mpf-jsmain]')[0];
mpf_require([scriptRequire.getAttribute('data-mpf-jsmain'), '/js/mpf/utils', '/js/mpf/Emitter'], function () {
  // we set an interval to let the configs get initiated properly
  var requireInterval = setInterval(function () {
    if (window.mpf.configs.hasOwnProperty('localStorage')) {
      clearInterval(requireInterval);
      mpf_requireFiles();
    }
  }, 10);
});

