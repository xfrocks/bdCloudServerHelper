<?php

/* @var $app XenForo_Application */
$app = XenForo_Application::getInstance();
$phpSource = file_get_contents($app->getRootDir() . '/library/XenForo/Template/Public.php');
$phpSource = substr($phpSource, strlen('<?php'));
$phpSource = str_replace('class XenForo_Template_Public', 'class _XenForo_Template_Public', $phpSource);
eval($phpSource);

/**
 * @see XenForo_Template_Public
 */
class bdCloudServerHelper_XenForo_Template_Public extends _XenForo_Template_Public
{
    protected $_usingTemplateFiles = false;

    protected function _loadTemplateFilePath($templateName)
    {
        $path = bdCloudServerHelper_Helper_Template::loadTemplateFilePath($templateName,
            self::$_styleId, self::$_languageId);

        if (strlen($path) > 0) {
            $this->_usingTemplateFiles = true;
        }

        return $path;
    }

    protected function _usingTemplateFiles()
    {
        return $this->_usingTemplateFiles;
    }
}

eval('class XenForo_Template_Public extends bdCloudServerHelper_XenForo_Template_Public {}');
