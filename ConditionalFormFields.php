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

        // JS
        $GLOBALS['CONDITIONALFORMFIELDS'][$formId] = array(
            'formSubmitId' => $formSubmitId,
            'fields' => array()
        );

        foreach ($fieldModels as $fieldModel) {
            // Start the fieldset
            if ((($fieldModel->type == 'fieldset' && $fieldModel->fsType == 'fsStart') || $fieldModel->type == 'fieldsetStart') && $fieldModel->isConditionalFormField) {
                $fieldset = $fieldModel->id;
                $condition = $this->generateCondition($fieldModel->conditionalFormFieldCondition, 'php');

                static::$fieldsets[$formId][$fieldset] = array(
                    'condition' => function ($arrPost) use ($condition) {
                        return eval($condition);
                    },
                    'fields' => array(),
                );

                // JS
                $GLOBALS['CONDITIONALFORMFIELDS'][$formId]['fields'][$fieldModel->id] = $fieldModel->conditionalFormFieldCondition;

                continue;
            }

            // Stop the fieldset
            if ($fieldModel->type == 'fieldset' && $fieldModel->fsType == 'fsStop' || $fieldModel->type == 'fieldsetStop') {
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
     * @param \Contao\Form  $form
     *
     * @return  Widget
     */
    public function loadFormField($objWidget, $formId, $arrForm, \Contao\Form $form)
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

        return $objWidget;
    }

    /**
     * Validate only if needed.
     *
     * @param Widget $objWidget
     * @param string $formId
     * @param array  $arrForm
     * @param \Contao\Form  $form
     *
     * @return Widget
     */
    public function validateFormField($objWidget, $formId, $arrForm, \Contao\Form $form)
    {
        // At this stage, widgets are already validated by the Form class
        // The mandatory (or any other restriction such as rgxp) settings are thus
        // already checked for fields that are conditional. We thus reset the
        // errors to none on them (ugly with reflection but there's no setter
        // on the Widget class so...)

        if (empty(static::$fieldsets[$form->id])) {

            return $objWidget;
        }

        $postData = $this->getFormPostData($arrForm['id']);

        foreach (static::$fieldsets[$form->id] as $fieldset) {
            foreach ($fieldset['fields'] as $fieldId) {
                if ($fieldId == $objWidget->id && !$fieldset['condition']($postData)) {

                    $reflection = new ReflectionClass($objWidget);
                    $errors = $reflection->getProperty('arrErrors');
                    $errors->setAccessible(true);
                    $errors->setValue($objWidget, array());

                    // Widget needs to be set to disabled (#17)
                    $objWidget->disabled = true;
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
        $previousStepsData = array();

        if (!is_array($data)) {
            $data = array();
            $fieldModels = \FormFieldModel::findPublishedByPid($formId);

            if ($fieldModels !== null) {
                // Compatibility with mp_forms
                if (class_exists('MPFormsFormManager')) {
                    $manager = new \MPFormsFormManager($formId);
                    $previousStepsData = $manager->getDataOfAllSteps();
                    $previousStepsData = $previousStepsData['submitted'];
                }

                foreach ($fieldModels as $fieldModel) {
                    // Load post value with priority if available
                    if ($_POST[$fieldModel->name]) {
                        $data[$fieldModel->name] = \Input::post($fieldModel->name);
                        continue;
                    }

                    if (isset($previousStepsData[$fieldModel->name])) {
                        $data[$fieldModel->name] = $previousStepsData[$fieldModel->name];
                    }
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
            foreach ($arrForms as $formId => $arrDefinition) {
                $arrTriggerFields   = $this->generateTriggerFields($arrDefinition['fields']);
                $arrConditions      = $this->generateConditions($arrDefinition['fields']);
                $arrAllFields       = array_unique(array_merge($arrTriggerFields, array_keys($arrConditions)));

                $strBuffer = str_replace('</body>', $this->generateJS($formId, $arrDefinition['formSubmitId'], $arrTriggerFields, $arrConditions, $arrAllFields) . '</body>', $strBuffer);
            }
        }

        return $strBuffer;
    }

    private function generateJS($formId, $formSubmitId, $arrTriggerFields, $arrConditions, $arrAllFields)
    {
        // No need to generate any JS if the form does not have any conditions
        if (!$arrTriggerFields) {
            return '';
        }

        $previousStepsData = array();

        // Compatibility with mp_forms
        if (class_exists('MPFormsFormManager')) {
            $manager = new \MPFormsFormManager($formId);
            $previousStepsData = $manager->getDataOfAllSteps();
            $previousStepsData = $previousStepsData['submitted'];
        }

        return "
<script>
(function($) {
    $('input[name=\"FORM_SUBMIT\"][value=\"" . $formSubmitId . "\"]').parents('form').conditionalFormFields({
        'fields': " . json_encode($arrTriggerFields) . ",
        'conditions': " . json_encode($arrConditions) . ",
        'previousValues': " . json_encode($previousStepsData) . "
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

        // The array must not be associative, see #32
        return array_values($arrTriggerFields);
    }
}
