<?php

class bdCloudServerHelper_XenForo_Model_Cron extends XFCP_bdCloudServerHelper_XenForo_Model_Cron
{
    public function runEntry(array $entry)
    {
        $startTime = 0;
        if (bdCloudServerHelper_Option::get('influxdb', 'cron')) {
            $startTime = microtime(true);
        }

        parent::runEntry($entry);

        if ($startTime !== 0) {
            bdCloudServerHelper_Helper_Influxdb::write('cron', null, array(),
                array(
                    'task' => sprintf('%s::%s', $entry['cron_class'], $entry['cron_method']),
                    'elapsed' => microtime(true) - $startTime,
                )
            );
        }
    }

}