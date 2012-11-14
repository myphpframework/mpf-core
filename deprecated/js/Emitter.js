var mpf_emitter_index = {
  emit: 0,
  once: 1,
  onceData: 2
};

var mpf_emitter_onceNames = ['ready'];

function mpf_Emitter() {
  this.callbacks = [
    {},
    {},
    {}
  ];
}

mpf_Emitter.prototype.on = function (eventName, callback) {
  var i = mpf_emitter_index.emit;

  // is it once event name
  if (mpf_emitter_onceNames.indexOf(eventName) != -1) {
    // if the event was already triggered we trigger the callback right away
    if (this.callbacks[ mpf_emitter_index.onceData ].hasOwnProperty(eventName)) {
      callback.apply(this, this.callbacks[ mpf_emitter_index.onceData ][ eventName ]);
      return;
    }

    i = mpf_emitter_index.once;
  }

  if (!this.callbacks[ i ].hasOwnProperty(eventName)) {
    this.callbacks[ i ][ eventName ] = [];
  }

  this.callbacks[ i ][ eventName ].push(callback);
};

mpf_Emitter.prototype.emitOnce = function () {
  var eventName = [].splice.call(arguments, 0, 1)[0];
  if (!this.callbacks[ mpf_emitter_index.onceData ].hasOwnProperty(eventName)) {
    if (this.callbacks[ mpf_emitter_index.once ].hasOwnProperty(eventName)) {
      while (this.callbacks[ mpf_emitter_index.once ][ eventName ].length != 0) {
        this.callbacks[ mpf_emitter_index.once ][ eventName ].splice(0, 1)[0].apply(this, arguments);
      }
    }

    this.callbacks[ mpf_emitter_index.onceData ][ eventName ] = arguments;
  }
};

mpf_Emitter.prototype.emit = function () {
  var eventName = [].splice.call(arguments, 0, 1)[0];
  if (this.callbacks[ mpf_emitter_index.emit ].hasOwnProperty(eventName)) {
    for (var i = 0; i < this.callbacks[ mpf_emitter_index.emit ][ eventName ].length; i++) {
      this.callbacks[ mpf_emitter_index.emit ][ eventName ][ i ].apply(this, arguments);
    }
  }
};
