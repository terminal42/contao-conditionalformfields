<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class ConditionMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (
            null === $schemaManager
            || !$schemaManager->tablesExist('tl_form_field')
            || !\array_key_exists('conditionalformfieldcondition', $schemaManager->listTableColumns('tl_form_field'))
        ) {
            return false;
        }

        return
            $this->connection->fetchOne(
                "SELECT COUNT(*) FROM tl_form_field WHERE conditionalFormFieldCondition REGEXP '(^|[^\\'\"])[$]([a-z0-9_]+)'",
            ) > 0;
    }

    public function run(): MigrationResult
    {
        $fields = $this->connection->fetchAllAssociative(
            "SELECT id, conditionalFormFieldCondition FROM tl_form_field WHERE conditionalFormFieldCondition REGEXP '(^|[^\\'\"])[$]([a-z0-9_]+)'",
        );

        foreach ($fields as $field) {
            $expression = preg_replace('{(^|[^\'"])\$([a-z0-9_]+)}i', '$1$2', $field['conditionalFormFieldCondition']);

            $this->connection->update(
                'tl_form_field',
                ['conditionalFormFieldCondition' => $expression],
                ['id' => $field['id']],
            );
        }

        return $this->createResult(true, sprintf('Updated %s form field conditions', \count($fields)));
    }
}
