<?php

class bdCloudServerHelper_bdCache_Core extends XFCP_bdCloudServerHelper_bdCache_Core
{
    public function output(XenForo_FrontController &$fc, array &$cached)
    {
        if (bdCloudServerHelper_Option::get('redisStats')
            && $fc->getDependencies() instanceof XenForo_Dependencies_Public
        ) {
            bdCloudServerHelper_Helper_Stats::log($fc, 'cache_hit');
        }

        if (bdCloudServerHelper_Listener::$internalInfoHeader !== '') {
            $headerName = bdCloudServerHelper_Listener::$internalInfoHeader;
            $headerValuePrefix = '';

            if (!empty($cached[bdCache_Model_Cache::DATA_EXTRA_DATA][self::EXTRA_DATA_RESPONSE_HEADERS])) {
                $cachedHeadersRef =& $cached[bdCache_Model_Cache::DATA_EXTRA_DATA][self::EXTRA_DATA_RESPONSE_HEADERS];
                foreach (array_keys($cachedHeadersRef) as $i) {
                    $cachedHeaderRef =& $cachedHeadersRef[$i];
                    if ($cachedHeaderRef['name'] === $headerName) {
                        // Found existing header value cached earlier
                        // keep a copy of the value then remove it from cache data
                        // We will set it again later (see below)
                        $headerValuePrefix = $cachedHeaderRef['value'] . '/';
                        unset($cachedHeadersRef[$i]);
                    }
                }
            }

            bdCloudServerHelper_Helper_InternalInfo::setHeader($fc->getResponse(), $headerName, $headerValuePrefix);
        }

        parent::output($fc, $cached);
    }

}