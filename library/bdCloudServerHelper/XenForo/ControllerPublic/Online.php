<?php

class bdCloudServerHelper_XenForo_ControllerPublic_Online
    extends XFCP_bdCloudServerHelper_XenForo_ControllerPublic_Online
{
    public function actionIndex()
    {
        if (bdCloudServerHelper_Option::get('voidSessionActivities')) {
            return $this->responseNoPermission();
        }

        return parent::actionIndex();
    }

}