<?php

class bdCloudServerHelper_Helper_Template
{
    public static $maxDateDelta = 300;

    protected static $_metadata = null;

    /**
     * @see bdCloudServerHelper_XenForo_Template_Public
     */
    public static function setup()
    {
        class_exists('bdCloudServerHelper_XenForo_Template_Public');
    }

    public static function handlePing()
    {
        self::_readMetadata();

        $lastModifiedDate = XenForo_Application::getDb()->fetchOne('SELECT MAX(last_modified_date) FROM xf_style');
        $lastModifiedDate = intval($lastModifiedDate);
        if (self::$_metadata['builtDate'] === $lastModifiedDate) {
            self::_log('Up to date');
            return false;
        }

        if (self::$_metadata['inProgressDate'] >= $lastModifiedDate) {
            self::_log('Already in progress: %d', self::$_metadata['inProgressDate']);
            return false;
        }

        $oldDate = self::$_metadata['builtDate'];
        self::$_metadata['inProgressDate'] = $lastModifiedDate;
        self::_saveMetadata();
        self::_log('Start rebuilding for %d', self::$_metadata['inProgressDate']);

        try {
            $templates = XenForo_Application::getDb()->query('SELECT * FROM xf_template_compiled');
            while ($template = $templates->fetch()) {
                bdCloudServerHelper_XenForo_Template_FileHandler::saveWithDate(self::$_metadata['inProgressDate'],
                    $template['title'], $template['style_id'], $template['language_id'],
                    $template['template_compiled']);
            }
        } catch (Exception $e) {
            XenForo_Error::logException($e, false);
        }

        self::$_metadata['builtDate'] = self::$_metadata['inProgressDate'];
        self::$_metadata['inProgressDate'] = 0;
        self::_saveMetadata();
        self::_log('Finished rebuilding for %d', self::$_metadata['builtDate']);

        if ($oldDate > 0) {
            bdCloudServerHelper_XenForo_Template_FileHandler::deleteWithDate($oldDate, null, null, null);
            self::_log('Deleted files for %d', $oldDate);
        }

        return true;
    }

    public static function loadTemplateFilePath($title, $styleId, $languageId)
    {
        self::_readMetadata();

        if (!isset(self::$_metadata['lastModifiedDates'][$styleId])) {
            return '';
        }

        $dateDelta = self::$_metadata['lastModifiedDates'][$styleId] - self::$_metadata['builtDate'];
        if ($dateDelta > self::$maxDateDelta) {
            // do not use the files if they are too old
            return '';
        }

        return bdCloudServerHelper_XenForo_Template_FileHandler::getWithDate(self::$_metadata['builtDate'],
            $title, $styleId, $languageId);
    }

    protected static function _getEffectiveDate()
    {
        self::_readMetadata();
        return self::$_metadata['effectiveDate'];
    }

    protected static function _readMetadata()
    {
        if (self::$_metadata !== null) {
            return false;
        }

        self::$_metadata = array(
            'inProgressDate' => 0,
            'builtDate' => 0,
            'lastModifiedDates' => array(),
        );

        $metadataPath = self::_getMetadataPath();
        if (file_exists($metadataPath)) {
            $metadataJson = file_get_contents($metadataPath);
            if (!empty($metadataJson)) {
                $metadataArray = json_decode($metadataJson, true);
                foreach (array_keys(self::$_metadata) as $key) {
                    if (!isset($metadataArray[$key])) {
                        continue;
                    }

                    self::$_metadata[$key] = $metadataArray[$key];
                }
            }
        }

        if (XenForo_Application::isRegistered('styles')) {
            $styles = XenForo_Application::get('styles');
            foreach ($styles as $style) {
                self::$_metadata['lastModifiedDates'][$style['style_id']] = $style['last_modified_date'];
            }
        }

        return true;
    }

    protected static function _saveMetadata()
    {
        return file_put_contents(self::_getMetadataPath(), json_encode(self::$_metadata), LOCK_EX);
    }

    protected static function _getMetadataPath()
    {
        return XenForo_Helper_File::getInternalDataPath() . '/templates-bdCloudServerHelper.json';
    }

    protected static function _log()
    {
        if (!XenForo_Application::debugMode()) {
            return false;
        }

        $args = func_get_args();
        $string = call_user_func_array('sprintf', $args);
        return XenForo_Helper_File::log(__CLASS__, $string);
    }
}