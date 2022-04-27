<?php

namespace BookneticApp\Backend\Base;

use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Session;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function switch_language()
	{
		if( !Helper::isSaaSVersion() )
		{
			return $this->response( false );
		}

		$language = Helper::_post('language', '', 'string');

		if( LocalizationService::isLngCorrect( $language ) )
		{
			Session::set('active_language', $language);
		}

		return $this->response( true );
	}


	public function ping()
	{
		return $this->response( true );
	}

    public function direct_link()
    {
        $service_id     = Helper::_post('service_id' ,0 ,'int');
        $staff_id       = Helper::_post('staff_id' ,0 ,'int');
        $location_id    = Helper::_post('location_id' ,0 ,'int');
        $services   = Service::fetchAll();
        $staff      = Staff::fetchAll();
        $locations  = Location::fetchAll();

        return $this->modalView('direct_link' , compact('services' , 'staff' , 'locations' ,'service_id' ,'staff_id' ,'location_id'));
    }

}
