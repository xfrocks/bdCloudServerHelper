<?php

class bdCloudServerHelper_Listener
{
    protected static $_templateFileChanged = 0;

    public static function front_controller_pre_view(
        /** @noinspection PhpUnusedParameterInspection */
        XenForo_FrontController $fc,
        XenForo_ControllerResponse_Abstract &$controllerResponse,
        XenForo_ViewRenderer_Abstract &$viewRenderer,
        array &$containerParams)
    {
        if (self::$_templateFileChanged > 0) {
            bdCloudServerHelper_Helper_Template::markTemplatesAsUpdated();
            return;
        }

        if (XenForo_Application::getOptions()->get('templateFiles')
            && $fc->getDependencies() instanceof XenForo_Dependencies_Public
        ) {
            bdCloudServerHelper_Helper_Template::makeSureTemplatesAreUpToDate();
        }
    }

    public static function template_file_change(
        /** @noinspection PhpUnusedParameterInspection */
        $file,
        $action)
    {
        if ($action == 'delete') {
            // ignore delete events to avoid wasting server resources
            return;
        }

        self::$_templateFileChanged++;
    }

    public static function file_health_check(
        /** @noinspection PhpUnusedParameterInspection */
        XenForo_ControllerAdmin_Abstract $controller,
        array &$hashes)
    {
        $hashes += bdCloudServerHelper_FileSums::getHashes();
    }
}