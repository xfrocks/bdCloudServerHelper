<?php

class bdCloudServerHelper_XenForo_Model_Cron extends XFCP_bdCloudServerHelper_XenForo_Model_Cron
{
    public function runEntry(array $entry)
    {
        $measurement = null;
        $startTime = 0;
        if (bdCloudServerHelper_Option::get('influxdb', 'cron')) {
            $measurement = 'cron';
            $startTime = microtime(true);
        }

        parent::runEntry($entry);

        if ($measurement !== null) {
            bdCloudServerHelper_Helper_Influxdb::write(
                $measurement,
                null,
                array(
                    'host' => $_POST['.hostname'],
                ),
                array(
                    'task' => sprintf('%s::%s', $entry['cron_class'], $entry['cron_method']),
                    'elapsed' => microtime(true) - $startTime,
                )
            );
        }
    }

}