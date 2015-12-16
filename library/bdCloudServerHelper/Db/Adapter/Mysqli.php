<?php

// this line is required in config.php to use this adapter:
// $config['db']['adapterNamespace'] = 'bdCloudServerHelper_Db_Adapter';
class bdCloudServerHelper_Db_Adapter_Mysqli extends Zend_Db_Adapter_Mysqli
{
    public function query($sql, $bind = array())
    {
        try {
            return parent::query($sql, $bind);
        } catch (Zend_Db_Statement_Mysqli_Exception $e) {
            if (strpos($e->getMessage(), 'try restarting transaction') !== false) {
                // https://www.percona.com/blog/2012/08/17/percona-xtradb-cluster-multi-node-writing-and-unexpected-deadlocks/
                // the server told us to restart, just do it then
                try {
                    return parent::query($sql, $bind);
                } catch (Zend_Db_Statement_Mysqli_Exception $anotherOne) {
                    throw new Exception($anotherOne->getMessage(), 0, $e);
                }
            }

            throw $e;
        }
    }

}