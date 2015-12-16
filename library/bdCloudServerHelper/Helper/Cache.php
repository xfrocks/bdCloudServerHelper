<?php

class bdCloudServerHelper_Helper_Cache
{
    public static function getDbCompatibleInstance()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new _bdCloudServerHelper_Helper_Cache_Db(array(
                'username' => 'username',
                'password' => 'password',
                'dbname' => 'dbname',
            ));
        }

        return $instance;
    }
}

class _bdCloudServerHelper_Helper_Cache_Db extends Zend_Db_Adapter_Abstract
{
    /** @var Zend_Cache_Core */
    protected $_cache = null;

    protected $_lastInsertId = array();
    protected $_lastInsertTableName = null;

    public function insert($table, array $bind)
    {
        $this->_connect();

        $id = time();
        $cacheId = $this->_getCacheId($table, $id);

        $this->_cache->save(serialize($bind), $cacheId);
        $this->_lastInsertTableName = $table;
        $this->_lastInsertId[$table] = $id;
    }

    public function queryByTableAndId($table, $id)
    {
        $this->_connect();

        $cacheId = $this->_getCacheId($table, $id);
        $cacheValue = $this->_cache->load($cacheId);
        if (is_string($cacheValue)) {
            $cacheValue = unserialize($cacheValue);
        }

        return $cacheValue;
    }

    public function listTables()
    {
        return array();
    }

    public function describeTable($tableName, $schemaName = null)
    {
        return array();
    }

    protected function _connect()
    {
        $this->_cache = XenForo_Application::getCache();

        if (empty($this->_cache)) {
            throw new XenForo_Exception('No Cache Backend has been configured');
        }
    }

    public function isConnected()
    {
        return !empty($this->_cache);
    }

    public function closeConnection()
    {
        // intentionally left blank
    }

    public function prepare($sql)
    {
        return new _bdCloudServerHelper_Helper_Cache_Db_Statement($this, $sql);
    }

    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        if ($tableName === null) {
            $tableName = $this->_lastInsertTableName;
        }

        if (isset($this->_lastInsertId[$tableName])) {
            return $this->_lastInsertId[$tableName];
        } else {
            return 0;
        }
    }

    protected function _beginTransaction()
    {
        // intentionally left blank
    }

    protected function _commit()
    {
        // intentionally left blank
    }

    protected function _rollBack()
    {
        // intentionally left blank
    }

    public function setFetchMode($mode)
    {
        // intentionally left blank
    }

    public function limit($sql, $count, $offset = 0)
    {
        // intentionally left blank
    }

    public function supportsParameters($type)
    {
        return true;
    }

    public function getServerVersion()
    {
        return XenForo_Application::$versionId;
    }

    protected function _getCacheId($tableName, $autoIncId)
    {
        return sprintf('%s_%d_%s', $tableName, $autoIncId, XenForo_Application::getSession()->getSessionId());
    }
}

class _bdCloudServerHelper_Helper_Cache_Db_Statement extends Zend_Db_Statement
{
    protected $_sql = '';
    protected $_results = array();

    protected function _prepare($sql)
    {
        $this->_sql = $sql;
    }

    public function _execute(array $params = null)
    {
        if (!preg_match('/FROM\s+(?<table>[^\s]+)/i', $this->_sql, $matches)) {
            XenForo_Error::logException(new XenForo_Exception('Unable to extract table from ' . $this->_sql), false);
            return;
        }
        $tableName = $matches['table'];

        $sprintfParams = array(str_replace('?', '%s', $this->_sql));
        foreach ($params as $param) {
            $sprintfParams[] = $param;
        }
        $sql = call_user_func_array('sprintf', $sprintfParams);
        if (!preg_match('/WHERE.*\s(?<field>[^\s]+_id)\s*=\s*(?<id>[^\s]+)/i', $sql, $matches)) {
            XenForo_Error::logException(new XenForo_Exception('Unable to extract field id from ' . $sql), false);
            return;
        }
        $autoIncField = $matches['field'];
        $autoIncId = $matches['id'];

        /** @var _bdCloudServerHelper_Helper_Cache_Db $adapter */
        $adapter = $this->_adapter;
        $result = $adapter->queryByTableAndId($tableName, $autoIncId);

        if (!empty($result)) {
            $result[$autoIncField] = $autoIncId;
            $this->_results = array($result);
        }
    }

    public function closeCursor()
    {
        // intentionally left blank
    }

    public function columnCount()
    {
        if (!empty($this->_results)) {
            $row = reset($this->_results);
            return count($row);
        } else {
            return null;
        }
    }

    public function errorCode()
    {
        return '';
    }

    public function errorInfo()
    {
        return array();
    }

    public function fetch($style = null, $cursor = null, $offset = null)
    {
        return array_shift($this->_results);
    }

    public function nextRowset()
    {
        return false;
    }

    public function rowCount()
    {
        return 0;
    }
}