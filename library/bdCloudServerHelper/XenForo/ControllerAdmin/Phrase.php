<?php

class bdCloudServerHelper_XenForo_ControllerAdmin_Phrase extends XFCP_bdCloudServerHelper_XenForo_ControllerAdmin_Phrase
{
    public function actionSync()
    {
        $input = $this->_input->filter(array(
            'api_address' => XenForo_Input::STRING,
            'language_id' => XenForo_Input::UINT,
            'addon_id' => XenForo_Input::STRING,
        ));

        if ($this->isConfirmedPost()) {
            if (empty($input['api_address'])
                || empty($input['language_id'])
            ) {
                return $this->responseNoPermission();
            }

            $language = $this->_getLanguageModel()->getLanguageById($input['language_id']);
            if (empty($language)) {
                return $this->responseNoPermission();
            }

            $addOn = null;
            $apiPhrases = array();
            if (!empty($input['addon_id'])) {
                $addOn = $this->_getAddOnModel()->getAddOnById($input['addon_id']);
                if (empty($addOn)) {
                    return $this->responseNoPermission();
                }

                $apiPhrases = bdCloudServerHelper_Helper_Api::postPhrases($input['api_address'],
                    $language['language_code'], array(), $addOn['addon_id']);
            } else {
                $masterPhrases = $this->_getPhraseModel()->getAllPhrasesInLanguage(0);
                while (count($masterPhrases) > 0) {
                    $phraseTitles = array();
                    while (count($phraseTitles) < bdCloudServerHelper_Helper_Api::GET_PHRASES_TITLES_LIMIT
                        && count($masterPhrases) > 0) {
                        $masterPhrase = array_shift($masterPhrases);
                        $phraseTitles[] = $masterPhrase['title'];
                    }

                    if (count($phraseTitles) > 0) {
                        $apiPhrases += bdCloudServerHelper_Helper_Api::postPhrases($input['api_address'],
                            $language['language_code'], $phraseTitles);
                    }
                }
            }

            $languagePhrases = $this->_getPhraseModel()->getAllEffectivePhrasesInLanguage($language['language_id']);
            $newPhrases = array();
            $matchedTextCount = 0;

            foreach ($languagePhrases as $languagePhrase) {
                $phraseTitle = $languagePhrase['title'];
                if (!isset($apiPhrases[$phraseTitle])) {
                    continue;
                }

                if ($languagePhrase['phrase_text'] !== $apiPhrases[$phraseTitle]) {
                    $newPhrase = $languagePhrase;
                    $newPhrase['new_phrase_text'] = $apiPhrases[$phraseTitle];
                    if ($newPhrase['map_language_id'] != $newPhrase['language_id']) {
                        $newPhrase['phrase_id'] = 0;
                    }

                    $newPhrases[] = $newPhrase;
                } else {
                    $matchedTextCount++;
                }
            }

            if (empty($newPhrases)) {
                return $this->responseMessage(new XenForo_Phrase('bdcsh_sync_found_x_matched_all', array(
                    'found' => XenForo_Template_Helper_Core::numberFormat(count($apiPhrases))
                )));
            }

            $viewParams = array(
                'apiAddress' => $input['api_address'],
                'language' => $language,
                'addOn' => $addOn,

                'apiPhrases' => $apiPhrases,
                'newPhrases' => $newPhrases,
                'matchedTextCount' => $matchedTextCount,
            );

            return $this->responseView('bdCloudServerHelper_ViewAdmin_Phrase_SyncResults',
                'bdcsh_phrase_sync_results', $viewParams);
        }

        $languages = $this->_getLanguageModel()->getAllLanguages();
        $languageOptions = array();
        foreach ($languages as $language) {
            $languageOptions[] = array(
                'value' => $language['language_id'],
                'label' => $language['title'],
            );
        }

        $addOns = $this->_getAddOnModel()->getAllAddOns();
        $addOnOptions = array();
        foreach ($addOns as $addOn) {
            $addOnOptions[] = array(
                'value' => $addOn['addon_id'],
                'label' => $addOn['title'],
            );
        }

        $viewParams = array(
            'languages' => $languageOptions,
            'addOns' => $addOnOptions,

            'input' => $input,
        );

        return $this->responseView('bdCloudServerHelper_ViewAdmin_Phrase_Sync', 'bdcsh_phrase_sync', $viewParams);
    }

    public function actionSyncSave()
    {
        $input = $this->_input->filter(array(
            'api_address' => XenForo_Input::STRING,
            'language_id' => XenForo_Input::UINT,
            'addon_id' => XenForo_Input::STRING,
        ));

        $language = $this->_getLanguageModel()->getLanguageById($input['language_id']);
        if (empty($language)) {
            return $this->responseNoPermission();
        }

        if (!$this->_getPhraseModel()->canModifyPhraseInLanguage($language['language_id'])) {
            return $this->responseNoPermission();
        }

        $texts = $this->_input->filterSingle('texts', XenForo_Input::STRING, array('array' => true));
        $phraseIds = $this->_input->filterSingle('phrase_ids', XenForo_Input::UINT, array('array' => true));
        $unchangedPhraseMapIds = array();

        foreach ($texts as $phraseMapId => $phraseText) {
            if (empty($phraseText)) {
                $unchangedPhraseMapIds[] = $phraseMapId;
                continue;
            }

            if (isset($phraseIds[$phraseMapId])) {
                $phraseId = $phraseIds[$phraseMapId];
            } else {
                $phraseId = 0;
            }

            $effectivePhrase = $this->_getPhraseModel()->getEffectivePhraseByMapId($phraseMapId);
            if (empty($effectivePhrase)) {
                continue;
            }

            $writer = XenForo_DataWriter::create('XenForo_DataWriter_Phrase');
            if ($phraseId > 0) {
                $writer->setExistingData($phraseId);
            }

            $writer->bulkSet(array(
                'title' => $effectivePhrase['title'],
                'phrase_text' => $phraseText,
                'language_id' => $language['language_id'],
                'global_cache' => $effectivePhrase['global_cache'],
                'addon_id' => $effectivePhrase['addon_id'],
            ));

            $writer->updateVersionId();
            $writer->save();
        }

        if (count($unchangedPhraseMapIds) > 0) {
            $unchangedPhrases = $this->_getPhraseModel()->getEffectivePhrasesByMapIds($unchangedPhraseMapIds);
            $translatedPhrases = array();
            foreach ($unchangedPhrases as $unchangedPhrase) {
                if ($unchangedPhrase['map_language_id'] == $unchangedPhrase['language_id']) {
                    $translatedPhrases[$unchangedPhrase['title']] = $unchangedPhrase['phrase_text'];
                }
            }

            if (count($translatedPhrases) > 0) {
                bdCloudServerHelper_Helper_Api::postPhrasesSuggestions($input['api_address'],
                    $language['language_code'], $translatedPhrases);
            }
        }

        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildAdminLink('phrases/sync', null, $input));
    }
}