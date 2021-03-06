<?php

class bdCloudServerHelper_Model_Stats extends XenForo_Model_Stats
{
    public function getStatsTypesSimple()
    {
        return array(
            'bdcsh_stats_pageTime',
            'bdcsh_stats_error',
            'bdcsh_stats_4xx',
            'bdcsh_stats_success',
        );
    }

    public function preparePageTimeAvg(array $plots)
    {
        if (!empty($plots['bdcsh_stats_pageTime'])) {
            $total = array();

            foreach ($plots as $type => $data) {
                if ($type === 'bdcsh_stats_pageTime') {
                    continue;
                }

                foreach ($data as $_data) {
                    if (!isset($total[$_data[0]])) {
                        $total[$_data[0]] = 0;
                    }
                    $total[$_data[0]] += $_data[1];
                }
            }

            foreach ($plots['bdcsh_stats_pageTime'] as &$_dataRef) {
                $avg = 0;

                if (isset($total[$_dataRef[0]])
                    && $total[$_dataRef[0]] > 0
                ) {
                    $avg = $_dataRef[1] / $total[$_dataRef[0]];
                    $avg /= bdCloudServerHelper_Helper_Stats::DAILY_STATS_MULTIPLIER_PAGE_TIME;
                }

                $_dataRef[1] = $avg;
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
                $dateStep = bdCloudServerHelper_Helper_Stats::STATS_BLOCK_IN_SECONDS;
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