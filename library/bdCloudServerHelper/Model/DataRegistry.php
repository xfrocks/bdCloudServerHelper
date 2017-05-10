<?php

class bdCloudServerHelper_Model_DataRegistry extends XenForo_Model_DataRegistry
{
    public function rebuildCache()
    {
        $rebuilt = 0;
        $cache = $this->_getCache(true);
        if (!$cache) {
            return $rebuilt;
        }

        $items = $this->_getMultiFromDb(array(
            // XenForo_Dependencies_Abstract
            'options',
            'languages',
            'contentTypes',
            'codeEventListeners',
            'deferredRun',
            'simpleCache',
            'addOns',
            'defaultStyleProperties',
            'routeFiltersIn',
            'routeFiltersOut',

            // XenForo_Dependencies_Public
            'routesPublic',
            'nodeTypes',
            'bannedIps',
            'discouragedIps',
            'styles',
            'displayStyles',
            'userBanners',
            'smilies',
            'bbCode',
            'threadPrefixes',
            'userTitleLadder',
            'reportCounts',
            'moderationCounts',
            'userModerationCounts',
            'notices',
            'userFieldsInfo',
        ));

        foreach ($items AS $name => $data) {
            $cache->save($data, $this->_getCacheEntryName($name));
            $rebuilt++;
        }

        return $rebuilt;
    }
}