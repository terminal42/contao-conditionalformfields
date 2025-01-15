<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle;

use Contao\Form;
use Contao\FormFieldModel;
use Contao\FormFieldsetStart;
use Contao\Input;
use Contao\StringUtil;
use Contao\Widget;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

class FormHandler
{
    private Form $form;

    private ?FormManagerFactoryInterface $formManagerFactory;

    private ExpressionLanguage $expressionLanguage;

    private array $conditions = [];

    private array $fields = [];

    private array $formData = [];

    /**
     * @param array<FormFieldModel> $fields
     */
    public function __construct(Form $form, array $fields, ?FormManagerFactoryInterface $formManagerFactory = null)
    {
        $this->form = $form;
        $this->formManagerFactory = $formManagerFactory;

        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->addFunction(
            new ExpressionFunction(
                'in_array',
                static fn ($needle, $haystack) => sprintf('\in_array(%s, (array) %s, false))', $needle, $haystack),
                static fn ($arguments, $needle, $haystack) => \in_array($needle, (array) $haystack, false),
            ),
        );
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('str_contains'));

        $conditions = [];
        $fieldsets = [];

        foreach ($fields as $field) {
            if ('fieldsetStart' === $field->type) {
                $fieldsets[] = $field->id;

                if ($field->isConditionalFormField) {
                    $conditions[] = $field->id;
                    $this->conditions[$field->id] = $this->createCondition($field->conditionalFormFieldCondition);
                }

                continue;
            }

            if ('fieldsetStop' === $field->type) {
                // If the current condition is equal to the current entry of all fieldsets, close the "condition" fieldset
                if (array_pop($fieldsets) === end($conditions)) {
                    array_pop($conditions);
                }
                continue;
            }

            $this->fields[(string) $field->id] = $conditions;

            if (!empty($field->name)) {
                $this->formData[$field->name] = $this->getInput($field->name);
            }
        }

        if (!empty($this->conditions)) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/terminal42conditionalformfields/conditionalformfields.js';
        }
    }

    public function init(Form $form): void
    {
        // Add CSS class for current form
        $formAttributes = StringUtil::deserialize($form->attributes, true);
        $formAttributes[1] = trim(($formAttributes[1] ?? '').' cff');

        if (!empty($previousData = $this->getPreviousDataFromMpForms())) {
            $formAttributes[1] .= '" data-cff-previous="'.StringUtil::specialcharsAttribute(json_encode($previousData, JSON_THROW_ON_ERROR));
        }

        $form->attributes = $formAttributes;
    }

    public function prepareField(Widget $widget): void
    {
        // Add a CSS class to conditional fieldset, so we can find and trigger them through JS
        if ($widget instanceof FormFieldsetStart && $widget->isConditionalFormField) {
            $widget->class = '" data-cff-condition="'.StringUtil::specialcharsAttribute($widget->conditionalFormFieldCondition);
        }
    }

    public function validateField(Widget $widget): void
    {
        // At this stage, widgets are already validated by the Form class
        // The mandatory (or any other restriction such as rgxp) settings are thus
        // already checked for fields that are conditional. We thus reset the
        // errors to none on them (ugly with reflection but there's no setter
        // on the Widget class so...)

        if ($this->isHidden((string) $widget->id)) {
            $reflection = new \ReflectionClass($widget);

            $errors = $reflection->getProperty('arrErrors');
            $errors->setAccessible(true);
            $errors->setValue($widget, []);

            $class = $reflection->getProperty('strClass');
            $class->setAccessible(true);
            $class->setValue($widget, preg_replace('{(^| )error( |$)}', '', (string) $widget->class));

            // Widget must not submit their input if they are hidden
            // We previously used "disabled = true" (see #18) but that will result in the
            // field being disabled on subsequent run, since v3 only toggles the fieldset.
            $submitInput = $reflection->getProperty('blnSubmitInput');
            $submitInput->setAccessible(true);
            $submitInput->setValue($widget, false);

            $widget->value = null;
        }
    }

    private function isHidden(string $fieldId): bool
    {
        if (empty($this->fields[$fieldId])) {
            return false;
        }

        foreach ($this->fields[$fieldId] as $fieldset) {
            if (!isset($this->conditions[$fieldset])) {
                continue;
            }

            // Lazy-evaluate conditions
            if (\is_callable($this->conditions[$fieldset])) {
                $this->conditions[$fieldset] = $this->conditions[$fieldset]();
            }

            // If condition does not match, the field is hidden
            if (!$this->conditions[$fieldset]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Closure|true
     */
    private function createCondition(?string $condition)
    {
        if (empty($condition)) {
            return true;
        }

        return fn () => (bool) $this->expressionLanguage->evaluate($condition, $this->formData);
    }

    /**
     * @return array|string|null
     */
    private function getInput(string $fieldName)
    {
        $value = 'get' === $this->form->method ? Input::get($fieldName, false, true) : Input::post($fieldName);

        if (null !== $value) {
            return $value;
        }

        $previousStepsData = $this->getPreviousDataFromMpForms();

        if (isset($previousStepsData[$fieldName])) {
            return $previousStepsData[$fieldName];
        }

        return null;
    }

    private function getPreviousDataFromMpForms(): array
    {
        // MP Forms v5
        if (null !== $this->formManagerFactory) {
            $manager = $this->formManagerFactory->forFormId((int) $this->form->id);

            if ($manager->isPreparing()) {
                return [];
            }

            $previousStepsData = $manager->getDataOfAllSteps();

            return $previousStepsData->getAllSubmitted();
        }

        // MP Forms v4
        if (class_exists(\MPFormsSessionManager::class)) {
            $manager = new \MPFormsSessionManager($this->form->id);
            $previousStepsData = $manager->getDataOfAllSteps();

            return $previousStepsData['submitted'];
        }

        return [];
    }
}
