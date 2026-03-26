<?php

declare(strict_types=1);

namespace Terminal42\ConditionalformfieldsBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Twig\Environment;

#[AsCallback('tl_form_field', 'list.label.label')]
class FormFieldLabelListener
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function __invoke(array $row): array
    {
        $label = (new \tl_form_field())->listFormFields($row);

        // Adapt row only for type 'fieldsetStart' with a filled 'conditionalFormFieldCondition' field.
        if ('fieldsetStart' === $row['type'] && $row['isConditionalFormField']) {
            $label[0] .= $this->twig->render('@Contao/backend/conditionalfieldpreview.html.twig', $row);
        }

        return $label;
    }
}
