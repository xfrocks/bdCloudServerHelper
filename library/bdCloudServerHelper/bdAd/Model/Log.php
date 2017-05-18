<?php

class bdCloudServerHelper_bdAd_Model_Log extends XFCP_bdCloudServerHelper_bdAd_Model_Log
{
    public function logAdClick($adId)
    {
        if (bdCloudServerHelper_Option::get('redis', 'bdAd')) {
            bdCloudServerHelper_Helper_Redis::increaseCounter('bdad_click', $adId);

            // prevent the default logging mechanism
            return;
        }

        parent::logAdClick($adId);
    }

    public function aggregateAdClicks()
    {
        if (bdCloudServerHelper_Option::get('redis', 'bdAd')) {
            bdCloudServerHelper_Helper_RedisValueSync::bdAdClick();
        }

        // still let the default method run to handle left over data
        parent::aggregateAdClicks();
    }

    public function logAdView($adId)
    {
        if (bdCloudServerHelper_Option::get('redis', 'bdAd')) {
            bdCloudServerHelper_Helper_Redis::increaseCounter('bdad_view', $adId);

            // prevent the default logging mechanism
            return;
        }

        parent::logAdView($adId);
    }

    public function logAdViews(array $adIds)
    {
        if (bdCloudServerHelper_Option::get('redis', 'bdAd')) {
            foreach ($adIds as $adId) {
                $this->logAdView($adId);
            }

            // prevent the default logging mechanism
            return;
        }

        parent::logAdViews($adIds);
    }

    public function aggregateAdViews()
    {
        if (bdCloudServerHelper_Option::get('redis', 'bdAd')) {
            bdCloudServerHelper_Helper_RedisValueSync::bdAdView();
        }

        // still let the default method run to handle left over data
        parent::aggregateAdViews();
    }
}