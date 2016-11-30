<?php

class bdCloudServerHelper_Listener
{
    protected static $_classes = array();

    protected static $_isReadOnly = false;

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        $requestPaths = XenForo_Application::get('requestPaths');
        if (XenForo_Application::$secure === false
            && isset($requestPaths['protocol'])
            && $requestPaths['protocol'] === 'http'
            && ((
                    isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                    && isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                    && isset($_SERVER['REMOTE_ADDR'])

                    && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
                    && $_SERVER['HTTP_X_FORWARDED_FOR'] === $_SERVER['REMOTE_ADDR']
                ) || (
                    isset($_SERVER['SERVER_PROTOCOL'])
                    && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/2.0'
                ))
        ) {
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
        if (isset($data['routesPublic'])
            && $config->get('bdCloudServerHelper_readOnly')
        ) {
            self::$_isReadOnly = true;
            self::$_classes['XenForo_Session'] = true;
            XenForo_Application::set('_bdCloudServerHelper_readonly', true);
        }
        if (isset($data['routesPublic'])
            && $config->get('bdCloudServerHelper_templateFiles')
        ) {
            bdCloudServerHelper_Helper_Template::setup();
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
        if (XenForo_Application::$secure
            && $hstsAge = XenForo_Application::getConfig()->get('bdCloudServerHelper_hstsAge', 15768000)
        ) {
            $fc->getResponse()->setHeader('Strict-Transport-Security', sprintf('max-age=%d', $hstsAge));
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

    public static function load_class_XenForo_ControllerAdmin_Phrase($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_Phrase') {
            $extend[] = 'bdCloudServerHelper_XenForo_ControllerAdmin_Phrase';
        }
    }

    public static function load_class_XenForo_Model_Phrase($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Phrase') {
            $extend[] = 'bdCloudServerHelper_XenForo_Model_Phrase';
        }
    }
}