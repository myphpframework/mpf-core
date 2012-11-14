var sockioport = 1232;

mpf_require(['http://'+document.domain+':'+sockioport+'/socket.io/socket.io'], function () {
  var path = document.location.pathname;
  path = path.substring(0, (path.lastIndexOf('/')+1));
  
  window.socketio['root'] = io.connect('http://'+document.domain+':'+sockioport+'/');
  
  window.socketio['root'].on('config:response', function (config) {
    window.mpf.configs = mpf_mergeRecursive(window.mpf.configs, config);
  });

  window.socketio['root'].on('environment:response', function (env) {
    window.mpf.env = env;
  });

  // fetch settings
  window.socketio['root'].emit('config', path, 'settings');
  window.socketio['root'].emit('environment', path, 'settings');
});
