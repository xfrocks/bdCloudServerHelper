<?php

class bdCloudServerHelper_XenForo_Session extends XFCP_bdCloudServerHelper_XenForo_Session
{
    const SESSION_ID_READ_ONLY = 'read_only';

    protected function _setup($sessionId = '', $ipAddress = false, array $defaultSession = null)
    {
        parent::_setup(self::SESSION_ID_READ_ONLY, $ipAddress, $defaultSession);
    }

    public function getSessionFromSource($sessionId)
    {
        if ($sessionId === self::SESSION_ID_READ_ONLY) {
            return array(
                'sessionCsrf' => XenForo_Application::generateRandomString(16),
                'sessionStart' => XenForo_Application::$time,
                'user_id' => 0,
            );
        }

        return false;
    }

    public function deleteSessionFromSource($sessionId)
    {
        // intentionally left empty
    }

    public function saveSessionToSource($sessionId, $isUpdate)
    {
        // intentionally left empty
    }
}