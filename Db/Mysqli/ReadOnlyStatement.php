<?php

namespace Xfrocks\CloudServerHelper\Db\Mysqli;

class ReadOnlyStatement extends \XF\Db\Mysqli\Statement
{
    public function prepare()
    {
        if (!$this->statement) {
            /** @var ReadOnlyAdapter $adapter */
            $adapter = $this->adapter;
            $query = $this->query;
            $connection = $adapter->getConnectionForQuery($query);
            if ($adapter->isWriteConnection($connection)) {
                $this->replaceWriteQuery($query);
            }
        }

        return parent::prepare();
    }

    private function replaceWriteQuery($query)
    {
        $comment = '';

        if (\XF::$debugMode) {
            if (preg_match('/\s*EXPLAIN/', $query)) {
                // for debugging purposes, let EXPLAIN queries it go through
                return;
            }

            $comment = $query;
            $comment = str_replace("\n", "\n-- ", $comment);
        }

        $this->query = "SELECT * FROM xf_session_activity WHERE 1=2;\n\n-- $comment";
        $this->params = [];
    }
}
