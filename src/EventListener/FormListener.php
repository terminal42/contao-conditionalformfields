<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\Widget;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\ConditionalformfieldsBundle\FormHandler;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

class FormListener
{
    private RequestStack $requestStack;

    private ScopeMatcher $scopeMatcher;

    private ?FormManagerFactoryInterface $formManagerFactory;

    /**
     * @var array<FormHandler>
     */
    private array $handlers = [];

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher, ?FormManagerFactoryInterface $formManagerFactory = null)
    {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->formManagerFactory = $formManagerFactory;
    }

    /**
     * @Hook("compileFormFields")
     */
    public function onCompileFormFields(array $fields, string $formId, Form $form): array
    {
        // mp_forms is calling the "compileFormFields" hook in the back end
        if (!($request = $this->requestStack->getCurrentRequest()) || $this->scopeMatcher->isBackendRequest($request)) {
            return $fields;
        }

        if (!$this->hasConditions($fields)) {
            return $fields;
        }

        if (!isset($this->handlers[$formId])) {
            $this->handlers[$formId] = new FormHandler($form, $fields, $this->formManagerFactory);
        }

        $this->handlers[$formId]->init($form);

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

    /**
     * @param array<FormFieldModel> $fields
     */
    private function hasConditions(array $fields): bool
    {
        foreach ($fields as $field) {
            if ('fieldsetStart' === $field->type && $field->isConditionalFormField) {
                return true;
            }
        }

        return false;
    }
}
