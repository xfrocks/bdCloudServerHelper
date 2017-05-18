<?php

class bdCloudServerHelper_Deferred_RedisValueSync extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $addOns = XenForo_Application::get('addOns');

        bdCloudServerHelper_Helper_RedisValueSync::attachmentView();

        if (!empty($addOns['bdAd'])) {
            bdCloudServerHelper_Helper_RedisValueSync::bdAdClick();
            bdCloudServerHelper_Helper_RedisValueSync::bdAdView();
        }

        bdCloudServerHelper_Helper_RedisValueSync::imageProxyView();

        bdCloudServerHelper_Helper_RedisValueSync::ipLogin();

        bdCloudServerHelper_Helper_RedisValueSync::sessionActivity();

        bdCloudServerHelper_Helper_RedisValueSync::threadView();
    }
}