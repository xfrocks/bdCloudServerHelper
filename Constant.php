<?php

namespace Xfrocks\CloudServerHelper;

class Constant
{
    const ADD_ON_ID = 'Xfrocks/CloudServerHelper';

    const COMPILE_PHRASE_GROUP_SKIP_SIMPLE_CACHE = __CLASS__ . '_skipCompilePhraseGroup';
    const COMPILE_PHRASE_GROUP_TIMESTAMP_SIMPLE_CACHE_KEY = 'cpgt';
    const COMPILE_PHRASE_GROUP_TIMESTAMP_ABSTRACT_PATH = 'code-cache://phrase_groups/csh_lpgrt.txt';

    const RECOMPILE_TEMPLATE_TIMESTAMP_ABSTRACT_PATH = 'code-cache://templates/csh_ltrt.txt';

    const REBUILD_NAV_CACHE_SKIP_SIMPLE_CACHE = __CLASS__ . '_skipRebuildNavCache';
    const REBUILD_NAV_CACHE_TIMESTAMP_SIMPLE_CACHE_KEY = 'rnct';
}
