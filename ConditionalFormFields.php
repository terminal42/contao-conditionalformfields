<?php


class ConditionalFormFields extends Controller
{
    /**
     * Apply conditional settings
     *
     * @param   Widget
     * @param   string
     * @param   array
     * @return  Widget
     */
    public function loadFormField($objWidget, $formId, $arrForm)
    {
        if ($objWidget->isConditionalFormField) {

            // JS magic
            $GLOBALS['TL_JAVASCRIPT']['CONDITIONALFORMFIELDS'] = 'system/modules/conditionalformfields/assets/conditionalformfields.js';
            $_SESSION['CONDITIONALFORMFIELDS'][$formId][$objWidget->name] = $objWidget->conditionalFormFieldCondition;

            // filter post data
            if ($this->Input->post('FORM_SUBMIT') == $formId) {
                $arrPost = array();
                // can't read from $_POST because for whatever reason those are modified (wtf?) -.-
                $arrFields = Database::getInstance()->prepare('SELECT name FROM tl_form_field WHERE pid=?' . ((!BE_USER_LOGGED_IN) ? ' AND invisible=\'\'' : '') . ' ORDER BY sorting')
                    ->execute($arrForm['id'])
                    ->fetchEach('name');
                foreach ($arrFields as $strName) {
                    $arrPost[$strName] = $this->Input->post($strName);
                }

                $strCondition = $this->generateCondition($objWidget->conditionalFormFieldCondition, 'php');

                $objCondition = create_function('$arrPost', $strCondition);
                if (!$objCondition($arrPost)) {
                    $objWidget->mandatory = false;
                    $objWidget->rgxp = '';
                }
            }
        }

        return $objWidget;
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
        if ($arrForms = $_SESSION['CONDITIONALFORMFIELDS']) {
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
        return '<script>
new ConditionalFormFields(\'' . $formId . '\', ' . json_encode($arrTriggerFields) . ', ' . json_encode($arrAllFields) . ', ' . json_encode($arrConditions) . ');
</script>';
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