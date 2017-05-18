<?php

class bdCloudServerHelper_Listener
{
    const CONFIG_HOSTNAME = 'bdCloudServerHelper_hostname';
    const CONFIG_HSTS_AGE = 'bdCloudServerHelper_hstsAge';
    const CONFIG_INFLUXDB = 'bdCloudServerHelper_influxdb';
    const CONFIG_READONLY = 'bdCloudServerHelper_readOnly';
    const CONFIG_REBUILD_DATA_REGISTRY_CACHE_INTERVAL = 'bdCloudServerHelper_rebuildDrcInterval';
    const CONFIG_REDIS = 'bdCloudServerHelper_redis';
    const CONFIG_TEMPLATE_FILES = 'bdCloudServerHelper_templateFiles';
    const CONFIG_TEMPLATE_REBUILD_CMD = 'bdCloudServerHelper_templateRebuildCmd';
    const CONFIG_VALID_HOST = 'bdCloudServerHelper_validHost';

    const CACHE_REBUILD_DATA_REGISTRY_CACHE_TIME = 'bdCloudServerHelper_rebuildDrcTime';

    protected static $_classes = array();

    protected static $_hostname = '';
    protected static $_isReadOnly = false;

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        $config = XenForo_Application::getConfig();
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

        $scriptFileName = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

        // inject hostname into $_POST to make it available in server error log
        self::$_hostname = $config->get(self::CONFIG_HOSTNAME);
        if (empty(self::$_hostname)) {
            self::$_hostname = gethostname();
        }
        $_POST['.hostname'] = self::$_hostname;

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

        $optionInfluxdb = bdCloudServerHelper_Option::get('influxdb');
        if (!empty($optionInfluxdb['cron'])) {
            self::$_classes['XenForo_Model_Cron'] = true;
        }

        if (isset($data['routesPublic'])
            && $config->get(self::CONFIG_READONLY)
            && $scriptFileName === 'index.php'
        ) {
            self::$_isReadOnly = true;
            self::$_classes['XenForo_Model_User'] = true;
            self::$_classes['XenForo_Session'] = true;
            XenForo_Application::set('_bdCloudServerHelper_readonly', true);
        }

        if (isset($_SERVER['REQUEST_METHOD'])
            && $_SERVER['REQUEST_METHOD'] === 'GET'
            && !self::$_isReadOnly
            && !XenForo_Application::debugMode()
        ) {
            self::_assertValidHost($config, $requestPaths);
        }

        if (isset($data['routesPublic'])
            && $config->get(self::CONFIG_TEMPLATE_FILES)
        ) {
            // it's possible to setup a rebuild command that will be exec()'d
            // when the template files are found to be outdated,
            // to avoid slowing down the current request, the command should be something like
            // `nohup curl http://domain.com/xenforo/cloud/template.php > /dev/null 2>&1 &`
            // it's entirely optional though, the recommended way to rebuild the files
            // is to setup a cronjob pointing to the cloud/template.php file
            $rebuildCmd = $config->get(self::CONFIG_TEMPLATE_REBUILD_CMD);

            bdCloudServerHelper_Helper_Template::setup($rebuildCmd);
        }

        // setting manual data registry cache rebuilder here, it's intended to use if all web workers don't share
        // the same cache backend (e.g. web workers are put in clusters and there is 1 memcached server per cluster)
        // it's required to configure bdCloudServerHelper_Cache_Core as XenForo cache front-end to handle it properly
        $rebuildDataRegistryCacheInterval = intval($config->get(self::CONFIG_REBUILD_DATA_REGISTRY_CACHE_INTERVAL));
        if ($rebuildDataRegistryCacheInterval > 0
            && !in_array($scriptFileName, array('admin.php', 'css.php', 'index.php'), true)
        ) {
            self::_rebuildDataRegistryCache($rebuildDataRegistryCacheInterval);
        }

        if (isset($data['routesAdmin'])) {
            bdCloudServerHelper_ShippableHelper_Updater::onInitDependencies($dependencies);
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
            && $hstsAge = XenForo_Application::getConfig()->get(self::CONFIG_HSTS_AGE, 15768000)
        ) {
            $fc->getResponse()->setHeader('Strict-Transport-Security', sprintf('max-age=%d', $hstsAge));
        }

        if (XenForo_Application::debugMode()) {
            $fc->getResponse()->setHeader('X-XenForo-Hostname', self::getHostname());
        }

        if (self::isReadOnly()) {
            XenForo_Application::set('deferredRun', 0);

            $dependencies = $fc->getDependencies();
            if ($dependencies instanceof XenForo_Dependencies_Public) {
                $noticeParam = 'bdCloudServerHelper_showBoardReadOnlyNotice';
                // new XenForo_Phrase('bdcsh_notice_board_read_only')
                $noticeKey = 'bdcsh_notice_board_read_only';
                $dependencies->notices[$noticeParam] = $noticeKey;
                $containerParams[$noticeParam] = true;
            }

            $containerParams['noSocialLogin'] = true;
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

    public static function getHostname()
    {
        return self::$_hostname;
    }

    public static function isReadOnly()
    {
        return self::$_isReadOnly;
    }

    protected static function _assertValidHost(Zend_Config $config, array $requestPaths)
    {
        $validHost = $config->get(self::CONFIG_VALID_HOST);
        if (!is_string($validHost)) {
            return;
        }

        if (isset($_SERVER['HTTP_X_HOST_HASH'])
            && $_SERVER['HTTP_X_HOST_HASH'] === md5($requestPaths['host'] . $config->get('globalSalt'))
        ) {
            // custom request header X-Host-Hash provided with a valid hash
            // let it go through
            return;
        }

        if ($requestPaths['host'] === $validHost) {
            // string matches
            return;
        }

        if (substr($validHost, 0, 1) === '/'
            && preg_match($validHost, $requestPaths['host'])
        ) {
            // regex matches
            return;
        }

        // force redirect
        $target = $requestPaths['fullUri'];
        $target = preg_replace('#^' . preg_quote(rtrim($requestPaths['fullBasePath'], '/'), '#') . '#',
            rtrim(XenForo_Application::getOptions()->get('boardUrl'), '/'), $target);
        header('Location: ' . $target);
        exit;
    }

    protected static function _rebuildDataRegistryCache($interval)
    {
        $rebuilt = 0;

        $cache = XenForo_Application::getCache();
        if (empty($cache)) {
            // no cache? Nothing to do
            return $rebuilt;
        }

        $lastRebuildTime = $cache->load(self::CACHE_REBUILD_DATA_REGISTRY_CACHE_TIME);
        $cutoff = XenForo_Application::$time - $interval;
        if ($lastRebuildTime > $cutoff) {
            // data is still fresh
            return $rebuilt;
        }

        $lockOk = false;
        $f = fopen(XenForo_Helper_File::getInternalDataPath() . DIRECTORY_SEPARATOR . __METHOD__, 'w');
        if ($f && flock($f, LOCK_EX | LOCK_NB)) {
            $lockOk = true;
        }
        if ($lockOk) {
            fwrite($f, strval(XenForo_Application::$time));

            if (XenForo_Application::debugMode()) {
                fwrite($f, var_export($_SERVER, true));
            }
        }
        if (!$lockOk) {
            // we didn't have a lock
            // maybe some other php processes are already rebuilding it
            fclose($f);
            return $rebuilt;
        }

        $cache->save(strval(XenForo_Application::$time),
            self::CACHE_REBUILD_DATA_REGISTRY_CACHE_TIME, array(), $interval);

        /** @var bdCloudServerHelper_Model_DataRegistry $ourDataRegistryModel */
        $ourDataRegistryModel = XenForo_Model::create('bdCloudServerHelper_Model_DataRegistry');
        $rebuilt = $ourDataRegistryModel->rebuildCache();

        fclose($f);
        return $rebuilt;
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