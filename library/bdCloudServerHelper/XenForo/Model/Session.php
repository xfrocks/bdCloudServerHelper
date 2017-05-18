<?php

class bdCloudServerHelper_XenForo_Model_Session extends XFCP_bdCloudServerHelper_XenForo_Model_Session
{
    public function updateUserLastActivityFromSessions($cutOffDate = null)
    {
        if (bdCloudServerHelper_Option::get('redis', 'session_activity')) {
            bdCloudServerHelper_Helper_RedisValueSync::sessionActivity();
        }

        // still let the default method run to handle left over data
        parent::updateUserLastActivityFromSessions($cutOffDate);
    }

}