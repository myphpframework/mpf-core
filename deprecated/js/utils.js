
function mpf_requireFiles() {
  var jsRequireMarkups = $('[data-mpf-jsrequire]');
  for (var i=0; i < jsRequireMarkups.length; i++) {
    var element = jsRequireMarkups[ i ];
    mpf_require(element.getAttribute('data-mpf-jsrequire'));
    element.removeAttribute('data-mpf-jsrequire');
  }
}

function mpf_instanciateWidgets () {
  var widgetMarkups = $('[data-mpf-widget-name]');
  for (var i=0; i < widgetMarkups.length; i++) {
    var widgetName = widgetMarkups[ i ].getAttribute('data-mpf-widget-name');

    if (!window.mpf.widgets.hasOwnProperty(widgetName)) {
      var widgetId = mpf_guidGenerator();
      
      widgetMarkups[ i ].setAttribute('data-mpf-widget-id', widgetId);
      widgetMarkups[ i ].removeAttribute('data-mpf-widget-name');
      window.mpf.widgets[ widgetName ] = new mpf_Widget(widgetId, widgetName, widgetMarkups[ i ]);
      window.mpf.widgets[ widgetName ].init();
    }
  }
}

function mpf_getCookie(name) {
  var rawCookies = document.cookie.split(';');
  for (var i=0; i < rawCookies.length; i++) {
    var keyValue = rawCookies[i].split('=');
    if (keyValue[0] == name) {
      return keyValue[1];
    }
  }
  return null;
}

function mpf_mergeRecursive(obj1, obj2) {
  for (var p in obj2) {
    try {
      // Property in destination object set; update its value.
      if ( obj2[p].constructor==Object ) {
        obj1[p] = mpf_mergeRecursive(obj1[p], obj2[p]);
      }
      else {
        obj1[p] = obj2[p];
      }
    }
    catch(e) {
      // Property in destination object not set; create it and set its value.
      obj1[p] = obj2[p];
    }
  }

  return obj1;
}

function mpf_guidGenerator() {
    var S4 = function() {
       return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
    };
    return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
}