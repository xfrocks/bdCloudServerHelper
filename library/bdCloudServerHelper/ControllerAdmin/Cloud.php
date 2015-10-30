<?php

class bdCloudServerHelper_ControllerAdmin_Cloud extends XenForo_ControllerAdmin_Abstract
{
    public function actionStats()
    {
        $tz = new DateTimeZone(XenForo_Visitor::getInstance()->get('timezone'));

        if (!$start = $this->_input->filterSingle('start', XenForo_Input::DATE_TIME, array('timeZone' => $tz))) {
            $start = XenForo_Application::$time - 7200;
        }

        if (!$end = $this->_input->filterSingle('end', XenForo_Input::DATE_TIME, array('dayEnd' => true, 'timeZone' => $tz))) {
            $end = XenForo_Application::$time;
        }

        $grouping = $this->_input->filterSingle('grouping', XenForo_Input::STRING, array(
            'default' => 'minutely',
        ));

        $viewParams = $this->getStatsData($start, $end, $grouping);

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

    public function getStatsData($start, $end, $grouping)
    {
        $statsModel = $this->_getStatsModel();
        $statsTypes = $statsModel->getStatsTypesSimple();

        $statsData = $statsModel->getStatsData($start, $end, $statsTypes, $grouping);
        $dateMap = array();

        $plots = array();
        foreach ($statsTypes as $type) {
            if (isset($statsData[$type])) {
                $plots[$type] = $statsData[$type];
            }
        }

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
        $plots = $statsModel->preparePageTimeAvg($output['plots']);
        $dateMap = $output['dateMap'];

        $viewParams = array(
            'plots' => $plots,
            'dateMap' => $dateMap,
            'start' => $start,
            'end' => $end,
            'endDisplay' => ($end >= XenForo_Application::$time ? 0 : $end),
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