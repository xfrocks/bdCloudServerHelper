<?php

class bdCloudServerHelper_bdCache_Core extends XFCP_bdCloudServerHelper_bdCache_Core
{
    public function output(XenForo_FrontController &$fc, array &$cached)
    {
        if (bdCloudServerHelper_Option::get('redisStats')
            && $fc->getDependencies() instanceof XenForo_Dependencies_Public
        ) {
            bdCloudServerHelper_Helper_Stats::log($fc, 'cache_hit');
        }

        parent::output($fc, $cached);
    }

}