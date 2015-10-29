<?php

class bdCloudServerHelper_XenForo_ViewAdmin_Log_ServerErrorView
    extends XFCP_bdCloudServerHelper_XenForo_ViewAdmin_Log_ServerErrorView
{
    public function prepareParams()
    {
        parent::prepareParams();

        if (!isset($this->_params['bdCloudServerHelper_hostname'])) {
            $this->_params['bdCloudServerHelper_hostname'] = '';

            if (!empty($this->_params['entry']['requestState']['_POST']['.hostname'])) {
                $this->_params['bdCloudServerHelper_hostname']
                    = $this->_params['entry']['requestState']['_POST']['.hostname'];
                unset($this->_params['entry']['requestState']['_POST']['.hostname']);
            }
        }
    }

}