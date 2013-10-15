var ConditionalFormFields = new Class({
    initialize: function(formId, triggers, fields, conditions) {
        var self = this;
        this.form = document.id(document.body).getElement('input[name="FORM_SUBMIT"][value="' + formId + '"]').getParent('form');
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
            var el = self.form.getElement('*[name="' + field + '"]');
            if (el) {
                self.fields[field] = el;
            }
        });

        this.initOnChangeEvents();
        this.updateFieldVisibility();
    },

    initOnChangeEvents: function() {
        var self = this;
        this.triggers.forEach(function(name) {
            var field = self.fields[name];

            field.addEvent('change', function() {
                self.updateFieldVisibility()
            });
        });
    },

    updateFieldVisibility: function() {
        var self = this;
        this.showAllFields();
        var values = this.loadValuesFromAllFields();

        Object.each(this.conditions, function(condition, field) {
            var fn = new Function('values', condition);
            if (!fn(values)) {
                self.hideField(self.fields[field]);
            }
        });
    },

    loadValuesFromAllFields: function() {
        var res = [];

        Object.each(this.fields, function(field, key) {
            res[key] = field.get('value');
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
        // @todo: make this configurable/extendable for other widgets
        var ctrl = field.get('id');
        document.getElements('label[for="' + ctrl + '"], #' + ctrl + ', #' + ctrl + ' + br').removeClass('invisible');
    },

    hideField: function(field) {
        // @todo: make this configurable/extendable for other widgets
        var ctrl = field.get('id');
        document.getElements('label[for="' + ctrl + '"], #' + ctrl + ', #' + ctrl + ' + br').addClass('invisible');
    }
});