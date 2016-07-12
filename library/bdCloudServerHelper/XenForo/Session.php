<?php

class bdCloudServerHelper_XenForo_Session extends XFCP_bdCloudServerHelper_XenForo_Session
{
    public function get($key)
    {
        if (bdCloudServerHelper_Listener::isReadOnly()
            && $this->_config['table'] === 'xf_session'
            && $key === 'user_id'
        ) {
            return 0;
        }

        return parent::get($key);
    }

}