<?php

namespace Xfrocks\CloudServerHelper\Db\Mysqli;

class ReadOnlyAdapter extends \XF\Db\Mysqli\ReplicationAdapter
{
    public function forceToWriteServer($type = 'implicit')
    {
        return;
    }

    public function isWriteConnection($connection)
    {
        return $this->writeConnection === $connection;
    }

    protected function getStatementClass()
    {
        return 'Xfrocks\CloudServerHelper\Db\Mysqli\ReadOnlyStatement';
    }

    protected function makeConnection(array $config)
    {
        if (isset($config['host']) && $config['host'] === 'nada') {
            $connection = $this->getTypeConnection('read');
            return new _ReadOnlyAdapter_ConnectionWrapper($connection);
        }

        return parent::makeConnection($config);
    }
}

class _ReadOnlyAdapter_ConnectionWrapper
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->connection, $name], $arguments);
    }

    public function __get($name)
    {
        return $this->connection->$name;
    }
}
