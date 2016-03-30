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
            $values = bdCloudServerHelper_Helper_Redis::getValues('bdad_click');

            $db = $this->_getDb();

            foreach ($values as $adId => $value) {
                try {
                    $db->query('
                        UPDATE xf_bdad_ad
                        SET click_count = click_count + ?
                        WHERE ad_id = ?
                    ', array($value, $adId));
                } catch (Zend_Db_Exception $e) {
                    // stop running, for now
                    return;
                }

                bdCloudServerHelper_Helper_Redis::clearCounter('bdad_click', $adId);
            }
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
            $values = bdCloudServerHelper_Helper_Redis::getValues('bdad_view');

            $db = $this->_getDb();

            foreach ($values as $adId => $value) {
                try {
                    $db->query('
                        UPDATE xf_bdad_ad
                        SET view_count = view_count + ?
                        WHERE ad_id = ?
                    ', array($value, $adId));
                } catch (Zend_Db_Exception $e) {
                    // stop running, for now
                    return;
                }

                bdCloudServerHelper_Helper_Redis::clearCounter('bdad_view', $adId);
            }
        }

        // still let the default method run to handle left over data
        parent::aggregateAdViews();
    }
}