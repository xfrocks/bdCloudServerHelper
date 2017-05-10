<?php

/**
 * Class bdCloudServerHelper_Cache_Frontend
 *
 * Probably best to only use this for front-end pages, add these lines to config.php
 *
 *  if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] === '/index.php') {
 *      require(dirname(__FILE__) . '/bdCloudServerHelper/Cache/Frontend.php');
 *      $cacheFrontend = new bdCloudServerHelper_Cache_Frontend(array('cache_id_prefix' => ...));
 *      $cacheFrontend->fileBackendTtl = 123;
 *      $config['cache']['frontend'] = $cacheFrontend;
 *  }
 *
 * It's possible to use bdCloudServerHelper_Deferred_DataRegistryOptionsDump
 * with $cache->idMappingToPhpPath['data_options'] for options overriding via file.
 *
 * @see bdCloudServerHelper_Deferred_DataRegistryOptionsDump
 */
class bdCloudServerHelper_Cache_Frontend extends Zend_Cache_Core
{
    public $fileBackendTtl = 60;
    public $idMappingToPhpPath = array();

    /** @var Zend_Cache_Backend_Interface */
    protected $_fileBackend;

    public function __construct($options = array())
    {
        parent::__construct($options);

        $cacheDir = XenForo_Helper_File::getInternalDataPath() . DIRECTORY_SEPARATOR . __CLASS__;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }
        $this->_fileBackend = Zend_Cache::_makeBackend('File', array(
            'cache_dir' => $cacheDir,
        ));
    }

    public function loadFromPhpPath(
        $id,
        /** @noinspection PhpUnusedParameterInspection */
        $doNotUnserialize = false
    ) {
        if (!isset($this->idMappingToPhpPath[$id])) {
            return false;
        }

        $path = $this->idMappingToPhpPath[$id];
        if (!file_exists($path)) {
            return false;
        }

        $data = false;
        /** @noinspection PhpIncludeInspection */
        require($path);

        if (XenForo_Application::debugMode()) {
            XenForo_Helper_File::log(__CLASS__, 'loadFromPhpPath for ' . $id);
        }

        return $data;
    }

    public function load($idOriginal, $doNotTestCacheValidity = false, $doNotUnserialize = false)
    {
        if (isset($this->idMappingToPhpPath[$idOriginal])) {
            return $this->loadFromPhpPath($idOriginal, $doNotUnserialize);
        }

        $idWithPrefix = $this->_id($idOriginal);
        self::_validateIdOrTag($idWithPrefix);
        $data = $this->_fileBackend->load($idWithPrefix, $doNotTestCacheValidity);
        $dataFromParent = false;

        if ($data === false) {
            $data = parent::load($idOriginal, $doNotTestCacheValidity, true);
            $dataFromParent = true;
        } elseif (XenForo_Application::debugMode()) {
            XenForo_Helper_File::log(__CLASS__, 'Skipped parent::load() for ' . $idOriginal);
        }

        if ($data === false) {
            return false;
        }

        if ($dataFromParent) {
            $fileBackendSaved = $this->_fileBackend->save($data, $idWithPrefix, array(), $this->fileBackendTtl);
            if (XenForo_Application::debugMode()) {
                XenForo_Helper_File::log(__CLASS__, sprintf('File Backend save: %s %d',
                    $idOriginal, $fileBackendSaved));
            }
        }

        if ((!$doNotUnserialize) && $this->_options['automatic_serialization']) {
            // we need to unserialize before sending the result
            return unserialize($data);
        }

        return $data;
    }

    public function save($data, $idOriginal = null, $tags = array(), $specificLifetime = false, $priority = 8)
    {
        if ($idOriginal !== null) {
            $idWithPrefix = $this->_id($idOriginal);
            self::_validateIdOrTag($idWithPrefix);
            $fileBackendRemoved = $this->_fileBackend->remove($idWithPrefix);
            if (XenForo_Application::debugMode()) {
                XenForo_Helper_File::log(__CLASS__, sprintf('File Backend remove: %s %d',
                    $idOriginal, $fileBackendRemoved));
            }
        }

        return parent::save($data, $idOriginal, $tags, $specificLifetime, $priority);
    }
}