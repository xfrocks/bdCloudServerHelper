<?php

namespace Xfrocks\CloudServerHelper\XF\Repository;

use Xfrocks\CloudServerHelper\Constant;

class Navigation extends XFCP_Navigation
{
    public function rebuildNavigationCache()
    {
        parent::rebuildNavigationCache();

        if (!defined(Constant::REBUILD_NAV_CACHE_SKIP_SIMPLE_CACHE)) {
            $addOnId = Constant::ADD_ON_ID;
            $key = Constant::REBUILD_NAV_CACHE_TIMESTAMP_SIMPLE_CACHE_KEY;
            $this->app()->simpleCache()->setValue($addOnId, $key, time());
        }
    }
}
