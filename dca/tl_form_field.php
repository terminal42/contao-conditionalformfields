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
 * Config
 */
$GLOBALS['TL_DCA']['tl_form_field']['config']['onload_callback'][] = array('ConditionalFormFields', 'addToPalette');


/**
 * Palettes
 */
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
	'eval'                    => array('tl_class'=>'clr', 'decodeEntities'=>true, 'style'=>'height:40px'),
);
