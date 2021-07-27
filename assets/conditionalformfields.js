;(function ($, window, document, undefined) {
    "use strict";

    // Create the defaults once
    let pluginName = "conditionalFormFields";
    let defaults = {
        fields: [],
        conditions: {},
        previousValues: {}
    };

    // The actual plugin constructor
    function ConditionalFormFields (element, options) {
        this.element = element;
        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    function htmlDecode(i){
        let e = document.createElement('textarea');
        e.innerHTML = i;
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
    }

    // Avoid Plugin.prototype conflicts
    $.extend(ConditionalFormFields.prototype, {
        init: function() {
            let $this = this;

            $this.initFields();
            $this.initFieldsets();

            $(this.element).on('ajax_change', function() {
                $this.initFields();
                $this.initFieldsets();
            });
        },
        initFields: function() {
            let $this = this;

            $this.fields = {};

            $($this.settings.fields).each(function(i, field) {
                $($this.element).find('*[name^="' + field + '"]').each(function() {
                    let field = $(this);
                    let name = field.attr('name');

                    // Array
                    if (name.substr(name.length - 2) == '[]') {
                        name = name.substr(0, name.length - 2);

                        if (!($this.fields[name] instanceof Array)) {
                            $this.fields[name] = [];
                        }

                        $this.fields[name].push(field);
                    } else if (field.attr('type') === 'radio') {
                        // Radio
                        if (!($this.fields[name] instanceof Array)) {
                            $this.fields[name] = [];
                        }

                        $this.fields[name].push(field);
                    } else {
                        // Regular field
                        $this.fields[name] = field;
                    }

                    $(this).on('change', function() {
                        $this.toggleFieldsets();
                    });
                });
            });
        },
        initFieldsets: function() {
            let $this = this;

            $this.fieldsets = {};
            $this.fieldsetFields = {};

            for (let id in $this.settings.conditions) {
                $this.fieldsets[id] = $('.cffs-' + id);
                $this.fieldsetFields[id] = $this.fieldsets[id].find('input, select, textarea');
            }

            $this.toggleFieldsets();
        },
        toggleFieldsets: function() {
            let $this = this;
            let values = $this.getFieldValues();

            for (let id in $this.settings.conditions) {
                let condition = 'let in_array = function(needle, haystack) { return jQuery.isArray(haystack) ? (jQuery.inArray(needle, haystack) != -1) : false; }, ' +
                    'in_string = function(needle, haystack) { return haystack.includes(needle); }; ' + htmlDecode($this.settings.conditions[id]);
                let fn = new Function('values', condition);

                if (!fn(values)) {
                    $this.hideFieldset(id);
                } else {
                    $this.showFieldset(id);
                }
            }
        },
        showFieldset: function(id) {
            this.fieldsets[id].show();

            $(this.fieldsetFields[id]).each(function() {
                $(this).attr('disabled', false);
            });
        },
        hideFieldset: function(id) {
            this.fieldsets[id].hide();

            $(this.fieldsetFields[id]).each(function() {
                $(this).attr('disabled', true);
            });
        },
        getFieldValues: function() {
            let $this = this;
            let values = Object.assign({}, $this.settings.previousValues);
            let value = null;

            $.each($this.fields, function(name) {
                if ($.isArray(this)) {
                    $(this).each(function() {
                        // Radio
                        if ($(this).attr('type') === 'radio') {
                            value = $this.getFieldValue(this);

                            if (value) {
                                values[name] = value;
                                return false; // break the loop
                            }
                        } else {
                            // Others
                            if (undefined === values[name]) {
                                values[name] = [];
                            }

                            value = $this.getFieldValue(this);

                            if (value) {
                                values[name].push(value);
                            }
                        }
                    });
                } else {
                    values[name] = $this.getFieldValue(this);
                }
            });

            return values;
        },
        getFieldValue: function(el) {
            el = $(el);

            if (el.attr('type') == 'checkbox' || el.attr('type') == 'radio') {
                return el.is(':checked') ? el.val() : null;
            }

            return el.val();
        }
    });

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        return this.each(function() {
            if (!$.data(this, 'plugin_' + pluginName)) {
                $.data(this, 'plugin_' + pluginName, new ConditionalFormFields(this, options));
            }
        });
    };
})(jQuery, window, document);
