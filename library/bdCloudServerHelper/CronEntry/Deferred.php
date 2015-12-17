<?php

class bdCloudServerHelper_CronEntry_Deferred
{
    public static function run()
    {
        if (bdCloudServerHelper_Option::get('redis', 'image_proxy_view')) {
            /** @var bdCloudServerHelper_XenForo_Model_ImageProxy $imageProxyModel */
            $imageProxyModel = XenForo_Model::create('XenForo_Model_ImageProxy');
            $imageProxyModel->bdCloudServerHelper_updateImageViews();
        }

        if (bdCloudServerHelper_Option::get('redis', 'ip_login')) {
            /** @var bdCloudServerHelper_XenForo_Model_Ip $ipModel */
            $ipModel = XenForo_Model::create('XenForo_Model_Ip');
            $ipModel->bdCloudServerHelper_logLoginIps();
        }
    }
}