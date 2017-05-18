<?php

class bdCloudServerHelper_Helper_RedisValueSync
{
    protected static $_syncToDbBatch = 100;
    protected static $_syncToDbTimeout = 30;

    public static function attachmentView()
    {
        self::_start(
            'attachment_view',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                self::_increaseDbColumnByKey($db, 'xf_attachment', 'view_count', 'attachment_id', $values);
            }
        );
    }

    public static function bdAdClick()
    {
        self::_start(
            'bdad_click',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                self::_increaseDbColumnByKey($db, 'xf_bdad_ad', 'click_count', 'ad_id', $values);
            }
        );
    }

    public static function bdAdView()
    {
        self::_start(
            'bdad_view',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                self::_increaseDbColumnByKey($db, 'xf_bdad_ad', 'view_count', 'ad_id', $values);
            }
        );
    }

    public static function imageProxyView()
    {
        self::_start(
            'image_proxy_view',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                self::_increaseDbColumnByKey($db, 'xf_image_proxy', 'views', 'image_id', $values);
            }
        );
    }

    public static function ipLogin()
    {
        self::_start(
            'ip_login',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                $queryValues = array();
                foreach ($values as $userId => $value) {
                    $value = @unserialize($value);
                    if (isset($value['ip'])
                        && isset($value['log_date'])
                        && $userId > 0
                    ) {
                        $queryValues[] = sprintf('(%1$d, "user", %1$d, "login", %2$s, %3$d)',
                            $userId, $db->quote($value['ip']), $value['log_date']);
                    }
                }

                if (count($queryValues) === 0) {
                    return;
                }

                $db->query('
                    INSERT INTO xf_ip
                    (user_id, content_type, content_id, action, ip, log_date)
                    VALUES ' . implode(', ', $queryValues)
                );
            }
        );
    }

    public static function sessionActivity()
    {
        self::_start(
            'session_activity',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                foreach ($values as $userId => $timestamp) {
                    $db->update('xf_user',
                        array('last_activity' => $timestamp),
                        array(
                            'user_id = ?' => $userId,
                            'last_activity < ?' => $timestamp,
                        )
                    );
                }
            }
        );
    }

    public static function threadView()
    {
        self::_start(
            'thread_view',
            function (Zend_Db_Adapter_Abstract $db, $values) {
                self::_increaseDbColumnByKey($db, 'xf_thread', 'view_count', 'thread_id', $values);
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
                if ($echo && $i === 0) {
                    echo(sprintf("[%s#%d] No values found\n", $type, $i));
                }
                break;
            }

            try {
                $result = call_user_func($callback, $db, $values);
                if ($result === false) {
                    $maxTime = 0;
                }
            } catch (Zend_Db_Exception $e) {
                XenForo_Error::logError($e->getMessage());

                if ($echo) {
                    echo(sprintf("[%s#%d] Error %s", $type, $i, $e->getMessage()));
                }

                $maxTime = 0;
            }

            $updatedVarNames = array_keys($values);
            bdCloudServerHelper_Helper_Redis::deleteValues($type, $updatedVarNames);

            if (XenForo_Application::debugMode()) {
                XenForo_Helper_File::log(__CLASS__, sprintf("[%s#%d] Updated %s",
                    $type, $i, implode(' ', $updatedVarNames)));
            } elseif ($echo) {
                echo(sprintf("[%s#%d] Updated %d\n", $type, $i, count($updatedVarNames)));
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

        if ($echo) {
            echo(sprintf("[%s#%d] Done\n", $type, $i));
        }
    }

    protected static function _increaseDbColumnByKey(Zend_Db_Adapter_Abstract $db, $tbl, $col, $key, array $values)
    {
        if (count($values) === 0) {
            return;
        }

        $cases = array();
        foreach ($values as $keyValue => $colValue) {
            $colValue = intval($colValue);
            if ($colValue === 0) {
                continue;
            }

            $cases[] = sprintf('WHEN %s = %s THEN %s', $key, $db->quote($keyValue), $colValue);
        }

        $q = sprintf('UPDATE %1$s SET %2$s = %2$s + CASE %4$s END WHERE %3$s IN (%5$s)',
            $tbl, $col, $key, implode(' ', $cases), $db->quote(array_keys($values)));

        if (XenForo_Application::debugMode()) {
            XenForo_Helper_File::log(__CLASS__, $q);
        }

        $db->query($q);
    }
}