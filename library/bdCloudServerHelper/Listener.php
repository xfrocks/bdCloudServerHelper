<?php

class bdCloudServerHelper_Listener
{
    protected static $_templateFileChanged = 0;

    public static function init_dependencies()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $requestPaths = XenForo_Application::get('requestPaths');
            $requestPaths['protocol'] = 'https';
            $hostWithoutPort = preg_replace('#:\d+$#', '', $requestPaths['host']);
            $requestPaths['fullBasePath'] = $requestPaths['protocol'] . '://' . $hostWithoutPort . $requestPaths['basePath'];
            $requestPaths['fullUri'] = $requestPaths['protocol'] . '://' . $hostWithoutPort . $requestPaths['requestUri'];
            XenForo_Application::set('requestPaths', $requestPaths);

            XenForo_Application::$secure = true;
        }

        // inject hostname into $_POST to make it available in server error log
        $_POST['.hostname'] = gethostname();
    }

    public static function load_class_XenForo_ControllerPublic_Online($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Online') {
            $extend[] = 'bdCloudServerHelper_XenForo_ControllerPublic_Online';
        }
    }

    public static function load_class_XenForo_Model_Attachment($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Attachment') {
            $extend[] = 'bdCloudServerHelper_XenForo_Model_Attachment';
        }
    }

    public static function load_class_XenForo_Model_Session($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Session') {
            $extend[] = 'bdCloudServerHelper_XenForo_Model_Session';
        }
    }

    public static function load_class_XenForo_Model_Thread($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Thread') {
            $extend[] = 'bdCloudServerHelper_XenForo_Model_Thread';
        }
    }

    public static function load_class_XenForo_ViewAdmin_Log_ServerErrorView($class, array &$extend)
    {
        if ($class === 'XenForo_ViewAdmin_Log_ServerErrorView') {
            $extend[] = 'bdCloudServerHelper_XenForo_ViewAdmin_Log_ServerErrorView';
        }
    }

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

        if (XenForo_Application::debugMode()) {
            $fc->getResponse()->setHeader('X-XenForo-Hostname', gethostname());
        }
    }

    public static function front_controller_post_view(
        XenForo_FrontController $fc,
        /** @noinspection PhpUnusedParameterInspection */
        &$output)
    {
        if (bdCloudServerHelper_Option::get('redisStats')
            && $fc->getDependencies() instanceof XenForo_Dependencies_Public
        ) {
            bdCloudServerHelper_Helper_Stats::log($fc);
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