<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *
 * PHP version 5
 * @copyright  terminal42 gmbh 2013
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


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
