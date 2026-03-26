<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\Asset\Packages;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\ConditionalformfieldsBundle\ExpressionLanguageFactory;

class MemberListener
{
    private readonly ExpressionLanguage $expressionLanguage;

    private array $fieldsets = [];

    private array $conditions = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Packages $packages,
        ExpressionLanguageFactory $expressionLanguageFactory,
    ) {
        $this->expressionLanguage = $expressionLanguageFactory->create();
    }

    #[AsCallback('tl_member', 'config.onload')]
    public function load(): void
    {
        if (
            !($request = $this->requestStack->getCurrentRequest())
            || !$this->scopeMatcher->isFrontendRequest($request)
            || !array_find($GLOBALS['TL_DCA']['tl_member']['fields'], static fn (array $config) => (bool) ($config['eval']['isConditionalFormField'] ?? false))
        ) {
            return;
        }

        $this->fields = [];
        $this->fieldsets = [];
        $this->conditions = [];

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $field => &$config) {
            if ($config['eval']['feEditable'] ?? false) {
                $config['attributes_callback'][] = [self::class, 'getAttributesFromDca'];
            }
        }
        unset($config);

        $GLOBALS['TL_JAVASCRIPT'][] = $this->packages->getUrl('conditionalformfields.js', 'terminal42_conditionalformfields');
    }

    public function getAttributesFromDca(array $attributes): array
    {
        if (
            !($request = $this->requestStack->getCurrentRequest())
            || !$request->isMethod(Request::METHOD_POST)
            || !$this->scopeMatcher->isFrontendRequest($request)
        ) {
            return $attributes;
        }

        if ('fieldsetStart' === ($attributes['type'] ?? null)) {
            $this->fieldsets[] = $attributes['id'];

            if (($attributes['isConditionalFormField'] ?? false) && !empty($attributes['conditionalFormFieldCondition'])) {
                $this->conditions[$attributes['id']] = $attributes['conditionalFormFieldCondition'];
            }
        } elseif ('fieldsetStop' === ($attributes['type'] ?? null)) {
            // If the current condition is equal to the current entry of all fieldsets, close the "condition" fieldset
            if (array_pop($this->fieldsets) === array_key_last($this->conditions)) {
                array_pop($this->conditions);
            }
        } elseif (!empty($this->conditions)) {
            foreach ($this->conditions as $condition) {
                // If condition matches, the field is NOT hidden
                if ($this->expressionLanguage->evaluate($condition, $request->request->all())) {
                    continue;
                }

                $request->request->remove($attributes['name']);
                unset($attributes['mandatory'], $attributes['rgxp']);
                break;
            }
        }

        return $attributes;
    }
}
