<?php

class bdCloudServerHelper_XenForo_CssOutput extends XFCP_bdCloudServerHelper_XenForo_CssOutput
{
    protected function _prepareForOutput()
    {
        if (XenForo_Application::getOptions()->get('templateFiles')) {
            bdCloudServerHelper_Helper_Template::makeSureTemplatesAreUpToDate();
        }

        parent::_prepareForOutput();
    }

}