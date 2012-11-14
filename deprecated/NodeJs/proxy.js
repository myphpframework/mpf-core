var io = require('socket.io').listen(1232)
  , config = require('mpf').Config
  , sh = require('child_process').spawn;


// ################################
// ############ Root namespace
io.sockets.on('connection', function (socket) {
  socket.on('config', function () {
    var params = [].slice.call(arguments);
    shell('fetchConfig.php', params, function (output) {
      socket.emit('config:response', JSON.parse(output));
    });
  });

  socket.on('environment', function () {
    socket.emit('environment:response', process.env.MPF_ENV);
  });
});


// ################################
// ############ Template namespace
var templateio = io.of('/mpf-templates');
templateio.on('connection', function (socket) {
  socket.on('request', function () {
    var params = [].slice.call(arguments);
    shell('fetchTemplate.php', params, function (output) {
      socket.emit('response', [output].concat(params));
    });
  });
});


// ################################
// ############ Text namespace
var textio = io.of('/mpf-texts');
textio.on('connection', function (socket) {
  socket.on('request', function () {
    var params = [].slice.call(arguments);
    shell('fetchText.php', params, function (output) {
      socket.emit('response', [output].concat(params));
    });
  });
});


// ################################
// ############ Model namespace
var modelio = io.of('/mpf-models');
modelio.on('connection', function (socket) {
  socket.on('request', function () {
    var params = [].slice.call(arguments);
    shell('fetchModel.php', params, function (output) {
      socket.emit('response', [output].concat(params));
    });
  });
});


function shell(phpFile, params, callback) {
  var php = sh('php', [phpFile].concat(params)), output = '';
  php.stdout.setEncoding('utf-8');
  php.stdout.on('data', function (data) {
    output = output + data;
  });

  php.on('exit', function (code) {
    callback(output);
  });
}

// TODO: Log into file according to config...
//var log = fs.createWriteStream('server.log', {'flags': 'a'});
process.on('uncaughtException', function (e) {
  console.log(e);
  //log.write(e+"\n");
});