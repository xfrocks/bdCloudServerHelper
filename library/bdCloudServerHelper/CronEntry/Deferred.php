<?php

class bdCloudServerHelper_CronEntry_Deferred
{
    public static function run()
    {
        if (bdCloudServerHelper_Option::get('redis', 'image_proxy_view')) {
            bdCloudServerHelper_Helper_RedisValueSync::imageProxyView();
        }

        if (bdCloudServerHelper_Option::get('redis', 'ip_login')) {
            bdCloudServerHelper_Helper_RedisValueSync::ipLogin();
        }

        // include daily clean up tasks
        // so it puts less stress on the db server
        XenForo_CronEntry_CleanUp::runDailyCleanUp();
    }
}