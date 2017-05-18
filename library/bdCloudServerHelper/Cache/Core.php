<?php

class bdCloudServerHelper_Cache_Core extends Zend_Cache_Core
{
    public function save($data, $id = null, $tags = array(), $specificLifetime = false, $priority = 8)
    {
        if ($specificLifetime !== false) {
            // limit the maximum lifetime to be twice the default one
            $defaultLifetime = $this->getOption('lifetime');
            $cappedLifetime = min(2 * $defaultLifetime, $specificLifetime);
            if ($cappedLifetime < $specificLifetime
                && XenForo_Application::debugMode()
            ) {
                XenForo_Helper_File::log(__METHOD__, sprintf('$specificLifetime=%d requested by %s',
                    $specificLifetime, var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true)));
            }

            $specificLifetime = $cappedLifetime;
        }

        return parent::save($data, $id, $tags, $specificLifetime, $priority);
    }
}