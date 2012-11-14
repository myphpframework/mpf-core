
function mpf_Templates() {
  var self = this;
  if (!window.socketio.hasOwnProperty('mpf-template')) {
    window.socketio['mpf-template'] = io.connect('http://'+document.domain+':'+sockioport+'/mpf-templates');
    window.socketio['mpf-template'].on('response', function (args) {
      var elements = []
        , markup = args[0]
        , name = args[2]
        , path = args[1];

        self.assignMarkup(path+':'+name, markup, function () {
          self.triggerCallback(path+':'+name);
        });
    });
  }
  
};

mpf_Templates.prototype.markups = {};
mpf_Templates.prototype.markupCallbacks = {};

mpf_Templates.prototype.assignMarkup = function assignMarkup(index, markup, callback) {
  if (localStorage !== undefined && window.mpf.configs.localStorage) {
    localStorage.setItem('mpf-templates:'+index, markup);
  }

  this.markups[index] = markup
  callback();
};

mpf_Templates.prototype.triggerCallback = function triggerCallback(index) {
  if (!this.markupCallbacks.hasOwnProperty(index)) {
    console.error('Could not trigger Callback, undefined index:', index);
    return;
  }
  
  if (!this.markups.hasOwnProperty(index)) {
    console.error('Could not trigger Callback, markup has no index:', index);
    return;
  }
  
  for (var i=0; i < this.markupCallbacks[index].length; i++) {
    this.markupCallbacks[index].pop()(this.markups[index]);
  }
};

mpf_Templates.prototype.fetch = function fetch(name, callback) {
  var path = document.location.pathname, self = this;
  path = path.substring(0, (path.lastIndexOf('/')+1));
  var index = path+':'+name;
  
  if (callback === undefined || typeof callback != 'function') {
    console.error('Function Templates.fetch requires the second argument to be a callback function');
    return;
  }
  
  if (!this.markupCallbacks.hasOwnProperty(index)) {
    this.markupCallbacks[index] = [];
  }
  
  this.markupCallbacks[index].push(callback);
  if (this.markups.hasOwnProperty(index)) {
    this.triggerCallback(index);
  }
  else if (localStorage !== undefined && window.mpf.configs.localStorage && localStorage.getItem('mpf-templates:'+index)) {
    var markup = localStorage.getItem('mpf-templates:'+index);
    this.assignMarkup(index, markup, function () {
      self.triggerCallback(index);
    });
  }
  else {
    window.socketio['mpf-template'].emit('request', path, name);
  }
}

window.mpf.templates = new mpf_Templates();