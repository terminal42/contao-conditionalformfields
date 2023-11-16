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
        let fnBody = '"use strict";\n\n';
        fnBody += 'function in_array (needle, haystack) { return !!Object.values(haystack).find(v => v == needle) }\n';
        fnBody += 'function str_contains (haystack, needle) { return String(haystack).includes(needle) }\n\n'

        formData.forEach(function (value, key) {

            if (/^(?!(?:do|if|in|for|let|new|try|var|case|else|enum|eval|false|null|this|true|void|with|break|catch|class|const|super|throw|while|yield|delete|export|import|public|return|static|switch|typeof|default|extends|finally|package|private|continue|debugger|function|arguments|interface|protected|implements|instanceof)$)[$A-Z_a-z][$A-Z_a-z0-9]*$/.test(key)) {
                fnBody += `const ${key} = values.get('${key}');\n`;
            } else {
                console.warn(`terminal42/contao-conditionalformfields: skipping "${key}", this name is not supported in JavaScript variables.`);
            }
        });

        fnBody += `\nreturn ${condition};`;
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
