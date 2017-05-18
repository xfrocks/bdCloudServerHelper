<?php

class bdCloudServerHelper_Helper_RedisValueSync
{
    protected static $_syncToDbBatch = 100;
    protected static $_syncToDbTimeout = 30;

    public static function attachmentView()
    {
        self::_start(
            'attachment_view',
            function (Zend_Db_Adapter_Abstract $db, $varName, $value) {
                $db->query('
                    UPDATE xf_attachment
                    SET view_count = view_count + ?
                    WHERE attachment_id = ?
                ', array($value, $varName));
            }
        );
    }

    public static function bdAdClick()
    {
        self::_start(
            'bdad_click',
            function (Zend_Db_Adapter_Abstract $db, $varName, $value) {
                $db->query('
                    UPDATE xf_bdad_ad
                    SET click_count = click_count + ?
                    WHERE ad_id = ?
                ', array($value, $varName));
            }
        );
    }

    public static function bdAdView()
    {
        self::_start(
            'bdad_view',
            function (Zend_Db_Adapter_Abstract $db, $varName, $value) {
                $db->query('
                    UPDATE xf_bdad_ad
                    SET view_count = view_count + ?
                    WHERE ad_id = ?
                ', array($value, $varName));
            }
        );
    }

    public static function imageProxyView()
    {
        self::_start(
            'image_proxy_view',
            function (Zend_Db_Adapter_Abstract $db, $varName, $value) {
                $db->query('
                    UPDATE xf_image_proxy
                    SET views = views + ?
                    WHERE image_id = ?
                ', array($value, $varName));
            }
        );
    }

    public static function ipLogin()
    {
        self::_start(
            'ip_login',
            function (Zend_Db_Adapter_Abstract $db, $userId, $value) {
                $value = @unserialize($value);
                if (isset($value['ip'])
                    && isset($value['log_date'])
                    && $userId > 0
                ) {
                    $db->insert('xf_ip', array(
                        'user_id' => $userId,
                        'content_type' => 'user',
                        'content_id' => $userId,
                        'action' => 'login',
                        'ip' => $value['ip'],
                        'log_date' => $value['log_date'],
                    ));
                }
            }
        );
    }

    public static function sessionActivity()
    {
        self::_start(
            'session_activity',
            function (Zend_Db_Adapter_Abstract $db, $userId, $timestamp) {
                $db->update('xf_user',
                    array('last_activity' => $timestamp),
                    array(
                        'user_id = ?' => $userId,
                        'last_activity < ?' => $timestamp,
                    )
                );
            }
        );
    }

    public static function threadView()
    {
        self::_start(
            'thread_view',
            function (Zend_Db_Adapter_Abstract $db, $varName, $value) {
                $db->query('
                    UPDATE xf_thread
                    SET view_count = view_count + ?
                    WHERE thread_id = ?
                ', array($value, $varName));
            }
        );
    }

    protected static function _start($type, $callback)
    {
        $redis = bdCloudServerHelper_Helper_Redis::getConnection();
        $db = XenForo_Application::getDb();

        $hashKey = bdCloudServerHelper_Helper_Redis::getHashKey($type);
        $iterator = null;
        $startTime = microtime(true);
        $maxTime = self::$_syncToDbTimeout;
        $i = 0;

        $echo = defined('DEFERRED_CMD');
        if ($echo) {
            echo(sprintf("[%s] Starting...\n", $type));
        }

        while (true) {
            $values = $redis->hScan($hashKey, $iterator, '', self::$_syncToDbBatch);
            if (empty($values)) {
                if ($echo && $i > 0) {
                    echo(sprintf("[%s#%d] No values found\n", $type, $i));
                }
                break;
            }

            $updatedVarNames = array();

            foreach ($values as $varName => $value) {
                try {
                    $result = call_user_func($callback, $db, $varName, $value);
                    if ($result === false) {
                        $maxTime = 0;
                        break;
                    }
                    $updatedVarNames[] = $varName;
                } catch (Zend_Db_Exception $e) {
                    XenForo_Error::logError($e->getMessage());

                    if ($echo) {
                        echo(sprintf("[%s#%d] Error %s", $type, $i, $e->getMessage()));
                    }

                    $maxTime = 0;
                    break;
                }
            }

            bdCloudServerHelper_Helper_Redis::deleteValues($type, $updatedVarNames);

            if ($echo) {
                echo(sprintf("[%s#%d] Updated %s\n", $type, $i, implode(' ', $updatedVarNames)));
            }

            $elapsedTime = microtime(true) - $startTime;
            if ($elapsedTime > $maxTime) {
                if ($echo) {
                    echo(sprintf("[%s#%d] Stopped because of elapsed %f > max %f\n",
                        $type, $i, $elapsedTime, $maxTime));
                }

                break;
            }

            $i++;
        }

        if ($echo && $i === 0) {
            echo(sprintf("[%s#%d] Done\n", $type, $i));
        }
    }
}