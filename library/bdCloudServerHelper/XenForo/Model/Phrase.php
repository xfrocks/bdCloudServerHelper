<?php

class bdCloudServerHelper_XenForo_Model_Phrase extends XFCP_bdCloudServerHelper_XenForo_Model_Phrase
{
    public function getOutdatedPhrases()
    {
        $phrases = parent::getOutdatedPhrases();

        $phrases += $this->fetchAllKeyed('
            SELECT p.*, "N/A" AS master_version_string
            FROM xf_phrase AS p
            LEFT JOIN xf_phrase AS m ON (m.title = p.title AND m.language_id = 0)
            WHERE p.language_id > 0 AND m.phrase_id IS NULL
        ', 'phrase_id');

        return $phrases;
    }
}