<?php

class bdCloudServerHelper_XenForo_Model_Search extends XFCP_bdCloudServerHelper_XenForo_Model_Search
{
    protected $_bdCloudServerHelper_useCacheDb = 0;

    public function insertSearch(array $results, $searchType, $searchQuery, array $constraints, $order, $groupByDiscussion,
                                 array $userResults = array(), array $warnings = array(), $userId = null, $searchDate = null
    )
    {
        $useCacheDb = false;
        if (bdCloudServerHelper_Option::get('cache', 'search')) {
            $this->_bdCloudServerHelper_useCacheDb++;
            $useCacheDb = true;
        }

        $search = parent::insertSearch($results, $searchType, $searchQuery, $constraints, $order, $groupByDiscussion,
            $userResults, $warnings, $userId, $searchDate);

        if ($useCacheDb) {
            $this->_bdCloudServerHelper_useCacheDb--;
        }

        return $search;
    }

    public function getSearchById($searchId)
    {
        $useCacheDb = false;
        if ($searchId < XenForo_Application::$time + 86400
            && $searchId > XenForo_Application::$time - 86400
            && bdCloudServerHelper_Option::get('cache', 'search')
        ) {
            $this->_bdCloudServerHelper_useCacheDb++;
            $useCacheDb = true;
        }

        $search = parent::getSearchById($searchId);

        if ($useCacheDb) {
            $this->_bdCloudServerHelper_useCacheDb--;
        }

        return $search;
    }

    protected function _getDb()
    {
        if ($this->_bdCloudServerHelper_useCacheDb > 0) {
            return bdCloudServerHelper_Helper_Cache::getDbCompatibleInstance();
        }

        return parent::_getDb();
    }


}