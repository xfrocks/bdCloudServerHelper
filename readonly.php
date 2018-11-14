<?php

function csh_updateConfigForReadOnly(array &$config)
{
    if (!isset($config['db']) || !is_array($config['db'])) {
        return false;
    }

    if (isset($config['db']['adapterClass']) &&
        $config['db']['adapterClass'] === 'XF\Db\Mysqli\ReplicationAdapter'
    ) {
        if (!isset($config['db']['read']) || !is_array($config['db']['read'])) {
            return false;
        }
        $dbConfig = $config['db']['read'];
    } else {
        $dbConfig = $config['db'];
    }

    if (!isset($dbConfig['username']) ||
        !isset($dbConfig['password']) ||
        !isset($dbConfig['dbname']) ||
        !isset($dbConfig['host'])) {
        return false;
    }

    $config['db'] = [
        'adapterClass' => 'Xfrocks\CloudServerHelper\Db\Mysqli\ReadOnlyAdapter',
        'read' => $dbConfig,
        'write' => ['host' => 'nada'],
    ];

    if (!isset($config['container'])) {
        $config['container'] = [];
    }
    $container =& $config['container'];

    $container['session.public'] = function ($c) {
        $session = new \Xfrocks\CloudServerHelper\Session\ReadOnlySession();
        return $session->start($c['request']);
    };

    define(\Xfrocks\CloudServerHelper\Constant::IS_READ_ONLY, true);

    return true;
}

if (isset($config) && is_array($config)) {
    csh_updateConfigForReadOnly($config);
}
