<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionLanguageFactory
{
    public function create(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();

        $expressionLanguage->addFunction(
            new ExpressionFunction(
                'in_array',
                static fn ($needle, $haystack) => \sprintf('\in_array(%s, (array) %s, false))', $needle, $haystack),
                static fn ($arguments, $needle, $haystack) => \in_array($needle, (array) $haystack, false),
            ),
        );

        $expressionLanguage->addFunction(ExpressionFunction::fromPhp('str_contains'));

        return $expressionLanguage;
    }
}
