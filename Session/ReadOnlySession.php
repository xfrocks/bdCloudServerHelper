<?php

namespace Xfrocks\CloudServerHelper\Session;

class ReadOnlySession extends \XF\Session\Session
{
    public function __construct()
    {
        parent::__construct(new InMemoryStorage());
    }

    public function start($ownerIp, $sessionId = null)
    {
        $this->sessionId = \XF::generateRandomString($this->config['keyLength']);;
        $this->data = [
            '_ip' => $ownerIp,
            'isReadOnly' => true,
        ];
        $this->exists = true;

        $this->fromCookie = $this->sessionId;

        return $this;
    }
}
