<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Contao\Widget;
use Terminal42\ConditionalformfieldsBundle\FormHandler;

class FormListener
{
    /**
     * @var array<FormHandler>
     */
    private $handlers = [];

    /**
     * @Hook("compileFormFields")
     */
    public function onCompileFormFields(array $fields, string $formId, Form $form): array
    {
        if (isset($this->handlers[$formId])) {
            throw new \RuntimeException("Duplicate form ID $formId");
        }

        $this->handlers[$formId] = new FormHandler($form, $fields);

        return $fields;
    }

    /**
     * @Hook("loadFormField")
     */
    public function onLoadFormField(Widget $widget, string $formId): Widget
    {
        if (isset($this->handlers[$formId])) {
            $this->handlers[$formId]->prepareField($widget);
        }

        return $widget;
    }

    /**
     * @Hook("validateFormField")
     */
    public function onValidateFormField(Widget $widget, string $formId): Widget
    {
        if (isset($this->handlers[$formId])) {
            $this->handlers[$formId]->validateField($widget);
        }

        return $widget;
    }
}
