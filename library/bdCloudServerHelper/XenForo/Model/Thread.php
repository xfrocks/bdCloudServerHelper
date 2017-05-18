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
            bdCloudServerHelper_Helper_RedisValueSync::threadView();
        }

        // still let the default method run to handle left over data
        parent::updateThreadViews();
    }

}