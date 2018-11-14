<?php

namespace Xfrocks\CloudServerHelper\XF\Service\Template;

use XF\Entity\Template;
use Xfrocks\CloudServerHelper\Constant;

class Compile extends XFCP_Compile
{
    private static $_registeredShutdownFunction = false;

    public function recompile(Template $template)
    {
        if (self::$_registeredShutdownFunction === false) {
            self::$_registeredShutdownFunction = true;
            register_shutdown_function(function () {
                $abstractedPath = Constant::RECOMPILE_TEMPLATE_TIMESTAMP_ABSTRACT_PATH;
                $contents = time();
                \XF\Util\File::writeToAbstractedPath($abstractedPath, $contents);
            });
        }

        parent::recompile($template);
    }
}
