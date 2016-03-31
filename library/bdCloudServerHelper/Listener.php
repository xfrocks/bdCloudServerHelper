<?php

class bdCloudServerHelper_Listener
{
    protected static $_templateFileChanged = 0;
    protected static $_classes = array(
        'XenForo_CssOutput' => true,
    );

    protected static $_isReadOnly = false;

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies)
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

        $optionRedis = bdCloudServerHelper_Option::get('redis');
        if (!empty($optionRedis['attachment_view'])) {
            self::$_classes['XenForo_Model_Attachment'] = true;
        }
        if (!empty($optionRedis['image_proxy_view'])) {
            self::$_classes['XenForo_Model_ImageProxy'] = true;
        }
        if (!empty($optionRedis['ip_login'])) {
            self::$_classes['XenForo_Model_Ip'] = true;
        }
        if (!empty($optionRedis['thread_view'])) {
            self::$_classes['XenForo_Model_Thread'] = true;
        }
        if (!empty($optionRedis['session_activity'])) {
            self::$_classes['XenForo_Model_Session'] = true;
            self::$_classes['XenForo_Model_User'] = true;
        }
        if (!empty($optionRedis['bdAd'])) {
            self::$_classes['bdAd_Model_Log'] = true;
        }

        if (bdCloudServerHelper_Option::get('redisStats')) {
            self::$_classes['bdCache_Core'] = true;
        }

        $optionCache = bdCloudServerHelper_Option::get('cache');
        if (!empty($optionCache['search'])) {
            self::$_classes['XenForo_Model_Search'] = true;
        }

        $config = XenForo_Application::getConfig();
        if ($config->get('bdCloudServerHelper_readOnly')
            && $dependencies instanceof XenForo_Dependencies_Public
        ) {
            self::$_isReadOnly = true;
            self::$_classes['XenForo_Session'] = true;
            XenForo_Application::set('_bdCloudServerHelper_readonly', true);
        }
    }

    public static function load_class($class, array &$extend)
    {
        if (isset(self::$_classes[$class])) {
            $extend[] = 'bdCloudServerHelper_' . $class;
        }
    }

    public static function load_class_XenForo_ViewAdmin_Log_ServerErrorView($class, array &$extend)
    {
        if ($class === 'XenForo_ViewAdmin_Log_ServerErrorView') {
            $extend[] = 'bdCloudServerHelper_XenForo_ViewAdmin_Log_ServerErrorView';
        }
    }

    public static function load_class_XenForo_BbCode_Formatter_Base($class, array &$extend)
    {
        if ($class === 'XenForo_BbCode_Formatter_Base') {
            $extend[] = 'bdCloudServerHelper_XenForo_BbCode_Formatter_Base';
        }
    }

    public static function front_controller_pre_view(
        /** @noinspection PhpUnusedParameterInspection */
        XenForo_FrontController $fc,
        XenForo_ControllerResponse_Abstract &$controllerResponse,
        XenForo_ViewRenderer_Abstract &$viewRenderer,
        array &$containerParams
    ) {
        if (self::$_templateFileChanged > 0) {
            bdCloudServerHelper_Helper_Template::markTemplatesAsUpdated();
            return;
        }

        if (XenForo_Application::getOptions()->get('templateFiles')
            && $fc->getDependencies() instanceof XenForo_Dependencies_Public
        ) {
            bdCloudServerHelper_Helper_Template::makeSureTemplatesAreUpToDate($fc);
        }

        if (XenForo_Application::debugMode()) {
            $fc->getResponse()->setHeader('X-XenForo-Hostname', gethostname());
        }

        if (self::isReadOnly()) {
            $dependencies = $fc->getDependencies();
            if ($dependencies instanceof XenForo_Dependencies_Public) {
                $noticeParam = 'bdCloudServerHelper_showBoardReadOnlyNotice';
                // new XenForo_Phrase('bdcsh_notice_board_read_only')
                $noticeKey = 'bdcsh_notice_board_read_only';
                $dependencies->notices[$noticeParam] = $noticeKey;
                $containerParams[$noticeParam] = true;
            }
        }
    }

    public static function front_controller_post_view(
        XenForo_FrontController $fc,
        /** @noinspection PhpUnusedParameterInspection */
        &$output
    ) {
        if (bdCloudServerHelper_Option::get('redisStats')
            && $fc->getDependencies() instanceof XenForo_Dependencies_Public
        ) {
            bdCloudServerHelper_Helper_Stats::log($fc);
        }
    }

    public static function template_file_change(
        /** @noinspection PhpUnusedParameterInspection */
        $file,
        $action
    ) {
        if ($action == 'delete') {
            // ignore delete events to avoid wasting server resources
            return;
        }

        self::$_templateFileChanged++;
    }

    public static function file_health_check(
        /** @noinspection PhpUnusedParameterInspection */
        XenForo_ControllerAdmin_Abstract $controller,
        array &$hashes
    ) {
        $hashes += bdCloudServerHelper_FileSums::getHashes();
    }

    public static function isReadOnly()
    {
        return self::$_isReadOnly;
    }
}