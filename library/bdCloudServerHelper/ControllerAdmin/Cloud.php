<?php

class bdCloudServerHelper_ControllerAdmin_Cloud extends XenForo_ControllerAdmin_Abstract
{
    public function actionStats()
    {
        $end = XenForo_Application::$time;
        $start = $end - 86400;
        $viewParams = $this->getStatsData($start, $end, 'hourly');

        $currentSegment = bdCloudServerHelper_Helper_Stats::getSegment();
        $currentStats = bdCloudServerHelper_Helper_Stats::compileStatsForSegment($currentSegment);

        $viewParams = array_merge($viewParams, array(
            'currentSegment' => $currentSegment,
            'currentStats' => $currentStats,
        ));

        return $this->responseView(
            'bdCloudServerHelper_ViewAdmin_Cloud_Stats',
            'bdcsh_cloud_stats',
            $viewParams
        );
    }

    public function getStatsData($start, $end, $grouping = 'hourly')
    {
        $statsTypes = array(
            'bdcsh_stats_success',
            'bdcsh_stats_error',
            'bdcsh_stats_pageTime',
        );

        $statsModel = $this->_getStatsModel();

        $plots = $statsModel->getStatsData($start, $end, $statsTypes, $grouping);
        $plots = $statsModel->preparePageTimeAvg($plots);
        $dateMap = array();

        foreach ($plots AS $type => $plot) {
            $output = $statsModel->prepareGraphData($plot, $grouping);

            $plots[$type] = $output['plot'];
            $dateMap[$type] = $output['dateMap'];
        }

        $graphMinDate = XenForo_Application::$time;
        $graphMaxDate = 0;
        foreach ($dateMap as $type => $dates) {
            $graphMinDate = min($graphMinDate, min(array_keys($dates)));
            $graphMaxDate = max($graphMaxDate, max(array_keys($dates)));
        }

        $output = $statsModel->filterGraphDataDates($plots, $dateMap);
        $plots = $output['plots'];
        $dateMap = $output['dateMap'];

        $viewParams = array(
            'plots' => $plots,
            'dateMap' => $dateMap,
            'start' => $start,
            'end' => $end,
            'endDisplay' => ($end >= XenForo_Application::$time ? 0 : $end),
            'statsTypeOptions' => $statsModel->getStatsTypeOptions($statsTypes),
            'statsTypePhrases' => $statsModel->getStatsTypePhrases($statsTypes),
            'datePresets' => XenForo_Helper_Date::getDatePresets(),
            'grouping' => $grouping,

            'graphMinDate' => $graphMinDate,
            'graphMaxDate' => $graphMaxDate,
        );

        return $viewParams;
    }

    /**
     * @return bdCloudServerHelper_Model_Stats
     */
    protected function _getStatsModel()
    {
        return $this->getModelFromCache('bdCloudServerHelper_Model_Stats');
    }
}