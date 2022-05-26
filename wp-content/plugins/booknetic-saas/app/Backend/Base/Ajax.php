<?php

namespace BookneticSaaS\Backend\Base;

use BookneticSaaS\Providers\Helpers\Helper;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function ping()
	{
		return $this->response( true );
	}

}
