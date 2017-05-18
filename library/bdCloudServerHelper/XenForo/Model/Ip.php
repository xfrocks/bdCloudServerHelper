<?php

class bdCloudServerHelper_XenForo_Model_Ip extends XFCP_bdCloudServerHelper_XenForo_Model_Ip
{
    public function logIp($userId, $contentType, $contentId, $action, $ipAddress = null, $date = null)
    {
        if (bdCloudServerHelper_Option::get('redis', 'ip_login')
            && $userId === $contentId
            && $action === 'login'
        ) {
            $ipAddress = XenForo_Helper_Ip::getBinaryIp(null, $ipAddress);
            if ($ipAddress) {
                if ($date === null) {
                    $date = XenForo_Application::$time;
                }

                bdCloudServerHelper_Helper_Redis::setValue('ip_login', $userId, serialize(array(
                    'ip' => $ipAddress,
                    'log_date' => $date,
                )));

                // prevent the default tracking mechanism
                return 0;
            }
        }

        return parent::logIp($userId, $contentType, $contentId, $action, $ipAddress, $date);
    }
}