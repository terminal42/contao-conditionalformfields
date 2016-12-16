;(function ($, window, document, undefined) {
    "use strict";

    // Create the defaults once
    var pluginName = "conditionalFormFields";
    var defaults = {
        fields: [],
        conditions: {}
    };

    // The actual plugin constructor
    function ConditionalFormFields (element, options) {
        this.element = element;
        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    // Avoid Plugin.prototype conflicts
    $.extend(ConditionalFormFields.prototype, {
        init: function() {
            var $this = this;

            $this.initFields();
            $this.initFieldsets();

            $(this.element).on('ajax_change', function() {
                $this.initFields();
                $this.initFieldsets();
            });
        },
        initFields: function() {
            var $this = this;

            $this.fields = {};

            $($this.settings.fields).each(function(i, field) {
                $($this.element).find('*[name^="' + field + '"]').each(function() {
                    var field = $(this);
                    var name = field.attr('name');

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
            var $this = this;

            $this.fieldsets = {};
            $this.fieldsetFields = {};

            for (var id in $this.settings.conditions) {
                $this.fieldsets[id] = $('.cffs-' + id);
                $this.fieldsetFields[id] = $this.fieldsets[id].find('input, select, textarea');
            }

            $this.toggleFieldsets();
        },
        toggleFieldsets: function() {
            var $this = this;
            var values = $this.getFieldValues();

            for (var id in $this.settings.conditions) {
                var condition = 'var in_array = function(needle, haystack) { return jQuery.isArray(haystack) ? (jQuery.inArray(needle, haystack) != -1) : false; }; ' + $this.settings.conditions[id];
                var fn = new Function('values', condition);

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
            var $this = this;
            var values = {};
            var value = null;

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
                    value = $this.getFieldValue(this);

                    if (value) {
                        values[name] = value;
                    }
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
