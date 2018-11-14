<?php

namespace Xfrocks\CloudServerHelper\XF\Service\Phrase;

use Xfrocks\CloudServerHelper\Constant;

class Group extends XFCP_Group
{
    private static $_registeredShutdownFunction = false;

    public function compilePhraseGroup($group)
    {
        if (self::$_registeredShutdownFunction === false) {
            self::$_registeredShutdownFunction = true;
            register_shutdown_function(function () {
                $addOnId = Constant::ADD_ON_ID;
                $key = Constant::COMPILE_PHRASE_GROUP_TIMESTAMP_SIMPLE_CACHE_KEY;
                $timestamp = time();

                if (!defined(Constant::COMPILE_PHRASE_GROUP_SKIP_SIMPLE_CACHE)) {
                    \XF::app()->simpleCache()->setValue($addOnId, $key, $timestamp);
                }

                $abstractedPath = Constant::COMPILE_PHRASE_GROUP_TIMESTAMP_ABSTRACT_PATH;
                \XF\Util\File::writeToAbstractedPath($abstractedPath, $timestamp);
            });
        }

        parent::compilePhraseGroup($group);
    }
}
