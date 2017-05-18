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
            bdCloudServerHelper_Helper_RedisValueSync::attachmentView();
        }

        // still let the default method run to handle left over data
        parent::updateAttachmentViews();
    }

}