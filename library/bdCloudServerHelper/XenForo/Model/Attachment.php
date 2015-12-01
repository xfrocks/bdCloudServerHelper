<?php

class bdCloudServerHelper_XenForo_Model_Attachment extends XFCP_bdCloudServerHelper_XenForo_Model_Attachment
{
    public function logAttachmentView($attachmentId)
    {
        if (bdCloudServerHelper_Option::get('redis', 'attachment_view')) {
            bdCloudServerHelper_Helper_Redis::increaseCounter('attachment_view', $attachmentId);

            // prevent the default logging mechanism
            return;
        }

        parent::logAttachmentView($attachmentId);
    }

    public function updateAttachmentViews()
    {
        if (bdCloudServerHelper_Option::get('redis', 'attachment_view')) {
            $values = bdCloudServerHelper_Helper_Redis::getCounters('attachment_view');

            $db = $this->_getDb();

            foreach ($values as $attachmentId => $value) {
                try {
                    $db->query('
                        UPDATE xf_attachment
                        SET view_count = view_count + ?
                        WHERE attachment_id = ?
                    ', array($value, $attachmentId));
                } catch (Zend_Db_Exception $e) {
                    // stop running, for now
                    return;
                }

                bdCloudServerHelper_Helper_Redis::clearCounter('attachment_view', $attachmentId);
            }
        }

        // still let the default method run to handle left over data
        parent::updateAttachmentViews();
    }

}