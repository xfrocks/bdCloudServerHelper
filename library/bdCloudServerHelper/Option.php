<?php

class bdCloudServerHelper_Option
{
    public static function get($key, $subKey = null)
    {
        $options = XenForo_Application::getOptions();

        return $options->get('bdcsh_' . $key, $subKey);
    }
}