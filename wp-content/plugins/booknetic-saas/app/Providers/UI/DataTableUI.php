<?php

namespace BookneticSaaS\Providers\UI;

use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticSaaS\Providers\Helpers\Helper;

class DataTableUI extends AbstractDataTableUI
{
    protected static $helper = Helper::class;
}