<?php

namespace BookneticSaaS\Providers\Core;

use BookneticApp\Providers\Core\Abstracts\AbstractRoute;

class Route extends AbstractRoute
{
	protected static $routesPOST = [];
    protected static $routesGET = [];
    protected static $globalMiddlewares = [];
    protected static $prefix = 'bkntcsaas_';
    protected static $backend = Backend::class;

}