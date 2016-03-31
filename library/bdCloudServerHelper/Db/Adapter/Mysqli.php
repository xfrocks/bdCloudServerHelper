<?php

// this line is required in config.php to use this adapter:
// $config['db']['adapterNamespace'] = 'bdCloudServerHelper_Db_Adapter';
class bdCloudServerHelper_Db_Adapter_Mysqli extends Zend_Db_Adapter_Mysqli
{
    protected function _connect()
    {
        if ($this->_connection) {
            return;
        }

        $hostsArray = null;
        $masterHost = null;

        if ($this->_config['host'] instanceof Zend_Config) {
            /** @noinspection PhpUndefinedMethodInspection */
            $hostsArray = $this->_config['host']->toArray();
        }
        if ($hostsArray !== null) {
            if (!empty($hostsArray['master'])) {
                $masterHost = $hostsArray['master'];
                unset($hostsArray['master']);
            } else {
                $hostArrayKeys = array_keys($hostsArray);
                $masterHostKey = reset($hostArrayKeys);
                $masterHost = $hostsArray[$masterHostKey];
                unset($hostsArray[$masterHostKey]);
            }
        }
        if ($masterHost !== null) {
            $this->_config['host'] = $masterHost;
        }

        if (!empty($hostsArray)
            && XenForo_Application::isRegistered('session')
            && XenForo_Application::getSession()->get('user_id') == 0
        ) {
            // session has been registered and reports some guest is browsing the site
            // this means some backend cache has been enabled and db is now being lazy-loaded
            // switch to use a random slave db host
            if (bdCloudServerHelper_Listener::isReadOnly()) {
                // read only mode: also consider the master host for random pick
                // because user cannot login anyway, we couldn't afford not using the master one
                $hostsArray[] = $masterHost;
            }
            shuffle($hostsArray);
            $this->_config['host'] = reset($hostsArray);
        }

        parent::_connect();

        if (!empty($this->_connection)) {
            $this->_connection->query('SET @@session.sql_mode=\'STRICT_ALL_TABLES\'');
        }
    }

    public function query($sql, $bind = array())
    {
        if (bdCloudServerHelper_Listener::isReadOnly()
            && preg_match('#(insert|update|replace)#i', $sql)
        ) {
            // basically do nothing
            // TODO: find a better NOP statement
            return parent::query('SET @a=1');
        }

        try {
            return parent::query($sql, $bind);
        } catch (Zend_Db_Statement_Mysqli_Exception $e) {
            if (strpos($e->getMessage(), 'try restarting transaction') !== false) {
                // https://www.percona.com/blog/2012/08/17/percona-xtradb-cluster-multi-node-writing-and-unexpected-deadlocks/
                // the server told us to restart, just do it then
                try {
                    usleep(500);
                    return parent::query($sql, $bind);
                } catch (Exception $anotherOne) {
                    throw new Exception('[RETRIED] ' . $anotherOne->getMessage(), 0, $e);
                }
            }

            throw $e;
        }
    }

}