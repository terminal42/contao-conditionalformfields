<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\StringUtil;
use tl_form_field;

use function preg_replace;
use function sprintf;

/**
 * @Callback(table="tl_form_field", target="list.sorting.child_record")
 */
class FormFieldChildRecordCallbackListener
{
    public function __invoke(array $row): string
    {
        // Adapt row only for type 'fieldsetStart' with a filled 'conditionalFormFieldCondition' field.
        $formField = $this->generateFormField($row);
        if ('fieldsetStart' !== $row['type']) {
            return $formField;
        }

        if (empty($row['conditionalFormFieldCondition'])) {
            return $formField;
        }

        $addInput = ' <span class="tl_gray conditional-fieldset">[';
        $addInput .= sprintf(
            '<img src="%s" alt="icon" aria-hidden="true">',
            (
            $row['isConditionalFormField']
                ? 'bundles/terminal42conditionalformfields/condition-arrows-active.svg'
                : 'bundles/terminal42conditionalformfields/condition-arrows-inactive.svg'
            )
        );
        $addInput .= sprintf(
            ' <abbr title="%s">%s</abbr>',
            $row['conditionalFormFieldCondition'],
            StringUtil::substr($row['conditionalFormFieldCondition'], 80)
        );
        $addInput .= ']</span>';

        $pattern = '/(<div\s+class="cte_type[^"]*">)(.*)(<\/div>)/sU';

        return preg_replace($pattern, '$1$2' . $addInput . '$3', $formField);
    }

    private function generateFormField($row): string
    {
        return (new tl_form_field)->listFormFields($row);
    }
}
