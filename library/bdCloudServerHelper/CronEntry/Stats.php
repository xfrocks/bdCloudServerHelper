<?php

class bdCloudServerHelper_CronEntry_Stats
{
    public static function aggregate()
    {
        if (!bdCloudServerHelper_Option::get('redisStats')) {
            return;
        }

        bdCloudServerHelper_Helper_Stats::aggregate();
    }

}