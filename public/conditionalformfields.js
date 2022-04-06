"use strict";

(function () {
    const initialized = [];

    function init (node) {
        node.querySelectorAll('fieldset[data-cff-condition]').forEach(function (el) {
            if (initialized.includes(el)) {
                return;
            }

            initialized.push(el);

            const form = el.form;
            const condition = el.getAttribute('data-cff-condition');
            let fields = null;

            Array.from(form.elements).forEach(function (control) {
                control.addEventListener('change', function () {
                    fields = toggleFieldset(el, fields, condition, getFormData(form));
                });
            })

            fields = toggleFieldset(el, fields, condition, getFormData(form));
        });
    }

    function toggleFieldset (fieldset, fields, condition, formData) {
        let fnBody = '"use strict";';
        fnBody += 'function in_array (needle, haystack) { return Array.isArray(haystack) ? haystack.includes(needle) : false; };';
        fnBody += 'function str_contains (haystack, needle) { return String(haystack).includes(needle) };'

        formData.forEach(function (value, key) {
            fnBody += `const ${key} = values.get('${key}');`;
        });

        fnBody += `return ${condition};`;

        let fn = new Function('values', fnBody);

        if (fn.call(undefined, formData)) {
            if (fields) {
                fieldset.append(...fields);
                return null;
            }
        } else {
            if (!fields) {
                const removed = [];
                Array.from(fieldset.children).forEach(function (field) {
                    removed.push(field);
                    field.remove();
                });

                return removed;
            }
        }

        return fields;
    }

    function getFormData (form) {
        const data = new Map();
        const formData = new FormData(form);

        if (form.hasAttribute('data-cff-previous')) {
            const previous = JSON.parse(form.getAttribute('data-cff-previous'));
            Object.keys(previous).forEach(function (key) {
                data.set(key, previous[key]);
            });
        }

        for (let { 0: name, 1: value } of formData) {
            // Array
            if (name.substring(name.length - 2) === '[]') {
                name = name.substring(0, name.length - 2);

                if (!(data.get(name) instanceof Array)) {
                    data.set(name, []);
                }

                data.get(name).push(value);
            } else {
                data.set(name, value);
            }
        }

        // Initialize empty values (e.g. no radio option selected)
        Array.from(form.elements).forEach(function (control) {
            if (control.name && !data.has(control.name)) {
                data.set(control.name, '');
            }
        })

        return data;
    }

    init(document);
    new MutationObserver(function (mutationsList) {
        for(const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function (element) {
                    if (element.querySelectorAll) {
                        init(element)
                    }
                })
            }
        }
    }).observe(document, {
        attributes: false,
        childList: true,
        subtree: true
    });
})();
