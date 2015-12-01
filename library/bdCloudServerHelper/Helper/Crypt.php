<?php

class bdCloudServerHelper_Helper_Crypt
{
    public static function oneWayHash($data, $key = '')
    {
        return hash_hmac('md5', serialize($data),
            XenForo_Application::getConfig()->get('globalSalt') . $key);
    }
}