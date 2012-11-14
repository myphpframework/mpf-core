
function mpf_TextFiles(filename) {
  this.callbacks = [
    {},
    {},
    {}
  ];
  
  this.path = document.location.pathname;
  this.path = this.path.substring(0, (this.path.lastIndexOf('/')+1));
  this.textIds = {};
  this.texts = {};
  this.locale = mpf_getCookie('mpf_locale');
  if (!this.locale) {
    this.locale = window.mpf.configs.defaults.locale;
  }
  
  var self = this;
  if (!window.socketio.hasOwnProperty('mpf-texts')) {
    window.socketio['mpf-text'] = io.connect('http://'+document.domain+':'+sockioport+'/mpf-texts');
    window.socketio['mpf-text'].on('response', function (args) {

      var text = args[0]
        , path = args[1]
        , name = args[2]
        , textId = args[3]
        , callbackId = args[4]
        , index = path + name
        , textMarkups = $('[data-mpf-text-id="'+textId+'"]');

        for (var i=0; i < textMarkups.length; i++) {
            textMarkups[i].innerHTML = text;
        }
  
        if (localStorage !== undefined && window.mpf.configs.localStorage) {
           localStorage.setItem('mpf-texts:'+index, text);
        }

        self.callbackIds[callbackId].count++;

        if (self.callbackIds[callbackId].count >= self.callbackIds[callbackId].length) {
            for (i in self.markupCallbacks[callbackId]) {
                self.markupCallbacks[callbackId][i]();
            }
        }
    });
  }
  
}

mpf_TextFiles.prototype = mpf_Emitter.prototype;
mpf_TextFiles.prototype.markupCallbacks = {};
mpf_TextFiles.prototype.callbackIds = {};

mpf_TextFiles.prototype.parseElement = function parseElement(element, callbackId, callback) {
  if (callback === undefined || typeof callback != 'function') {
    console.error('Function TextFiles.parseElement requires the third argument to be a callback function');
    return;
  }

  var path = document.location.pathname, self = this, textMarkups = $('[data-mpf-text]', element);
  path = path.substring(0, (path.lastIndexOf('/')+1));
  
  if (!textMarkups) {
      callback();
      return;
  }

  if (!this.markupCallbacks.hasOwnProperty(callbackId)) {
    this.markupCallbacks[callbackId] = [];
  }
  this.markupCallbacks[callbackId].push(callback);
  
  if (!this.callbackIds.hasOwnProperty(callbackId)) {
    this.callbackIds[callbackId] = {
        length: textMarkups.length,
        count: 0
    };
  }
  this.callbackIds[callbackId].length = textMarkups.length;
  this.callbackIds[callbackId].count = 0;
  
  for (var i=0; i < textMarkups.length; i++) {
    var name = textMarkups[i].getAttribute('data-mpf-text'),
        textId = this.getTextId(name);
        index = path + name;

    textMarkups[i].removeAttribute('data-mpf-text');
    textMarkups[i].setAttribute('data-mpf-text-id', textId);

    if (this.texts.hasOwnProperty(textId)) {
      textMarkups[i].innerHTML = this.texts[textId];
      this.callbackIds[callbackId].count++;
    }
    else if (localStorage !== undefined && window.mpf.configs.localStorage && localStorage.getItem('mpf-texts:'+index)) {
      var text = localStorage.getItem('mpf-texts:'+index);
      textMarkups[i].innerHTML = text;
      this.callbackIds[callbackId].count++;
    }
    else {
      window.socketio['mpf-text'].emit('request', path, name, textId, callbackId);
    }
  }
  
  if (this.callbackIds[callbackId].count >= this.callbackIds[callbackId].length) {
      for (i in this.markupCallbacks[callbackId]) {
          this.markupCallbacks[callbackId][i]();
      }
  }
}

mpf_TextFiles.prototype.getTextId = function getTextId(index) {
  if (!this.textIds.hasOwnProperty(this.path+index)) {
    this.textIds[ this.path+index ] = mpf_guidGenerator();
  }
  return this.textIds[ this.path+index ];
};


window.mpf.texts = new mpf_TextFiles();