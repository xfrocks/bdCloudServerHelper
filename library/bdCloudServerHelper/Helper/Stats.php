<?php

class bdCloudServerHelper_Helper_Stats
{
    const STATS_BLOCK_IN_SECONDS = 300;
    const DAILY_STATS_TYPE_PREFIX = 'bdcsh_stats_';
    const DAILY_STATS_MULTIPLIER_PAGE_TIME = 100000;

    public static function log(XenForo_FrontController $fc, $responseType = null)
    {
        $success = 0;

        if (XenForo_Application::isRegistered('page_start_time')) {
            $segment = self::getSegment();
            $pageTime = microtime(true) - XenForo_Application::get('page_start_time');

            if ($responseType === null) {
                $responseType = 'success';
                $httpResponseCode = $fc->getResponse()->getHttpResponseCode();
                if ($httpResponseCode != 200) {
                    if ($httpResponseCode >= 300
                        && $httpResponseCode < 400
                    ) {
                        // redirect? still success
                    } elseif ($httpResponseCode >= 400
                        && $httpResponseCode < 500
                    ) {
                        $responseType = '4xx';
                    } else {
                        $responseType = 'error';
                    }
                }
            }

            if (bdCloudServerHelper_Helper_Redis::increaseCounter('stats_' . $responseType, $segment) > 0) {
                $success++;
            }
            if (bdCloudServerHelper_Helper_Redis::increaseCounter('stats_pageTime', $segment, $pageTime, true) > 0) {
                $success++;
            }

            if (XenForo_Application::debugMode()) {
                $fc->getResponse()->setHeader('X-Stats-Segment', $segment);
                $fc->getResponse()->setHeader('X-Stats-ResponseType', $responseType);
                $fc->getResponse()->setHeader('X-Stats-Page-Time', $pageTime);
                $fc->getResponse()->setHeader('X-Stats-Success', $success);
            }
        }

        return $success;
    }

    public static function aggregate()
    {
        self::_aggregate('success');
        self::_aggregate('4xx');
        self::_aggregate('error');
        self::_aggregate('cache_hit');
        self::_aggregate('pageTime', self::DAILY_STATS_MULTIPLIER_PAGE_TIME);
    }

    public static function compileStatsForSegment($segment)
    {
        $successCounters = bdCloudServerHelper_Helper_Redis::getValues('stats_success');
        $_4xxCounters = bdCloudServerHelper_Helper_Redis::getValues('stats_4xx');
        $errorCounters = bdCloudServerHelper_Helper_Redis::getValues('stats_error');
        $cacheHitCounters = bdCloudServerHelper_Helper_Redis::getValues('stats_cache_hit');
        $pageTimes = bdCloudServerHelper_Helper_Redis::getValues('stats_pageTime');

        $stats = array(
            'success' => 0,
            '4xx' => 0,
            'error' => 0,
            'cache_hit' => 0,
            'total' => 0,
            'pageTime' => 0,
            'pageTime_avg' => 0,
        );

        if (isset($successCounters[$segment])) {
            $stats['success'] = intval($successCounters[$segment]);
            $stats['total'] += $stats['success'];
        }
        if (isset($_4xxCounters[$segment])) {
            $stats['4xx'] = intval($_4xxCounters[$segment]);
            $stats['total'] += $stats['4xx'];
        }
        if (isset($errorCounters[$segment])) {
            $stats['error'] = intval($errorCounters[$segment]);
            $stats['total'] += $stats['error'];
        }
        if (isset($cacheHitCounters[$segment])) {
            $stats['cache_hit'] = intval($cacheHitCounters[$segment]);
            $stats['total'] += $stats['cache_hit'];
        }
        if (isset($pageTimes[$segment])) {
            $stats['pageTime'] = floatval($pageTimes[$segment]);
        }

        if ($stats['total'] > 0
            && $stats['pageTime'] > 0
        ) {
            $stats['pageTime_avg'] = $stats['pageTime'] / $stats['total'];
        }

        return $stats;
    }

    public static function getSegment($timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = XenForo_Application::$time;
        }

        return floor($timestamp / self::STATS_BLOCK_IN_SECONDS) * self::STATS_BLOCK_IN_SECONDS;
    }

    protected static function _aggregate($type, $multiplier = 1)
    {
        $counterType = 'stats_' . $type;
        $counts = bdCloudServerHelper_Helper_Redis::getValues($counterType);
        $currentSegment = self::getSegment();
        $db = XenForo_Application::getDb();

        $segments = array();
        foreach ($counts as $segment => $count) {
            if ($segment + self::STATS_BLOCK_IN_SECONDS > $currentSegment) {
                continue;
            }

            $db->query(sprintf('
                INSERT INTO xf_stats_daily
                    (stats_date, stats_type, counter)
                VALUES
                    (%1$d, %2$s, %3$d)
                ON DUPLICATE KEY UPDATE
                    counter = counter + %3$d;
            ',
                $segment,
                $db->quote(self::DAILY_STATS_TYPE_PREFIX . $type),
                $count * $multiplier
            ));

            $segments[] = $segment;
        }

        bdCloudServerHelper_Helper_Redis::clearCounter($counterType, $segments);
    }
}