<?php

/**
 * Palettes
 */
foreach ($GLOBALS['TL_DCA']['tl_form_field']['palettes'] as $k => $palette) {
    if ($k == '__selector__') {
        continue;
    }

    $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$k] = preg_replace('/({expert_legend(:hide)?})/u', '$1,isConditionalFormField', $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$k]);
}

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'isConditionalFormField';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['isConditionalFormField'] = 'conditionalFormFieldCondition';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['isConditionalFormField'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form_field']['isConditionalFormField'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr')
);
$GLOBALS['TL_DCA']['tl_form_field']['fields']['conditionalFormFieldCondition'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form_field']['conditionalFormFieldCondition'],
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('tl_class'=>'clr', 'decodeEntities'=>true)
);