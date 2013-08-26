<?php

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['loadFormField'][] = array('ConditionalFormFields', 'loadFormField');
$GLOBALS['TL_HOOKS']['outputFrontendTemplate'][] = array('ConditionalFormFields', 'outputFrontendTemplate');
