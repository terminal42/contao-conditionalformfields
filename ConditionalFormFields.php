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


class ConditionalFormFields extends Controller
{
    protected static $fieldsets;

    protected static $reset;

    /**
     * Register hook when initializeSystem hook is triggerd.
     */
    public function registerHook()
    {
        $GLOBALS['TL_HOOKS']['compileFormFields'][] = array(__CLASS__, 'registerFieldsets');
    }

    /**
     * Register fieldsets.
     *
     * @param \FormFieldModel[] $fieldModels
     * @param string            $formSubmitId
     * @param \Form             $objForm
     *
     * @return mixed
     */
    public function registerFieldsets($fieldModels, $formSubmitId, $objForm)
    {
        $formId = $objForm->id;
        $fieldset = null;

        static::$fieldsets[$formId] = array();
        static::$reset[$formId] = array();

        foreach ($fieldModels as $fieldModel) {

            // Start the fieldset
            if ($fieldModel->type == 'fieldset' && $fieldModel->fsType == 'fsStart' && $fieldModel->isConditionalFormField) {
                $fieldset = $fieldModel->id;
                $condition = $this->generateCondition($fieldModel->conditionalFormFieldCondition, 'php');

                static::$fieldsets[$formId][$fieldset] = array(
                    'condition' => function ($arrPost) use ($condition) {
                        return eval($condition);
                    },
                    'fields' => array(),
                );

                // JS
                $GLOBALS['CONDITIONALFORMFIELDS'][$formSubmitId][$fieldModel->id] = $fieldModel->conditionalFormFieldCondition;

                continue;
            }

            // Stop the fieldset
            if ($fieldModel->type == 'fieldset' && $fieldModel->fsType == 'fsStop') {
                $fieldset = null;
                continue;
            }

            if ($fieldset === null) {
                continue;
            }

            static::$fieldsets[$formId][$fieldset]['fields'][] = $fieldModel->id;
        }

        return $fieldModels;
    }

    /**
     * Apply conditional settings
     *
     * @param Widget $objWidget
     * @param string $formId
     * @param array  $arrForm
     * @param \Form  $form
     *
     * @return  Widget
     */
    public function loadFormField($objWidget, $formId, $arrForm, \Form $form)
    {
        if (empty(static::$fieldsets[$form->id])) {
            return $objWidget;
        }

        // Set the CSS class only for fieldsets
        if ($objWidget->isConditionalFormField) {
            $objWidget->class = 'cffs-' . $objWidget->id;
        }

        // JS magic
        $GLOBALS['TL_JAVASCRIPT']['CONDITIONALFORMFIELDS'] = 'system/modules/conditionalformfields/assets/conditionalformfields' . ($GLOBALS['TL_CONFIG']['debugMode'] ? '' : '.min') . '.js';

        // Find and mark the fields that should not be validated
        if (\Input::post('FORM_SUBMIT') == $formId) {
            $postData = $this->getFormPostData($arrForm['id']);

            foreach (static::$fieldsets[$form->id] as $fieldset) {
                foreach ($fieldset['fields'] as $fieldId) {
                    if ($fieldId == $objWidget->id && !$fieldset['condition']($postData)) {
                        static::$reset[$formId][$objWidget->id]['mandatory'] = $objWidget->mandatory;
                        static::$reset[$formId][$objWidget->id]['rgxp'] = $objWidget->rgxp;

                        $objWidget->mandatory = false;
                        $objWidget->rgxp      = '';
                        $objWidget->disabled  = true; // don't submit
                    }
                }
            }
        }

        return $objWidget;
    }

    /**
     * Reset conditional settings
     *
     * @param Widget $objWidget
     * @param string $formId
     *
     * @return Widget
     */
    public function validateFormField($objWidget, $formId)
    {
        if (isset(static::$reset[$formId][$objWidget->id])) {
            $objWidget->mandatory = static::$reset[$formId][$objWidget->id]['mandatory'];
            $objWidget->rgxp = static::$reset[$formId][$objWidget->id]['rgpx'];
        }

        return $objWidget;
    }

    /**
     * Get the form postdata
     *
     * @param int $formId
     *
     * @return array
     */
    protected function getFormPostData($formId)
    {
        static $data;

        if (!is_array($data)) {
            $data = array();
            $fieldModels = \FormFieldModel::findPublishedByPid($formId);

            if ($fieldModels !== null) {
                foreach ($fieldModels as $fieldModel) {
                    $data[$fieldModel->name] = \Input::post($fieldModel->name);
                }
            }
        }

        return $data;
    }

    /**
     * Inject JavaScript
     *
     * @param   string
     * @param   string
     * @return  string
     */
    public function outputFrontendTemplate($strBuffer, $strTemplate)
    {
        if ($arrForms = $GLOBALS['CONDITIONALFORMFIELDS']) {
            foreach ($arrForms as $formId => $arrFields) {
                $arrTriggerFields   = $this->generateTriggerFields($arrFields);
                $arrConditions      = $this->generateConditions($arrFields);
                $arrAllFields       = array_unique(array_merge($arrTriggerFields, array_keys($arrConditions)));

                $strBuffer = str_replace('</body>', $this->generateJS($formId, $arrTriggerFields, $arrConditions, $arrAllFields) . '</body>', $strBuffer);
            }
        }

        return $strBuffer;
    }

    /**
     * Generates the JS per form
     * @param   string
     * @param   array
     * @return  string
     */
    private function generateJS($formId, $arrTriggerFields, $arrConditions, $arrAllFields)
    {
        return "
<script>
(function($) {
    $('input[name=\"FORM_SUBMIT\"][value=\"" . $formId . "\"]').parentsUntil('form').conditionalFormFields({
        'fields': " . json_encode($arrTriggerFields) . ",
        'conditions': " . json_encode($arrConditions) . "
    });
})(jQuery);
</script>";
    }

    private function generateConditions($arrFields)
    {
        $arrConditions = array();
        foreach ($arrFields as $name => $strCondition) {
            $arrConditions[$name] = $this->generateCondition($strCondition, 'js');
        }

        return $arrConditions;
    }

    private function generateCondition($strCondition, $strLanguage)
    {
        if ($strLanguage === 'js') {
            $strCondition = preg_replace("/\\$([A-Za-z0-9_]+)/u", "values.$1", $strCondition);
        } else {
            $strCondition = str_replace('in_array', '@in_array', $strCondition);
            $strCondition = preg_replace("/\\$([A-Za-z0-9_]+)/u", '$arrPost[\'$1\']', $strCondition);
        }

        return 'return (' . $strCondition . ');';
    }

    private function generateTriggerFields($arrFields)
    {
        $arrTriggerFields = array();
        foreach ($arrFields as $strCondition) {
            if (preg_match_all('/\\$([A-Za-z0-9_]+)/u', $strCondition, $arrMatches)) {
                if ($arrMatches[1]) {
                    $arrTriggerFields = array_unique(array_merge($arrTriggerFields, $arrMatches[1]));
                }
            }
        }

        return $arrTriggerFields;
    }
}
