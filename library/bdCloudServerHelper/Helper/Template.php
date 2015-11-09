<?php

class bdCloudServerHelper_Helper_Template
{
    protected static $_templatesUpdated = 0;
    protected static $_templatesIsUpdating = false;

    /**
     * Generates script to keep track of last updated time of templates directory.
     * @param bool $isUpdating
     */
    public static function markTemplatesAsUpdated($isUpdating = false)
    {
        file_put_contents(self::_getTemplatesUpdatedScriptPath(), sprintf(
            "<?php\nbdCloudServerHelper_Helper_Template::setTemplatesUpdated(%s, %s);",
            var_export(time(), true),
            $isUpdating ? 'true' : 'false'
        ), LOCK_EX);
    }

    public static function makeSureTemplatesAreUpToDate(XenForo_FrontController $fc = null)
    {
        /** @noinspection PhpIncludeInspection */
        @include(self::_getTemplatesUpdatedScriptPath());

        if (self::$_templatesIsUpdating) {
            // templates are being updated
            // temporary switch to use db
            XenForo_Application::getOptions()->set('templateFiles', false);

            if (XenForo_Application::debugMode()
                && $fc != null
            ) {
                $fc->getResponse()->setHeader('X-Templates-Files-Updating', 'yes');
            }
        }

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
            self::markTemplatesAsUpdated(true);

            /** @var XenForo_Model_Template $templateModel */
            $templateModel = XenForo_Model::create('XenForo_Model_Template');
            $templateModel->writeTemplateFiles(false, false);

            self::markTemplatesAsUpdated(false);

            if (XenForo_Application::debugMode()
                && $fc != null
            ) {
                $fc->getResponse()->setHeader('X-Templates-Files-Updated', 'yes');
            }
        }

        if (XenForo_Application::debugMode()
            && $fc != null
        ) {
            $fc->getResponse()->setHeader('X-Templates-From-Files',
                XenForo_Application::getOptions()->get('templateFiles') ? 'yes' : 'no');
        }
    }

    /**
     * Sets updated timestamp for templates directory.
     * This method should only be called from our auto-generated script.
     *
     * @param int $timestamp
     * @param bool $isUpdating
     */
    public static function setTemplatesUpdated($timestamp, $isUpdating = false)
    {
        self::$_templatesUpdated = max(self::$_templatesUpdated, $timestamp);
        self::$_templatesIsUpdating = self::$_templatesIsUpdating || $isUpdating;
    }

    protected static function _getTemplatesUpdatedScriptPath()
    {
        return XenForo_Helper_File::getInternalDataPath() . '/templates-updated.php';
    }
}