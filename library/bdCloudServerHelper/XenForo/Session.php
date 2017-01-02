<?php

class bdCloudServerHelper_XenForo_Session extends XFCP_bdCloudServerHelper_XenForo_Session
{
    const SESSION_ID_READ_ONLY = 'bdCloudServerHelper_readOnlySessionId';

    protected function _setup($sessionId = '', $ipAddress = false, array $defaultSession = null)
    {
        if (bdCloudServerHelper_Listener::isReadOnly()) {
            $sessionId = self::SESSION_ID_READ_ONLY;
        }

        parent::_setup($sessionId, $ipAddress, $defaultSession);
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

        return parent::getSessionFromSource($sessionId);
    }

    public function deleteSessionFromSource($sessionId)
    {
        if ($sessionId === self::SESSION_ID_READ_ONLY) {
            // no op
            return;
        }

        parent::deleteSessionFromSource($sessionId);
    }

    public function saveSessionToSource($sessionId, $isUpdate)
    {
        if ($sessionId === self::SESSION_ID_READ_ONLY) {
            // no op
            return;
        }

        parent::saveSessionToSource($sessionId, $isUpdate);
    }
}