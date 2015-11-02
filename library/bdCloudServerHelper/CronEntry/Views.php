<?php

class bdCloudServerHelper_CronEntry_Views
{
    public static function update()
    {
        /** @var bdCloudServerHelper_XenForo_Model_ImageProxy $imageProxyModel */
        $imageProxyModel = XenForo_Model::create('XenForo_Model_ImageProxy');
        $imageProxyModel->bdCloudServerHelper_updateImageViews();
    }
}