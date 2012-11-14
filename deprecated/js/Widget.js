function mpf_Widget(id, name, element) {
  this.element = element;
  this.id = id;
  this.name = name;
  this.callbacks = [
    {},
    {},
    {}
  ];

  this.isTempateReady = false;
  this.isTextReady = false;
  this.isModelReady = false;
  this.readyInterval = null;
}

mpf_Widget.prototype = mpf_Emitter.prototype;
mpf_Widget.prototype.templates = null;
mpf_Widget.prototype.markups = {};
mpf_Widget.prototype.init = function init() {
  if (!this.element.getAttribute('data-mpf-widget-template')) {
    console.error('Could not initiate Widget "'+this.name+'", missing argument "data-mpf-widget-template"');
    return;
  }

  var self = this;
  window.mpf.templates.fetch(this.element.getAttribute('data-mpf-widget-template'), function (markup) {
    $(self.element).append(markup);
    self.isTemplateReady = true;

    window.mpf.texts.parseElement(self.element, self.id, function () {
      self.isTextReady = true;
    });

    self.isModelReady = true;
  });
  
  this.readyInterval = setInterval(function () {
    if (self.isTemplateReady && self.isTextReady && self.isModelReady) {
      clearInterval(self.readyInterval);
      self.readyInterval = null;
      self.emitOnce('ready');
    }
  }, 100);
}

if (typeof window.socketio != 'object') {
  console.error('MPF js client requires main.js to be loaded first through require.js');
}
else {
  mpf_require(['/js/mpf/Templates', '/js/mpf/TextFiles', '/js/mpf/Models'], function () {
    mpf_instanciateWidgets();
  });
}
