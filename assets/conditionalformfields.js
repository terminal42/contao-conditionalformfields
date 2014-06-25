var ConditionalFormFields = new Class({
    initialize: function(formId, triggers, fields, conditions) {
        var self = this;
        var formSubmit = document.id(document.body).getElement('input[name="FORM_SUBMIT"][value="' + formId + '"]');
        if (formSubmit) this.form = formSubmit.getParent('form');
        if (!this.form) {
            return;
        }
        this.form.setProperty('novalidate', 'novalidate');
        this.triggers = triggers;
        this.conditions = conditions;

        this.form.addEvent('ajax_change', function() { self.loadFields(fields); })
        this.loadFields(fields);
    },

    loadFields: function(fields) {
        var self = this;
        this.fields = {};
        fields.forEach(function(field) {
            var els = self.form.getElements('*[name*="' + field + '"]');

            if (els.length > 0) {
                els.forEach(function(el) {
                    var name = el.get('name');

                    // Array
                    if (name.substr(name.length - 2) == '[]') {
                        if (!(self.fields[field] instanceof Array)) {
                            self.fields[field] = [];
                        }

                        self.fields[field].push(el);
                    } else {
                        // Regular field
                        self.fields[field] = el;
                    }
                });
            }
        });

        this.initOnChangeEvents();
        this.updateFieldVisibility();
    },

    initOnChangeEvents: function() {
        var self = this;
        var addChangeEvent = function(field) {
            field.addEvent('change', function() {
                self.updateFieldVisibility()
            });
        }

        this.triggers.forEach(function(name) {
            var field = self.fields[name];

            if (field instanceof Array) {
                field.forEach(function(el) {
                    addChangeEvent(el);
                });
            } else {
                addChangeEvent(field);
            }
        });
    },

    updateFieldVisibility: function() {
        var self = this;
        this.showAllFields();
        var values = this.loadValuesFromAllFields();

        Object.each(this.conditions, function(condition, field) {
            condition = 'var in_array = function(needle, haystack) { return haystack.contains(needle); }; ' + condition;
            var fn = new Function('values', condition);

            if (!fn(values)) {
                self.hideField(self.fields[field]);
            }
        });
    },

    loadValuesFromAllFields: function() {
        var res = [];

        Object.each(this.fields, function(field, key) {
            if (field instanceof Array) {
                res[key] = [];

                field.forEach(function(el) {
                    if ((el.get('type') == 'checkbox' || el.get('type') == 'radio') && el.checked) {
                        res[key].push(el.get('value'));
                    }
                });
            } else {
                res[key] = field.get('value');
            }
        });

        return res;
    },

    showAllFields: function() {
        var self = this;
        Object.each(this.fields, function(field) {
            self.showField(field);
        });
    },

    showField: function(field) {
        var show = function(field) {
            // @todo: make this configurable/extendable for other widgets
            var ctrl = field.get('id');
            document.getElements('label[for="' + ctrl + '"], #' + ctrl + ', #' + ctrl + ' + br').removeClass('invisible');
            field.getPrevious('p.error') && field.getPrevious('p.error').removeClass('invisible');

            // Support form_stylify
            field.getParent('.select_container') && field.getParent('.select_container').removeClass('invisible');
        }

        if (field instanceof Array) {
            field.forEach(function(el) {
                show(el);
            });
        } else {
            show(field);
        }
    },

    hideField: function(field) {
        var hide = function(field) {
            // @todo: make this configurable/extendable for other widgets
            var ctrl = field.get('id');
            document.getElements('label[for="' + ctrl + '"], #' + ctrl + ', #' + ctrl + ' + br').addClass('invisible');
            field.getPrevious('p.error') && field.getPrevious('p.error').addClass('invisible');

            // Support form_stylify
            field.getParent('.select_container') && field.getParent('.select_container').addClass('invisible');
        }

        if (field instanceof Array) {
            field.forEach(function(el) {
                hide(el);
            });
        } else {
            hide(field);
        }
    }
});