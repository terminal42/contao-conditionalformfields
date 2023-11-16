<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\Token;

/**
 * @Callback(table="tl_form_field", target="fields.conditionalFormFieldCondition.save")
 */
class ConditionValidationListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(string $expression, DataContainer $dc): string
    {
        if (empty($expression)) {
            return $expression;
        }

        // Replace old syntax where variables started with $
        $expression = preg_replace('{(^|[^\'"])\$([a-z0-9_]+)}i', '$1$2', $expression);

        /** @var array<Token> $tokens */
        $tokens = [];

        $tokenStream = (new Lexer())->tokenize($expression);

        while (!$tokenStream->isEOF()) {
            $tokens[] = $tokenStream->current;
            $tokenStream->next();
        }

        $variables = [];

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0, $c = \count($tokens); $i < $c; ++$i) {
            if (
                Token::OPERATOR_TYPE === $tokens[$i]->type
                && !\in_array($tokens[$i]->value, ['&&', '||', '!', '==', '!=', '<', '>', '<=', '>='], true)
            ) {
                throw new \RuntimeException(sprintf('Unexpected operator "%s" around position %s', $tokens[$i]->value, $tokens[$i]->cursor));
            }

            if (!$tokens[$i]->test(Token::NAME_TYPE)) {
                continue;
            }

            $value = $tokens[$i]->value;

            // Skip constant nodes (see Symfony/Component/ExpressionLanguage/Parser#parsePrimaryExpression()
            if (\in_array($value, ['true', 'TRUE', 'false', 'FALSE', 'null', 'NULL'], true)) {
                continue;
            }

            // Validate functions
            if (isset($tokens[$i + 1]) && '(' === $tokens[$i + 1]->value) {
                if (!\in_array($tokens[$i]->value, ['in_array', 'str_contains'], true)) {
                    throw new \RuntimeException(sprintf('Unexpected function "%s" around position %s', $tokens[$i]->value, $tokens[$i]->cursor));
                }

                ++$i;

                continue;
            }

            if (!isset($variables[$value])) {
                $variables[$value] = $tokens[$i]->cursor;
            }
        }

        if (!$dc->activeRecord->pid) {
            return $expression;
        }

        /** @noinspection SqlNoDataSourceInspection */
        $fieldNames = $this->connection->fetchFirstColumn("SELECT name FROM tl_form_field WHERE pid=? AND invisible='' AND name!=''", [$dc->activeRecord->pid]);
        $unknown = array_keys(array_diff_key($variables, array_flip($fieldNames)));

        if (!empty($unknown)) {
            $unknown = $unknown[0];

            throw new \RuntimeException(sprintf('Unknown field name "%s" around position %s', $unknown, $variables[$unknown]));
        }

        foreach ($variables as $variable => $position) {
            if (!preg_match('/^(?!(?:do|if|in|for|let|new|try|var|case|else|enum|eval|false|null|this|true|void|with|break|catch|class|const|super|throw|while|yield|delete|export|import|public|return|static|switch|typeof|default|extends|finally|package|private|continue|debugger|function|arguments|interface|protected|implements|instanceof)$)[$A-Z_a-z][$A-Z_a-z0-9]*$/', $variable)) {
                throw new \RuntimeException(sprintf('Unsupported variable name "%s" around position %s', $variable, $position));
            }
        }

        return $expression;
    }
}
