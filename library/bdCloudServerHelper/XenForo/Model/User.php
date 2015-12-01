<?php

class bdCloudServerHelper_XenForo_Model_User extends XFCP_bdCloudServerHelper_XenForo_Model_User
{
    public function prepareUserFetchOptions(array $fetchOptions)
    {
        $fetchLastActivity = false;

        if (bdCloudServerHelper_Option::get('redis', 'session_activity')
            && !empty($fetchOptions['join'])
            && $fetchOptions['join'] & XenForo_Model_User::FETCH_LAST_ACTIVITY
        ) {
            $fetchLastActivity = true;
            $fetchOptions['join'] = $fetchOptions['join'] ^ XenForo_Model_User::FETCH_LAST_ACTIVITY;
        }

        $response = parent::prepareUserFetchOptions($fetchOptions);

        if ($fetchLastActivity) {
            $response['selectFields'] .= ',
                user.last_activity AS effective_last_activity,
                NULL AS view_date,
                NULL AS controller_name,
                NULL AS controller_action,
                NULL AS params,
                NULL AS ip';
        }

        return $response;
    }


    public function updateSessionActivity($userId, $ip, $controllerName, $action, $viewState, array $inputParams, $viewDate = null, $robotKey = '')
    {
        $userId = intval($userId);

        if ($userId > 0
            && bdCloudServerHelper_Option::get('redis', 'session_activity')
        ) {
            if (!$viewDate) {
                $viewDate = XenForo_Application::$time;
            }

            bdCloudServerHelper_Helper_Redis::setCounter('session_activity', $userId, $viewDate);

            // prevent the default tracking mechanism
            return;
        }

        parent::updateSessionActivity($userId, $ip, $controllerName, $action, $viewState, $inputParams, $viewDate, $robotKey);
    }
}