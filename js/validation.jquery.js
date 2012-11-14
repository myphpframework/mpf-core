var countries = ["AF","AL","DZ","AS","AD","AO","AI","AQ","AG","AR","AM","AW","AU","AT","AZ","BS","BH","BD","BB","BY","BE","BZ","BJ","BM","BT","BO","BA","BW","BV","BR","IO","BN","BG","BF","BI","KH","CM","CA","CV","KY","CF","TD","CL","CN","CX","CC","CO","KM","CG","CK","CR","CI","HR","CU","CY","CZ","DK","DJ","DM","DO","TP","EC","EG","SV","GQ","ER","EE","ET","FK","FO","FJ","FI","FR","FX","GF","PF","TF","GA","GM","GE","DE","GH","GI","GR","GL","GD","GP","GU","GT","GN","GW","GY","HT","HM","HN","HK","HU","IS","IN","ID","IR","IQ","IE","IL","IT","JM","JP","JO","KZ","KE","KI","KP","KR","KW","KG","LA","LV","LB","LS","LR","LY","LI","LT","LU","MO","MK","MG","MW","MY","MV","ML","MT","MH","MQ","MR","MU","YT","MX","FM","MD","MC","MN","MS","MA","MZ","MM","NA","NR","NP","NL","AN","NC","NZ","NI","NE","NG","NU","NF","MP","NO","OM","PK","PW","PA","PG","PY","PE","PH","PN","PL","PT","PR","QA","RE","RO","RU","RW","KN","LC","VC","WS","SM","ST","SA","SN","SC","SL","SG","SK","SI","SB","SO","ZA","GS","ES","LK","SH","PM","SD","SR","SJ","SZ","SE","CH","SY","TW","TJ","TZ","TH","TG","TK","TO","TT","TN","TR","TM","TC","TV","UG","UA","AE","GB","US","UM","UY","UZ","VU","VA","VE","VN","VG","VI","WF","EH","YE","YU","ZR","ZM","ZW","ME","RS"],
    statesCA = ["AB","BC","PE","MB","NB","NS","NV","ON","QC","SK","NL","NT","YK"],
    statesUS = ["AL","AK","AZ","AR","CA","CO","CT","DE","FL","GA","HI","ID","IL","IN","IA","KS","KY","LA","ME","MD","MA","MI","MN","MS","MO","MT","NE","NV","NH","NJ","NM","NY","NC","ND","OH","OK","OR","PA","RI","SC","SD","TN","TX","UT","VT","VA","WA","WV","WI","WY","DC"],
    validations = {
    creditcard: function (element) {
        var value = $(element).val().replace(/[^0-9]+/, '').substr(0, 16);

        if (element.value != value) {
            element.value = value;
        }

        var cardDigits = element.value.split('');
        if (cardDigits.length >= 13 && cardDigits.length <= 16) {
            var total = 0;
            cardDigits.reverse().forEach(function (digit, index) {
                if ((index % 2) != 0) {
                    var timestwo = (parseInt(digit) * 2);
                    if (timestwo > 9) {
                        timestwo.toString().split('').forEach(function (digit) {
                            total += parseInt(digit);
                        });
                    } else {
                        total += timestwo;
                    }
                } else {
                    total += parseInt(digit);
                }
            });

            if ((total % 10) == 0) {
                return true;
            }
        }
        return false;
    },
    country: function (element) {
        var $element = $(element),
            $form = $element.closest('form'),
            state = $('[name=state]', $form).val(),
            country = $('[name=country]', $form).val();

        if (countries.indexOf(country) === -1) {
            return false;
        }

        if (!window.hasOwnProperty("states"+country) || window["states"+country].indexOf(state) === -1) {
            $('[name=state]', $element.closest('form')).val('0');
        }

        return true;
    },
    state: function (element) {
        var $element = $(element),
            country = $('[name=country]', $element.closest('form')).val(),
            state = $(element).val();

        // state is optionnal except for US & CA
        if (state == 0 && country != 'US' && country != 'CA') {
            return true;
        }

        if (window.hasOwnProperty("states"+country) && window["states"+country].indexOf(state) !== -1) {
            return true;
        }

        return false;
    },
    phone: function (element) {
        var value = $(element).val().replace(/[^0-9+\.x]+/, '');
        if (element.value != value) {
            element.value = value;
            return false;
        }

        if (element.value.length < 10 && element.value != "") {
            return false;
        }
        return true;
    },
    email: function (element) {
        if ($(element).val().match(/.{1}@.{2}/)) {
            return true;
        }
        return false;
    },
    password: function (element) {
        var value = $(element).val();
        if (value.length < 8 && value.length != 0) {
            return false;
        }
        return true;
    },
    passwordConfirm: function (element) {
        // find the password field
        var formName = $(element).closest('form').attr('name'), passwordField = null;
        formsValidations[ formName ].forEach(function (field) {
            if (field.element.getAttribute('type') == 'password'
              && field.element.getAttribute('name') != element.getAttribute('name')) {
                passwordField = field;
            }
        });

        // verify if the it matchs the password
        if (passwordField && $(passwordField.element).val() == $(element).val()) {
            return true;
        }

        return false;
    },
    numeric: function (element) {
        var value = $(element).val().replace(/[^0-9]+/, '');
        if (element.value != value) {
            element.value = value;
            return false;
        }
        return true;
    },
    name: function (element) {
        var value = $(element).val().replace(/[^a-zA-Z0-9áãåàâäçéëèêíïìîñóõòôöùûúüýÿßæðøÁÃÅÀÂÄÇÉËÈÊÍÏÌÎÓÕÒÔÖÙÛÚÜÝ?., \-\']/, '')
        if (element.value != value) {
            element.value = value;
            return false;
        }
        return true;
    },
    alphanumeric: function (element) {
        var value = $(element).val().replace(/[^a-zA-Z0-9áãåàâäçéëèêíïìîñóõòôöùûúüýÿßæðø]+/, '');
        if (element.value != value) {
            element.value = value;
            return false;
        }
        return true;
    },
    required: function (element) {
        if (element.getAttribute('type') == 'checkbox') {
            // if its a checkbox we need to verify other box to see if atleast one is checked
            var isChecked = false;
            $('[name="'+element.getAttribute('name')+'"]', $(element).closest('form')).each(function (index, element) {
                if (element.checked) {
                    isChecked = true;
                    return;
                }
            });
            return isChecked;
        }

        if (element.getAttribute('data-value') && $(element).val() == element.getAttribute('data-value')) {
            return false;
        }

        return ($(element).val() == '' ? false : true);
    }
}, formsValidations = {};

$.fn.unvalidate = function removeValidate() {
    return this.stop().each(function() {
        var formName = $(this).closest('form').attr('name');
        formName = (typeof formName == 'undefined' ? 'default' : formName);
        if (!formsValidations.hasOwnProperty(formName)) {
            return;
        }

        // find the element en remove it from the checks
        for (var i=0; i < formsValidations[formName].length; i++) {
            if (formsValidations[formName][i].element == this) {
                formsValidations[formName].splice(i, 1);
                return;
            }
        }
    });
};

$.fn.validate = function validate() {
    var args = Array.prototype.slice.call(arguments),
        callback = null,
        eventString = '',
        checkString = '';

    if (typeof args[args.length-1] == 'function') {
        callback = args.pop();
    }
    checkString = (args.length <= 2 ? args[0] : '');
    eventString = (args.length >= 1 ? args[1] : '');

    return this.stop().each(function() {
        var checks = (checkString ? checkString.split(' ') : []),
            element = this;

        var formName = $(element).closest('form').attr('name');
        formName = (typeof formName == 'undefined' ? 'default' : formName);
        if (!formsValidations.hasOwnProperty(formName)) {
            formsValidations[formName] = [];
        }

        // if we call validate on a form element we validate all fields that have events on them
        if ('form' == element.tagName.toLowerCase()) {
            var isAllValid = true, invalidFields = [];

            // for all events that were bound we verify them
            for (var i=0; i < formsValidations[formName].length; i++) {
                var isFieldValid = true, invalidChecks = [], event = formsValidations[formName][i];
                if (event.checks.indexOf('required') != -1 && !validations.required(event.element)) {
                    invalidChecks.push('required');
                    isFieldValid = false;
                    isAllValid = false;
                } else {
                    // verify all the checks for this element
                    for (var j=0; j < event.checks.length; j++) {
                        var check = event.checks[j];
                        if (check != 'required' && validations.hasOwnProperty(check)) {
                            var isValid = validations[check](event.element);
                            isFieldValid &= isValid;
                            isAllValid &= isValid;

                            // we keep try of the checks that failed for this element
                            if (!isFieldValid) {
                                invalidChecks.push(check);
                            }
                        }
                    }
                }

                // for the form even there is a second argument that gives the list of element that failed the checks
                if (!isFieldValid) {
                    invalidFields.push({
                        element: event.element,
                        failedChecks: invalidChecks
                    });
                }
            };

            if (callback != null) {
                callback.call(element, isAllValid, invalidFields);
            }
            return;
        }

        // verify if the check is not already added before adding it again
        var alreadyBound = false;
        for (var i=0; i < formsValidations[formName].length; i++) {
            if (formsValidations[formName][i].element == element) {
                alreadyBound = true;
            }
        }

        if (!alreadyBound) {
            formsValidations[formName].push({
                eventString: eventString,
                checks: checks,
                element: element
            });
        }

        if (checks.length > 0) {
            $(element).bind(eventString, function (event) {
                var isValid = true;
                for (var i=0; i < checks.length; i++) {
                    var check = checks[i];
                    if (validations.hasOwnProperty(check)) {
                        isValid &= validations[check](element);
                    }
                }

                if (callback != null) {
                    callback.call(element, isValid);
                }
            });
        }
    });
};

$.fn.addFormErrors = function addFormErrors(invalidFields) {
    var $form = $(this), fieldNames = [];
    $('.error', $form).remove();
    invalidFields.forEach(function (field) {
        var $errors = $('<ul class="error">'), $element = $(field.element);

        // Custom error ?
        if (field.hasOwnProperty('errorMsg')) {
            $errors.append('<li>'+ field.errorMsg +'</li>');
        } else {
            field.failedChecks.forEach(function (check) {
                $errors.append('<li>'+ mpf.text('mpf_validations', check) +'</li>');
            });
        }

        // if we already output an error message for this field we dont do it again (Checkbox, radio bottons, etc...)
        if (fieldNames.indexOf($element.attr('name')) === -1) {
            fieldNames.push($element.attr('name'));
            if ($element.attr('type') == 'checkbox') {
                $errors.insertBefore(field.element);
            } else {
                $errors.insertAfter(field.element);
            }
        }
    });
}

// load texts for the validations
mpf.text('mpf_validations');