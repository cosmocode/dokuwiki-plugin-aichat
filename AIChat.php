<?php

namespace dokuwiki\plugin\aichat;

/**
 * AIChat constants
 */
class AIChat
{
    /** @var int preferUIlanguage config: guess language use, all sources */
    const LANG_AUTO_ALL = 0;
    /** @var int preferUIlanguage config: use UI language, all sources */
    const LANG_UI_ALL = 1;
    /** @var int preferUIlanguage config: use UI language, limit sources */
    const LANG_UI_LIMITED = 2;
}