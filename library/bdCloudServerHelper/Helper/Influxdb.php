<?php

class bdCloudServerHelper_Helper_Influxdb
{
    public static function write($measurement, $value, array $tags = null, array $fields = null, $timestamp = null)
    {
        /** @var Zend_Config $config */
        $config = XenForo_Application::getConfig();
        $influxdbConfig = $config->get(bdCloudServerHelper_Listener::CONFIG_INFLUXDB);
        if (empty($influxdbConfig)) {
            XenForo_Error::logError('$config["bdCloudServerHelper_influxdb"] is missing');
            return false;
        }

        $influxdbAddress = $influxdbConfig->get('address');
        if (empty($influxdbAddress)) {
            XenForo_Error::logError('$config["bdCloudServerHelper_influxdb"]["address"] is missing');
            return false;
        }

        $influxdbDatabase = $influxdbConfig->get('database');
        if (empty($influxdbDatabase)) {
            XenForo_Error::logError('$config["bdCloudServerHelper_influxdb"]["database"] is missing');
            return false;
        }

        $username = $influxdbConfig->get('username');
        $password = $influxdbConfig->get('password');
        $url = sprintf('%s/write?db=%s', rtrim($influxdbAddress, '/'), $influxdbDatabase);

        $line = self::_escapeString($measurement);

        $tags = array_merge(array(
            'host' => $_POST['.hostname'],
        ), is_array($tags) ? $tags : array());
        ksort($tags);
        foreach ($tags as $tagKey => $tagValue) {
            $line .= sprintf(',%s=%s', self::_escapeString($tagKey), self::_escapeString($tagValue));
        }

        if (empty($line)) {
            XenForo_Error::logError('Measurement missing');
            return false;
        }
        $line .= ' ';

        if (!is_array($fields)) {
            $fields = array();
        }
        if ($value !== null) {
            $fields['value'] = $value;
        }
        $lineFields = array();
        foreach ($fields as $fieldKey => $fieldValue) {
            $lineFields[] = sprintf('%s=%s', self::_escapeString($fieldKey), self::_escapeValue($fieldValue));
        }
        if (empty($lineFields)) {
            XenForo_Error::logError('Fields missing');
            return false;
        }
        $line .= implode(',', $lineFields);

        if ($timestamp === null) {
            $timestamp = floor(microtime(true) * pow(10, 9));
        }
        $line .= sprintf(' %d', $timestamp);

        $client = XenForo_Helper_Http::getClient($url, array(
            'timeout' => 1,
        ));
        if (!empty($username) && !empty($password)) {
            $client->setAuth($username, $password);
        }

        $client->setRawData($line);

        $success = false;
        $responseStatus = 0;
        try {
            $response = $client->request('POST');
            $responseStatus = $response->getStatus();
            $success = $responseStatus === 204;
        } catch (Zend_Exception $e) {
            // ignore error
        }

        if (XenForo_Application::debugMode()) {
            XenForo_Helper_File::log('bdCloudServerHelper', sprintf("%s() POST %s\n\t%s -> %d",
                __METHOD__, $url, $line, $responseStatus));
        }

        return $success;
    }

    protected static function _escapeString($string)
    {
        $mapping = array(
            '\\' => '\\\\',
            ',' => '\\,',
            ' ' => '\\ ',
            '=' => '\\=',
        );
        return str_replace(array_keys($mapping), array_values($mapping), $string);
    }

    protected static function _escapeValue($value)
    {
        if (is_int($value)) {
            return $value . 'i';
        } elseif (is_float($value)) {
            return sprintf('%f', $value);
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            $mapping = array(
                '\\' => '\\\\',
                '"' => '\\"',
            );
            return sprintf('"%s"', str_replace(array_keys($mapping), array_values($mapping), $value));
        }
    }
}