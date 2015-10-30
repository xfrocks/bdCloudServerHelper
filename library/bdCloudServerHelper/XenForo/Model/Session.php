<?php

class bdCloudServerHelper_XenForo_Model_Session extends XFCP_bdCloudServerHelper_XenForo_Model_Session
{
    public function getSessionActivityRecords(array $conditions = array(), array $fetchOptions = array())
    {
        if (bdCloudServerHelper_Option::get('voidSessionActivities')) {
            return array();
        }

        return parent::getSessionActivityRecords($conditions, $fetchOptions);
    }

}