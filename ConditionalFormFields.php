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

    /**
     * Apply conditional settings
     *
     * @param   Widget
     * @param   string
     * @param   array
     * @param \Form $form
     *
     * @return  Widget
     */
    public function loadFormField($objWidget, $formId, $arrForm, \Form $form)
    {
        $fieldsets = $this->getConditionalFieldsets($arrForm['id'], $formId);

        if (empty($fieldsets)) {
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

            foreach ($fieldsets as $fieldset) {
                foreach ($fieldset['fields'] as $fieldId) {
                    if ($fieldId == $objWidget->id && !$fieldset['condition']($postData)) {
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
     * Get the conditional fieldsets with condition and fields
     *
     * @param int    $formId
     * @param string $formSubmitId
     *
     * @return array
     */
    protected function getConditionalFieldsets($formId, $formSubmitId)
    {
        static $fieldsets;

        if (!is_array($fieldsets)) {
            $fieldsets = array();
            $fieldModels = \FormFieldModel::findPublishedByPid($formId);

            if ($fieldModels !== null) {
                $fieldset = null;

                foreach ($fieldModels as $fieldModel) {

                    // Start the fieldset
                    if ($fieldModel->type == 'fieldset' && $fieldModel->fsType == 'fsStart' && $fieldModel->isConditionalFormField) {
                        $fieldset = $fieldModel->id;
                        $condition = $this->generateCondition($fieldModel->conditionalFormFieldCondition, 'php');

                        $fieldsets[$fieldset] = array(
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

                    $fieldsets[$fieldset]['fields'][] = $fieldModel->id;
                }
            }
        }

        return $fieldsets;
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
