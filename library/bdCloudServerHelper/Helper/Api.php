<?php

class bdCloudServerHelper_Helper_Api
{
    const GET_PHRASES_TITLES_LIMIT = 1000;

    public static function postPhrases($apiAddress, $languageCode, $phraseTitles = array(), $addOnId = '')
    {
        $requestParams = array('language' => $languageCode);
        if (!empty($phraseTitles)) {
            $requestParams['gzipped'] = base64_encode(gzencode(implode(',', $phraseTitles), 9));
        } elseif (!empty($addOnId)) {
            $requestParams['addon'] = $addOnId;
        }

        $response = self::_request('POST', $apiAddress, 'phrases', $requestParams);

        if (isset($response['phrases'])) {
            return $response['phrases'];
        } else {
            return array();
        }
    }

    protected static function _request($method, $apiAddress, $path, array $params = array())
    {
        try {
            if (Zend_Uri::check($path)) {
                $uri = $path;
            } else {
                $uri = call_user_func_array('sprintf', array(
                    '%s/index.php?%s',
                    rtrim($apiAddress, '/'),
                    $path,
                ));
            }
            $client = XenForo_Helper_Http::getClient($uri);

            if ($method === 'GET') {
                $client->setParameterGet($params);
            } else {
                $client->setParameterPost($params);
            }

            $response = $client->request($method);

            $body = $response->getBody();
            $json = @json_decode($body, true);

            if (!is_array($json)) {
                $json = array('_body' => $body);
            }

            $json['_headers'] = $response->getHeaders();
            $json['_responseStatus'] = $response->getStatus();

            return $json;
        } catch (Zend_Http_Client_Exception $e) {
            if (XenForo_Application::debugMode()) {
                XenForo_Error::logException($e, false);
            }
            return false;
        }
    }
}