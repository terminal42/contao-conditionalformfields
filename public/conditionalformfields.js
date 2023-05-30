(function () {
    "use strict";

    const initialized = [];

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
        let fnBody = '"use strict";';
        fnBody += 'function in_array (needle, haystack) { return Array.isArray(haystack) ? haystack.includes(needle) : false; };';
        fnBody += 'function str_contains (haystack, needle) { return String(haystack).includes(needle) };'

        formData.forEach(function (value, key) {
            if (String(key).includes('-') || String(key).includes('[')) {
                console.warn(`terminal42/contao-conditionalformfields: skipping "${key}", special characters [-] and brackets are not supported in JavaScript variables.`);
            } else {
                fnBody += `const ${key} = values.get('${key}');`;
            }
        });

        fnBody += `return ${condition};`;

        let fn = new Function('values', fnBody);

        if (fn.call(undefined, formData)) {
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
        })

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
