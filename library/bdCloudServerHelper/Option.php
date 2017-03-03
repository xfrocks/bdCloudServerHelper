<?php

class bdCloudServerHelper_Option
{
    public static function get($key, $subKey = null)
    {
        $options = XenForo_Application::getOptions();

        return $options->get('bdcsh_' . $key, $subKey);
    }

    public static function renderRedis(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $selected = $preparedOption['option_value'];
        $choices = array();

        foreach (array(
                     'attachment_view',
                     'image_proxy_view',
                     'ip_login',
                     'session_activity',
                     'thread_view',
                 ) as $choice) {
            // new XenForo_Phrase('bdcsh_redis_attachment_view')
            // new XenForo_Phrase('bdcsh_redis_image_proxy_view')
            // new XenForo_Phrase('bdcsh_redis_ip_login')
            // new XenForo_Phrase('bdcsh_redis_session_activity')
            // new XenForo_Phrase('bdcsh_redis_thread_view')
            $choices[] = array(
                'name' => htmlspecialchars($fieldPrefix . "[$preparedOption[option_id]][$choice]"),
                'label' => new XenForo_Phrase(sprintf('bdcsh_redis_%s', $choice)),
                'selected' => !empty($selected[$choice]),
            );
        }

        if (XenForo_Application::isRegistered('addOns')) {
            $addOns = XenForo_Application::get('addOns');
            if (isset($addOns['bdAd'])) {
                $choices[] = array(
                    'name' => htmlspecialchars($fieldPrefix . "[$preparedOption[option_id]][bdAd]"),
                    'label' => new XenForo_Phrase('bdcsh_redis_bdAd'),
                    'selected' => !empty($selected['bdAd']),
                );
            }
        }

        $preparedOption['formatParams'] = $choices;

        return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox',
            $view, $fieldPrefix, $preparedOption, $canEdit
        );
    }

    public static function renderInfluxdb(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $selected = $preparedOption['option_value'];
        $choices = array();

        foreach (array(
                     'cron',
                 ) as $choice) {
            // new XenForo_Phrase('bdcsh_influxdb_cron')
            $choices[] = array(
                'name' => htmlspecialchars($fieldPrefix . "[$preparedOption[option_id]][$choice]"),
                'label' => new XenForo_Phrase(sprintf('bdcsh_influxdb_%s', $choice)),
                'selected' => !empty($selected[$choice]),
            );
        }

        $preparedOption['formatParams'] = $choices;

        return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox',
            $view, $fieldPrefix, $preparedOption, $canEdit
        );
    }
}