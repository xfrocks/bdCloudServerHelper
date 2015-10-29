<?php

class bdCloudServerHelper_Helper_Template
{
    protected static $_templatesUpdated = 0;

    /**
     * Generates script to keep track of last updated time of templates directory.
     */
    public static function markTemplatesAsUpdated()
    {
        file_put_contents(self::_getTemplatesUpdatedScriptPath(), sprintf(
            "<?php\nbdCloudServerHelper_Helper_Template::setTemplatesUpdated(%s);",
            var_export(time(), true)
        ), LOCK_EX);
    }

    public static function makeSureTemplatesAreUpToDate()
    {
        /** @noinspection PhpIncludeInspection */
        @include(self::_getTemplatesUpdatedScriptPath());

        $lastMod = 0;
        $visitor = XenForo_Visitor::getInstance();
        $styleId = (!empty($visitor['style_id']) ? $visitor['style_id'] : 0);
        if ($styleId === 0) {
            $styleId = XenForo_Application::getOptions()->get('defaultStyleId');
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $styles = (XenForo_Application::isRegistered('styles')
            ? XenForo_Application::get('styles')
            : XenForo_Model::create('XenForo_Model_Style')->getAllStyles()
        );
        if (!empty($styles[$styleId]['last_modified_date'])) {
            $lastMod = $styles[$styleId]['last_modified_date'];
        }

        if (self::$_templatesUpdated < $lastMod) {
            // mark it first to avoid concurrent requests all trying to rebuild
            self::markTemplatesAsUpdated();

            /** @var XenForo_Model_Template $templateModel */
            $templateModel = XenForo_Model::create('XenForo_Model_Template');
            $templateModel->writeTemplateFiles(false, false);
        }
    }

    /**
     * Sets updated timestamp for templates directory.
     * This method should only be called from our auto-generated script.
     *
     * @param $timestamp
     */
    public static function setTemplatesUpdated($timestamp)
    {
        self::$_templatesUpdated = max(self::$_templatesUpdated, $timestamp);
    }

    protected static function _getTemplatesUpdatedScriptPath()
    {
        return XenForo_Helper_File::getInternalDataPath() . '/templates-updated.php';
    }
}