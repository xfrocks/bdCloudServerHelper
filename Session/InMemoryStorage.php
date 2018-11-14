<?php

namespace Xfrocks\CloudServerHelper\Session;

class InMemoryStorage implements \XF\Session\StorageInterface
{
    public function getSession($sessionId)
    {
        return false;
    }

    public function deleteSession($sessionId)
    {
        // no op
    }

    public function writeSession($sessionId, array $data, $lifetime, $existing)
    {
        // no op
    }

    public function deleteExpiredSessions()
    {
        // no op
    }
}
