<?php

class bdCloudServerHelper_Helper_Redis
{
    protected static $_keyPrefix = 'xf_';

    /**
     * @return Redis|false
     */
    public static function getConnection()
    {
        static $redis = null;

        if ($redis === null) {
            $redis = false;

            /** @var Zend_Config $config */
            $config = XenForo_Application::getConfig();
            $redisConfig = $config->get(bdCloudServerHelper_Listener::CONFIG_REDIS);
            if (empty($redisConfig)) {
                XenForo_Error::logError('$config["bdCloudServerHelper_redis"] is missing');
                return $redis;
            }

            $socket = $redisConfig->get('socket');
            $host = $redisConfig->get('host');
            if (empty($socket) && empty($host)) {
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

            $_redis = null;
            try {
                $_redis = new Redis();

                if (!empty($socket)) {
                    $connected = $_redis->connect($socket);
                } else {
                    $connected = $_redis->connect($host, $port, $timeout);
                }

                if (!$connected) {
                    XenForo_Error::logError(sprintf('Cannot connect to Redis %s:%d %f', $host, $port, $timeout));
                    return $redis;
                }
            } catch (Throwable $t) {
                XenForo_Error::logError(sprintf('Error connecting to Redis: %s', $t->getMessage()));
                return $redis;
            }

            $password = $redisConfig->get('password');
            if (!empty($password)
                && !$_redis->auth($password)
            ) {
                XenForo_Error::logError(sprintf('Cannot authenticate with Redis (password=%s)', $password));
                return $redis;
            }

            $keyPrefix = $redisConfig->get('key_prefix');
            if (!empty($keyPrefix)) {
                self::$_keyPrefix = $keyPrefix;
            }

            // for HSCAN usage
            // https://github.com/phpredis/phpredis
            $_redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

            $redis = $_redis;
        }


        return $redis;
    }

    public static function increaseCounter($type, $varName, $varValue = null, $isFloat = false)
    {
        $redis = self::getConnection();
        if ($redis === false) {
            return 0;
        }

        if ($varValue === null) {
            return $redis->hIncrBy(self::getHashKey($type), $varName, 1);
        } elseif ($isFloat === false) {
            return $redis->hIncrBy(self::getHashKey($type), $varName, $varValue);
        } else {
            return $redis->hIncrByFloat(self::getHashKey($type), $varName, $varValue);
        }
    }

    public static function setValue($type, $varName, $varValue)
    {
        $redis = self::getConnection();
        if ($redis === false) {
            return 0;
        }

        return $redis->hSet(self::getHashKey($type), $varName, $varValue);
    }

    public static function getValues($type)
    {
        $redis = self::getConnection();
        if ($redis === false) {
            return array();
        }

        return $redis->hGetAll(self::getHashKey($type));
    }

    /**
     * @param string $type
     * @param array|string $varNames
     * @return bool
     */
    public static function deleteValues($type, $varNames)
    {
        $params = array(self::getHashKey($type));

        if (is_array($varNames)) {
            if (count($varNames) == 0) {
                return false;
            }

            foreach ($varNames as $varName) {
                $params[] = $varName;
            }
        } else {
            $params[] = $varNames;
        }

        $redis = self::getConnection();
        if ($redis === false) {
            return false;
        }

        return call_user_func_array(array($redis, 'hDel'), $params) > 0;
    }

    public static function getHashKey($key)
    {
        return self::$_keyPrefix . $key;
    }
}