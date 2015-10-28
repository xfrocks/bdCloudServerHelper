<?php

class bdCloudServerHelper_Helper_Redis
{
    /**
     * @return Redis
     */
    public static function getConnection()
    {
        $redis = null;

        if ($redis === null) {
            $redis = false;

            $config = XenForo_Application::getConfig();
            $redisConfig = $config->get('bdCloudServerHelper_redis');
            if (empty($redisConfig)) {
                XenForo_Error::logError('$config["bdCloudServerHelper_redis"] is missing');
                return $redis;
            }

            $host = $redisConfig->get('host');
            if (empty($host)) {
                XenForo_Error::logError('$config["bdCloudServerHelper_redis"]["host"] is missing');
                return $redis;
            }

            $port = $redisConfig->get('port');
            if (empty($port)) {
                $port = 6379;
            }

            $timeout = $redisConfig->get('timeout');
            if (empty($timeout)) {
                $timeout = 0.0;
            }

            $_redis = new Redis();
            if (!$_redis->pconnect($host, $port, $timeout)) {
                XenForo_Error::logError($_redis->getLastError());
                return $redis;
            }

            $password = $redisConfig->get('password');
            if (!empty($password)
                && !$_redis->auth($password)
            ) {
                XenForo_Error::logError($_redis->getLastError());
                return $redis;
            }

            $redis = $_redis;
        }


        return $redis;
    }

    public static function increaseCounter($type, $varName, $varValue = null)
    {
        $redis = self::getConnection();
        if ($redis === false) {
            return 0;
        }

        if ($varValue === null) {
            return $redis->hIncrBy(self::getHashKey($type), $varName, 1);
        } else {
            return $redis->hIncrBy(self::getHashKey($type), $varName, $varValue);
        }
    }

    public static function getCounters($type)
    {
        $redis = self::getConnection();
        if ($redis === false) {
            return array();
        }

        return $redis->hGetAll(self::getHashKey($type));
    }

    public static function clearCounter($type, $varName)
    {
        $redis = self::getConnection();
        if ($redis === false) {
            return array();
        }

        return $redis->hSet(self::getHashKey($type), $varName, 0) !== false;
    }

    public static function getHashKey($key)
    {
        return 'xf_' . $key;
    }
}