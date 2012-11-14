var shell = require('child_process').spawn;
var php = shell('php', ['test.php']);

php.stdout.setEncoding('utf-8');
php.stdout.on('data', function (data) {
  output = JSON.parse(data);
  console.log(output.event.name);
});

php.on('exit', function(code) {
  console.log('PHP Process exited with code: '+code);
});
