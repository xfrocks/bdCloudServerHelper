<?php

class bdCloudServerHelper_XenForo_Model_Session extends XFCP_bdCloudServerHelper_XenForo_Model_Session
{
    public function updateUserLastActivityFromSessions($cutOffDate = null)
    {
        if (bdCloudServerHelper_Option::get('redis', 'session_activity')) {
            $values = bdCloudServerHelper_Helper_Redis::getValues('session_activity');

            $db = $this->_getDb();

            foreach ($values as $userId => $timestamp) {
                try {
                    $db->update('xf_user',
                        array('last_activity' => $timestamp),
                        array('user_id = ?' => $userId)
                    );
                } catch (Zend_Db_Exception $e) {
                    // stop running, for now
                    return;
                }

                bdCloudServerHelper_Helper_Redis::clearCounter('session_activity', $userId);
            }
        }

        // still let the default method run to handle left over data
        parent::updateUserLastActivityFromSessions($cutOffDate);
    }

}