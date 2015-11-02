<?php

class bdCloudServerHelper_XenForo_Model_ImageProxy extends XFCP_bdCloudServerHelper_XenForo_Model_ImageProxy
{
    public function logImageView(array $image)
    {
        if (!empty($image['image_id'])
            && bdCloudServerHelper_Option::get('redis', 'image_proxy_view')
        ) {
            bdCloudServerHelper_Helper_Redis::increaseCounter('image_proxy_view', $image['image_id']);

            // prevent the default logging mechanism
            return true;
        }

        return parent::logImageView($image);
    }

    public function bdCloudServerHelper_updateImageViews()
    {
        if (!bdCloudServerHelper_Option::get('redis', 'image_proxy_view')) {
            return;
        }

        $values = bdCloudServerHelper_Helper_Redis::getCounters('image_proxy_view');

        $db = $this->_getDb();

        foreach ($values as $imageId => $value) {
            $db->query('
                    UPDATE xf_image_proxy
                    SET views = views + ?,
                        last_request_date = ?
                    WHERE image_id = ?
                ', array($value, XenForo_Application::$time, $imageId));

            bdCloudServerHelper_Helper_Redis::clearCounter('image_proxy_view', $imageId);
        }
    }
}