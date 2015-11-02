<?php

class bdCloudServerHelper_ViewAdmin_Cloud_StatsLive extends XenForo_ViewAdmin_Base
{
    public function renderJson()
    {
        $data = array();

        foreach (array(
                     'currentSegment',
                     'currentStats',
                     'hostname',
                     'loadavg',
                 ) as $key) {
            if (isset($this->_params[$key])) {
                $data[$key] = $this->_params[$key];
            }
        }

        return $data;
    }
}