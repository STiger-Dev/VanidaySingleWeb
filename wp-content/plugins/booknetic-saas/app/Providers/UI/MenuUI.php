<?php

namespace BookneticSaaS\Providers\UI;

use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;
use BookneticSaaS\Providers\Core\Backend;

class MenuUI extends AbstractMenuUI
{
    protected static $items = [
        self::MENU_TYPE_LEFT    => [],
        self::MENU_TYPE_TOP_RIGHT     => [],
        self::MENU_TYPE_TOP_LEFT    => []
    ];
    protected static $lastItemPriority = 0;
    protected static $backend = Backend::class;
}