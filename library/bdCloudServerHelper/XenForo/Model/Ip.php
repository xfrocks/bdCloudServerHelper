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

    public function bdCloudServerHelper_logLoginIps()
    {
        $values = bdCloudServerHelper_Helper_Redis::getValues('ip_login');

        $db = $this->_getDb();

        foreach ($values as $userId => $value) {
            $value = @unserialize($value);
            if (isset($value['ip'])
                && isset($value['log_date'])
            ) {
                $db->insert('xf_ip', array(
                    'user_id' => $userId,
                    'content_type' => 'user',
                    'content_id' => $userId,
                    'action' => 'login',
                    'ip' => $value['ip'],
                    'log_date' => $value['log_date'],
                ));
            }

            bdCloudServerHelper_Helper_Redis::clearCounter('ip_login', $userId);
        }
    }

}