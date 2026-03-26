import { ExpressionLanguage } from 'expression-language';
import './images/condition.svg'; // load with Webpack for backend use

(function () {
    const initialized = [];
    const expressionLanguage = new ExpressionLanguage();

    expressionLanguage.register(
        'in_array',
        (needle, haystack) => `!!Object.values(${haystack}).find(v => v == '${needle}')`,
        (values, needle, haystack) => !!Object.values(haystack).find((v) => String(v) === String(needle)),
    );

    expressionLanguage.register(
        'str_contains',
        (needle, haystack) => `String('${haystack}').includes('${needle}')`,
        (values, needle, haystack) => String(haystack).includes(needle),
    );

    function init(node) {
        node.querySelectorAll('fieldset[data-cff-condition]').forEach((el) => {
            if (initialized.includes(el)) {
                return;
            }

            initialized.push(el);

            const { form } = el;
            const condition = el.getAttribute('data-cff-condition');

            Array.from(form.elements).forEach((control) => {
                control.addEventListener('change', () => {
                    toggleFieldset(el, condition, getFormData(form));
                });
            });

            toggleFieldset(el, condition, getFormData(form));
        });
    }

    function toggleFieldset(fieldset, condition, formData) {
        if (expressionLanguage.evaluate(condition, Object.fromEntries(formData.entries()))) {
            fieldset.disabled = false;
            fieldset.style.display = '';
        } else {
            fieldset.disabled = true;
            fieldset.style.display = 'none';
        }
    }

    function getFormData(form) {
        const data = new Map();
        const formData = new FormData(form);

        if (form.hasAttribute('data-cff-previous')) {
            const previous = JSON.parse(form.getAttribute('data-cff-previous'));
            Object.keys(previous).forEach((key) => {
                data.set(key, previous[key]);
            });
        }

        formData.entries().forEach(([name, value]) => {
            // Array
            if (name.substring(name.length - 2) === '[]') {
                const key = name.substring(0, name.length - 2);

                if (!(data.get(key) instanceof Array)) {
                    data.set(key, []);
                }

                data.get(key).push(value);
            } else {
                data.set(name, value);
            }
        });

        // Initialize empty values (e.g. no radio option selected)
        Array.from(form.elements).forEach((control) => {
            if (!control.name) {
                return;
            }

            let { name } = control;
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
        data.forEach((value, key) => {
            if (Array.isArray(value)) {
                data.set(key, Object.fromEntries(value.entries()));
            }
        });

        return data;
    }

    function load() {
        init(document);
        new MutationObserver((mutationsList) => {
            // eslint-disable-next-line
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((element) => {
                        if (element.querySelectorAll) {
                            init(element);
                        }
                    });
                }
            }
        }).observe(document, {
            attributes: false,
            childList: true,
            subtree: true,
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
