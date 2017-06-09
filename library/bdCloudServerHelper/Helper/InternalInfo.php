<?php

class bdCloudServerHelper_Helper_InternalInfo
{
    const HOSTNAME = 'h';
    const PAGE_TIME = 't';
    const READ_ONLY = 'ro';
    const VISITOR_USER_ID = 'u';

    const RENDER_FORUM_ID = 'rf';
    const RENDER_TEMPLATE_NAME = 'rv';
    const RENDER_THREAD_ID = 'rt';
    const RENDER_USER_ID = 'ru';

    protected static $_data = array();

    public static function onControllerResponse(XenForo_ControllerResponse_Abstract &$cr)
    {
        if ($cr instanceof XenForo_ControllerResponse_View) {
            self::onControllerResponseView($cr);
        }
    }

    public static function onControllerResponseView(XenForo_ControllerResponse_View &$cr)
    {
        self::$_data[self::RENDER_TEMPLATE_NAME] = $cr->templateName;

        $paramsRef = &$cr->params;

        if (isset($paramsRef['user']) && !empty($paramsRef['user']['user_id'])) {
            self::$_data[self::RENDER_USER_ID] = $paramsRef['user']['user_id'];
        }

        if (isset($paramsRef['forum']) && !empty($paramsRef['forum']['node_id'])) {
            self::$_data[self::RENDER_FORUM_ID] = $paramsRef['forum']['node_id'];
        }

        if (isset($paramsRef['thread']) && !empty($paramsRef['thread']['thread_id'])) {
            $threadRef =& $paramsRef['thread'];
            self::$_data[self::RENDER_THREAD_ID] = $threadRef['thread_id'];

            if (!empty($threadRef['user_id'])) {
                self::$_data[self::RENDER_USER_ID] = $threadRef['user_id'];
            }

            if (!empty($threadRef['node_id'])) {
                self::$_data[self::RENDER_FORUM_ID] = $threadRef['node_id'];
            }
        }
    }

    public static function setHeader(Zend_Controller_Response_Http $response, $headerName, $headerValuePrefix = '')
    {
        if (XenForo_Application::isRegistered('page_start_time')) {
            $pageTime = microtime(true) - XenForo_Application::get('page_start_time');
            self::$_data[self::PAGE_TIME] = sprintf('%.3f', $pageTime);
        }

        self::$_data[self::HOSTNAME] = bdCloudServerHelper_Listener::getHostname();
        if (bdCloudServerHelper_Listener::isReadOnly()) {
            self::$_data[self::READ_ONLY] = '';
        }
        self::$_data[self::VISITOR_USER_ID] = XenForo_Visitor::getUserId();

        ksort(self::$_data);
        $headerValue = array();
        foreach (self::$_data as $ifName => $ifValue) {
            $headerValue[] = sprintf('%s%s', $ifName, $ifValue);
        }
        $response->setHeader($headerName, $headerValuePrefix . implode(',', $headerValue), true);
    }
}