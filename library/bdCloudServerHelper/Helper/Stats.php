<?php

class bdCloudServerHelper_Helper_Stats
{
    const STATS_BLOCK_IN_SECONDS = 300;
    const DAILY_STATS_TYPE_PREFIX = 'bdcsh_stats_';
    const DAILY_STATS_MULTIPLIER_PAGE_TIME = 100000;

    public static function log(XenForo_FrontController $fc)
    {
        $success = 0;

        if (XenForo_Application::isRegistered('page_start_time')) {
            $segment = self::getSegment();
            $pageTime = microtime(true) - XenForo_Application::get('page_start_time');

            $responseType = 'success';
            if ($fc->getResponse()->getHttpResponseCode() != 200) {
                $responseType = 'error';
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
        self::_aggregate('error');
        self::_aggregate('pageTime', self::DAILY_STATS_MULTIPLIER_PAGE_TIME);
    }

    public static function compileStatsForSegment($segment)
    {
        $successCounters = bdCloudServerHelper_Helper_Redis::getCounters('stats_success');
        $errorCounters = bdCloudServerHelper_Helper_Redis::getCounters('stats_error');
        $pageTimes = bdCloudServerHelper_Helper_Redis::getCounters('stats_pageTime');

        $stats = array(
            'success' => 0,
            'error' => 0,
            'total' => 0,
            'pageTime_avg' => 0,
        );

        $total = 0;
        if (isset($successCounters[$segment])) {
            $stats['success'] = $successCounters[$segment];
            $total += $stats['success'];
        }
        if (isset($errorCounters[$segment])) {
            $stats['error'] = $errorCounters[$segment];
            $total += $stats['error'];
        }

        if ($total > 0) {
            $stats['total'] = $total;

            if (isset($pageTimes[$segment])) {
                $stats['pageTime_avg'] = $pageTimes[$segment] / $stats['total'];
            }
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
        $counts = bdCloudServerHelper_Helper_Redis::getCounters($counterType);
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