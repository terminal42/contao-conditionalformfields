<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle;

use Contao\Form;
use Contao\FormFieldModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

class FormHandler
{
    private Form $form;
    private ExpressionLanguage $expressionLanguage;

    private array $conditions = [];
    private array $fields = [];
    private array $formData = [];

    /**
     * @param array<FormFieldModel> $fields
     */
    public function __construct(Form $form, array $fields)
    {
        $this->form = $form;

        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('in_array'));
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('str_contains'));

        $fieldsets = [];

        foreach ($fields as $field) {
            if ('fieldsetStart' === $field->type && $field->isConditionalFormField) {
                $fieldsets[] = $field->id;
                $this->conditions[$field->id] = $this->createCondition($field->conditionalFormFieldCondition);
                continue;
            }

            if ('fieldsetStop' === $field->type) {
                array_pop($fieldsets);
                continue;
            }

            $this->fields[(string) $field->id] = $fieldsets;

            if (!empty($field->name)) {
                $this->formData[$field->name] = $this->getInput($field->name);
            }
        }

        if (!empty($this->conditions)) {
            // Add CSS class for current form
            $formAttributes = StringUtil::deserialize($form->attributes, true);
            $formAttributes[1] = trim(($formAttributes[1] ?? '').' cff');

            if (!empty(($previousData = $this->getPreviousDataFromMpForms()))) {
                $formAttributes[1] .= '" data-cff-previous="'.StringUtil::specialcharsAttribute(json_encode($previousData, JSON_THROW_ON_ERROR));
            }

            $form->attributes = $formAttributes;

            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/terminal42conditionalformfields/conditionalformfields.js';
        }
    }

    public function prepareField(Widget $widget): void
    {
        // Add a CSS class to conditional fieldset so we can find and trigger them through JS
        if ($widget->isConditionalFormField) {
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

            // Widget needs to be set to disabled (#17)
            $widget->disabled = true;
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
     * @return \callable|false
     */
    private function createCondition(string $condition)
    {
        if (empty($condition)) {
            return true;
        }

        return fn () => (bool) $this->expressionLanguage->evaluate($condition, $this->formData);
    }

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
        if (System::getContainer()->has(FormManagerFactoryInterface::class)) {
            /** @var FormManagerFactoryInterface $factory */
            $factory = System::getContainer()->get(FormManagerFactoryInterface::class);
            $previousStepsData = $factory->forFormId((int) $this->form->id)->getDataOfAllSteps();

            return $previousStepsData['submitted'];
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
