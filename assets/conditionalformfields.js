import { ExpressionLanguage } from 'expression-language';

(function () {
    "use strict";

    const initialized = [];
    const el = new ExpressionLanguage();

    el.register(
        'in_array',
        (needle, haystack) => `!!Object.values(${haystack}).find(v => v == '${needle}')`,
        (values, needle, haystack) => !!Object.values(haystack).find(v => v == needle),
    );

    el.register(
        'str_contains',
        (needle, haystack) => `String('${haystack}').includes('${needle}')`,
        (values, needle, haystack) => String(haystack).includes(needle),
    );

    function init (node) {
        node.querySelectorAll('fieldset[data-cff-condition]').forEach(function (el) {
            if (initialized.includes(el)) {
                return;
            }

            initialized.push(el);

            const form = el.form;
            const condition = el.getAttribute('data-cff-condition');

            Array.from(form.elements).forEach(function (control) {
                control.addEventListener('change', function () {
                    toggleFieldset(el, condition, getFormData(form));
                });
            })

            toggleFieldset(el, condition, getFormData(form));
        });
    }

    function toggleFieldset (fieldset, condition, formData) {
        if (el.evaluate(condition, Object.fromEntries(formData.entries()))) {
            fieldset.disabled = false
            fieldset.style.display = '';
        } else {
            fieldset.disabled = true
            fieldset.style.display = 'none';
        }
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
            if (!control.name) {
                return;
            }

            let name = control.name;
            let value = '';

            if (name.substring(name.length - 2) === '[]') {
                name = name.substring(0, name.length - 2);
                value = [];
            }

            if (!data.has(name)) {
                data.set(control.name, value);
            }
        });

        // Convert arrays to temporary objects to enforce in_array check
        data.forEach(function (value, key) {
            if (Array.isArray(value)) {
                data.set(key, Object.fromEntries(value.entries()));
            }
        });

        return data;
    }

    function load () {
        init(document);
        new MutationObserver(function (mutationsList) {
            for (const mutation of mutationsList) {
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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
