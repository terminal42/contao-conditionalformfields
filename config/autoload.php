<?php

/**
 * conditionalformfields extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2014, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-conditionalformfields
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'ConditionalFormFields'   => 'system/modules/conditionalformfields/ConditionalFormFields.php',
    'FormFieldsetConditional' => 'system/modules/conditionalformfields/FormFieldsetConditional.php',
));

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'form_fieldset_conditional' => 'system/modules/conditionalformfields/templates/forms',
));
