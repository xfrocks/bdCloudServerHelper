<?php

class bdCloudServerHelper_Listener
{
    protected static $_templateFileChanged = 0;

    public static function init_dependencies()
    {
        $redisOption = bdCloudServerHelper_Option::get('redis');
        if ($redisOption['attachment_view'] > 0) {
            XenForo_CodeEvent::addListener('load_class_model',
                array(__CLASS__, 'load_class_XenForo_Model_Attachment'),
                'XenForo_Model_Attachment');
        }
        if ($redisOption['thread_view'] > 0) {
            XenForo_CodeEvent::addListener('load_class_model',
                array(__CLASS__, 'load_class_XenForo_Model_Thread'),
                'XenForo_Model_Thread');
        }
    }

    public static function load_class_XenForo_Model_Attachment($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Attachment') {
            $extend[] = 'bdCloudServerHelper_XenForo_Model_Attachment';
        }
    }

    public static function load_class_XenForo_Model_Thread($class, array &$extend)
    {
        if ($class === 'XenForo_Model_Thread') {
            $extend[] = 'bdCloudServerHelper_XenForo_Model_Thread';
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