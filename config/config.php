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
 * Hooks
 */
$GLOBALS['TL_HOOKS']['initializeSystem'][] = array('ConditionalFormFields', 'registerHook');
$GLOBALS['TL_HOOKS']['loadFormField'][] = array('ConditionalFormFields', 'loadFormField');
$GLOBALS['TL_HOOKS']['validateFormField'][] = array('ConditionalFormFields', 'validateFormField');
$GLOBALS['TL_HOOKS']['modifyFrontendPage'][] = array('ConditionalFormFields', 'outputFrontendTemplate');
