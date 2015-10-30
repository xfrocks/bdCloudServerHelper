<?php

class bdCloudServerHelper_Model_Stats extends XenForo_Model_Stats
{
    public function getStatsTypesSimple()
    {
        return array(
            'bdcsh_stats_success',
            'bdcsh_stats_4xx',
            'bdcsh_stats_error',
            'bdcsh_stats_pageTime',
        );
    }

    public function preparePageTimeAvg(array $plots)
    {
        if (!empty($plots['bdcsh_stats_pageTime'])) {
            foreach ($plots['bdcsh_stats_pageTime'] as $segment => &$valueRef) {
                $total = 0;
                if (isset($plots['bdcsh_stats_success'][$segment])) {
                    $total += $plots['bdcsh_stats_success'][$segment];
                }
                if (isset($plots['bdcsh_stats_4xx'][$segment])) {
                    $total += $plots['bdcsh_stats_4xx'][$segment];
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
        }

        return $plots;
    }

    public function prepareGraphData(array $data, $grouping = 'daily')
    {
        $plot = array();
        $dateMap = array();

        $keys = array_keys($data);

        $date = reset($keys);
        $maxDate = end($keys);

        switch ($grouping) {
            case 'daily':
                $dateFormat = 'd/m';
                $dateStep = 86400;
                break;
            case 'minutely':
                $dateFormat = 'H:i';
                $dateStep = 60;
                break;
            case 'hourly':
            default:
                $dateFormat = 'H:i';
                $dateStep = 3600;
        }

        // normalize the date values across plots
        $date /= $dateStep;
        $date = intval($date);
        $date *= $dateStep;

        while ($date <= $maxDate) {
            $dateMap[$date] = XenForo_Locale::date($date, $dateFormat);

            $nextDate = $date + $dateStep;
            $value = 0;
            foreach ($keys as $key) {
                if ($key >= $date
                    && $key < $nextDate
                ) {
                    $value += floatval($data[$key]);
                }
            }
            $plot[$date] = array($date, $value);

            $date = $nextDate;
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