<?php

class bdCloudServerHelper_Helper_Api
{
    const GET_PHRASES_TITLES_LIMIT = 1000;

    public static function postPhrases($apiAddress, $languageCode, array $phraseTitles = array(), $addOnId = '')
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

    public static function postPhrasesSuggestions($apiAddress, $languageCode, array $suggestions)
    {
        $requestParams = array('language' => $languageCode);
        $requestParams['gzipped'] = base64_encode(gzencode(json_encode($suggestions), 9));

        $response = self::_request('POST', $apiAddress, 'phrases/suggestions', $requestParams);

        return intval($response['_responseStatus']) == 202;
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
                $json = array('_jsonDecodeError' => true);
            }

            $json['_body'] = $body;
            $json['_headers'] = $response->getHeaders();
            $json['_responseStatus'] = $response->getStatus();
        } catch (Zend_Http_Client_Exception $e) {
            $json = array(
                '_body' => $e->getMessage(),
                '_headers' => array(),
                '_responseStatus' => 503,
                '_exception' => $e
            );
        }

        if (XenForo_Application::debugMode()) {
            XenForo_Helper_File::log(__METHOD__, sprintf('%s %s %s %s -> %d %s',
                $method, $apiAddress, $path, var_export($params, true),
                $json['_responseStatus'], $json['_body']));
        }

        return $json;
    }
}