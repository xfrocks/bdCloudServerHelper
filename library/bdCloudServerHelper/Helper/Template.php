<?php

class bdCloudServerHelper_Helper_Template
{
    public static $maxDateDelta = 300;
    public static $randomMax = 1000;
    public static $randomRange = 50;

    protected static $_rebuildCmd = '';
    protected static $_metadata = null;
    protected static $_lastModifiedDates = null;

    /**
     * @param string $rebuildCmd
     *
     * @see bdCloudServerHelper_XenForo_Template_Public
     */
    public static function setup($rebuildCmd)
    {
        class_exists('bdCloudServerHelper_XenForo_Template_Public');

        // disable built-in template files feature
        XenForo_Application::getOptions()->set('templateFiles', false);

        self::$_rebuildCmd = $rebuildCmd;
    }

    public static function handlePing()
    {
        self::_readMetadata();

        $lastModifiedDate = XenForo_Application::getDb()->fetchOne('SELECT MAX(last_modified_date) FROM xf_style');
        $lastModifiedDate = intval($lastModifiedDate);
        if (self::$_metadata['builtDate'] >= $lastModifiedDate) {
            self::_log('%s: Up to date', __METHOD__);
            return false;
        }

        if (self::$_metadata['inProgressDate'] >= $lastModifiedDate) {
            self::_log('%s: Already in progress for %d', __METHOD__, self::$_metadata['inProgressDate']);
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

        $styleLastModifiedDate = self::_getLastModifiedDate($styleId);
        if ($styleLastModifiedDate === 0) {
            // do not proceed with invalid style id
            return '';
        }

        if ($styleLastModifiedDate - self::$_metadata['builtDate'] > 0) {
            self::_attemptRebuild();

            $dateDelta = XenForo_Application::$time - $styleLastModifiedDate;
            if ($dateDelta > self::$maxDateDelta) {
                // do not use the files if the rebuilder seems to be stale
                return '';
            }
        }

        return bdCloudServerHelper_XenForo_Template_FileHandler::getWithDate(self::$_metadata['builtDate'],
            $title, $styleId, $languageId);
    }

    protected static function _attemptRebuild()
    {
        if (empty(self::$_rebuildCmd)) {
            return false;
        }
        $rebuildCmd = self::$_rebuildCmd;
        self::$_rebuildCmd = '';

        self::_getLastModifiedDate(0);
        $lastModifiedDate = max(self::$_lastModifiedDates);

        if (self::$_metadata['builtDate'] >= $lastModifiedDate) {
            self::_log('%s: Up to date', __METHOD__);
            return false;
        }

        if (self::$_metadata['inProgressDate'] >= $lastModifiedDate) {
            self::_log('%s: Already in progress for %d', __METHOD__, self::$_metadata['inProgressDate']);
            return false;
        }

        if (self::$randomMax > 0 && self::$randomRange > 0) {
            // flip the coin to avoid more than one rebuild being triggered at once
            $random = rand(0, self::$randomMax);
            if ($random > self::$randomRange) {
                self::_log('%s: $random=%d', __METHOD__, $random);
                return false;
            }
        }

        self::_log('Start executing %s', $rebuildCmd);
        exec($rebuildCmd, $output, $returnVar);
        self::_log('Finished executing %s: %d, %s', $rebuildCmd, $returnVar, $output);

        return true;
    }

    protected static function _getLastModifiedDate($styleId)
    {
        if (self::$_lastModifiedDates === null) {
            self::$_lastModifiedDates = array();

            if (XenForo_Application::isRegistered('styles')) {
                $styles = XenForo_Application::get('styles');
                foreach ($styles as $style) {
                    self::$_lastModifiedDates[$style['style_id']] = $style['last_modified_date'];
                }
            }
        }

        if (isset(self::$_lastModifiedDates[$styleId])) {
            return self::$_lastModifiedDates[$styleId];
        } else {
            return 0;
        }
    }

    protected static function _readMetadata()
    {
        if (self::$_metadata !== null) {
            return false;
        }

        self::$_metadata = array(
            'inProgressDate' => 0,
            'builtDate' => 0,
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
        foreach ($args as &$argRef) {
            if (is_array($argRef)) {
                $argRef = var_export($argRef, true);
            } elseif (!is_string($argRef)) {
                $argRef = strval($argRef);
            }
        }

        $string = call_user_func_array('sprintf', $args);
        return XenForo_Helper_File::log(__CLASS__, $string);
    }
}