<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\Widget;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\ConditionalformfieldsBundle\ExpressionLanguageFactory;
use Terminal42\ConditionalformfieldsBundle\FormHandler;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

class FormListener
{
    /**
     * @var array<FormHandler>
     */
    private array $handlers = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly FormManagerFactoryInterface|null $formManagerFactory,
        private readonly Packages $packages,
        private readonly ExpressionLanguageFactory $expressionLanguageFactory,
    ) {
    }

    #[AsHook('compileFormFields')]
    public function onCompileFormFields(array $fields, string $formId, Form $form): array
    {
        // mp_forms is calling the "compileFormFields" hook in the back end
        if (!($request = $this->requestStack->getCurrentRequest()) || $this->scopeMatcher->isBackendRequest($request)) {
            return $fields;
        }

        if (!$this->hasConditions($fields)) {
            return $fields;
        }

        if (empty($form->Template)) {
            // The form template is not loaded yet
            return $fields;
        }

        if (!isset($this->handlers[$formId])) {
            $this->handlers[$formId] = new FormHandler($form, $fields, $this->expressionLanguageFactory->create(), $this->formManagerFactory);
        }

        $this->handlers[$formId]->init();
        $GLOBALS['TL_JAVASCRIPT'][] = $this->packages->getUrl('conditionalformfields.js', 'terminal42_conditionalformfields');

        return $fields;
    }

    #[AsHook('validateFormField')]
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
