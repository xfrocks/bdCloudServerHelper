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
}