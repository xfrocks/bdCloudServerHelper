<?php

class bdCloudServerHelper_Model_Stats extends XenForo_Model_Stats
{
    public function preparePageTimeAvg(array $plots)
    {
        foreach ($plots['bdcsh_stats_pageTime'] as $segment => &$valueRef) {
            $total = 0;
            if (isset($plots['bdcsh_stats_success'][$segment])) {
                $total += $plots['bdcsh_stats_success'][$segment];
            }
            if (isset($plots['bdcsh_stats_error'][$segment])) {
                $total += $plots['bdcsh_stats_error'][$segment];
            }

            if ($total > 0) {
                $valueRef /= $total * bdCloudServerHelper_Helper_Stats::DAILY_STATS_MULTIPLIER_PAGE_TIME;
            } else {
                $valueRef = 0;
            }
        }

        return $plots;
    }

    public function prepareGraphData(array $data, $grouping = 'daily')
    {
        if ($grouping !== 'hourly'
            && $grouping !== 'minutely'
        ) {
            return parent::prepareGraphData($data, $grouping);
        }

        $plot = array();
        $dateMap = array();

        $keys = array_keys($data);

        $date = reset($keys);
        $maxDate = end($keys);

        // stats are generated based on UTC
        $utcTz = new DateTimeZone('UTC');

        switch ($grouping) {
            case 'minutely':
                $dateFormat = 'H:i';
                $dateStep = 60;
                break;
            case 'hourly':
            default:
                $dateFormat = 'H:i';
                $dateStep = 3600;
        }

        while ($date <= $maxDate) {
            $dateMap[$date] = XenForo_Locale::date($date, $dateFormat, null, $utcTz);

            $value = (isset($data[$date]) ? $data[$date] : 0);
            $plot[$date] = array($date, floatval($value));

            $date += $dateStep;
        }

        ksort($plot);
        return array(
            'plot' => array_values($plot),
            'dateMap' => $dateMap
        );
    }

    protected function _getStatsContentTypeHandlerNames()
    {
        return array(
            'bdCloudServerHelper_StatsHandler_Response',
        );
    }
}