var mpf = {}, mpfTexts = {}, mpfRestUrl = '/rest/';

mpf.ajaxForm = function ajaxForm($form, callback) {
    var data = [];

    $('[type="radio"]:checked', $form).each(function (index, element) {
        var $element = $(element);
        data.push($element.attr('name') +'='+ $element.val());
    });

    $('[type="text"],[type="password"],textarea,select').each(function (index, element) {
        var $element = $(element);
        data.push($element.attr('name') +'='+ $element.val());
    });

    mpf.ajax($form.attr('action'), data.join('&'), $form.attr('method'), callback);
};

mpf.ajax = function ajax(url, querystring, method, callback) {
    var args = Array.prototype.slice.call(arguments),
        callback = args.pop(),
        url = args.shift(),
        querystring = (args.length == 0 ? '' : args.shift()),
        method = (args.length == 0 ? 'POST' : args.shift());

    querystring = (!querystring ? 'PWD='+encodeURIComponent(document.location.href) : querystring+'&PWD='+encodeURIComponent(document.location.href));

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

mpf.ajaxGet = function get(url, callback) {
    mpf.ajax(url, null, 'GET', callback);
};

mpf.locale = function () {
    if ($.cookie('mpf_locale')) {
        return $.cookie('mpf_locale');
    }

    return 'en_CA';
};

mpf.text = function text(filename, id) {
    if (mpfTexts.hasOwnProperty(filename) && mpfTexts[filename].hasOwnProperty(id)) {
        return mpfTexts[filename][id];
    }

    var localStorageIndex = mpf.locale() +':'+ filename;
    if (typeof localStorage != 'undefined' && localStorage.hasOwnProperty(localStorageIndex)) {
        mpfTexts[ filename ] = JSON.parse(localStorage[ localStorageIndex ]);
        return mpfTexts[ filename ][id];
    }

    mpf.ajaxGet(mpfRestUrl+'text/'+filename, function (error, response) {
        if (error) {
            console.error(error);
            return;
        }

        mpfTexts[filename] = response;
        if (typeof localStorage != 'undefined') {
            localStorage[ localStorageIndex ] = JSON.stringify(response);
        }
    });
};

