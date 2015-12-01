<?php

class bdCloudServerHelper_XenForo_Model_Thread extends XFCP_bdCloudServerHelper_XenForo_Model_THread
{
    public function logThreadView($threadId)
    {
        if (bdCloudServerHelper_Option::get('redis', 'thread_view')) {
            bdCloudServerHelper_Helper_Redis::increaseCounter('thread_view', $threadId);

            // prevent the default logging mechanism
            return;
        }

        parent::logThreadView($threadId);
    }

    public function updateThreadViews()
    {
        if (bdCloudServerHelper_Option::get('redis', 'thread_view')) {
            $values = bdCloudServerHelper_Helper_Redis::getCounters('thread_view');

            $db = $this->_getDb();

            foreach ($values as $threadId => $value) {
                try {
                    $db->query('
                        UPDATE xf_thread
                        SET view_count = view_count + ?
                        WHERE thread_id = ?
                    ', array($value, $threadId));
                } catch (Zend_Db_Exception $e) {
                    // stop running, for now
                    return;
                }

                bdCloudServerHelper_Helper_Redis::clearCounter('thread_view', $threadId);
            }
        }

        // still let the default method run to handle left over data
        parent::updateThreadViews();
    }

}