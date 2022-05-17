<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Models\Location;
use BookneticApp\Models\ServiceStaff;

trait ARDHelper
{

    public function getAvailableLocations()
    {
        $appointmentObj = $this;

        $locations      = Location::where('is_active', 1)->orderBy('id');

        if( $appointmentObj->staffId > 0 )
        {
            $locationsFilter = empty( $appointmentObj->staffInf->locations ) ? [0] : explode( ',', $appointmentObj->staffInf->locations );
            $locations->where('id', $locationsFilter);
        }
        else if( $appointmentObj->serviceId > 0 )
        {
            $locationsFilter    = [];
            $staffList          = ServiceStaff::where('service_id', $appointmentObj->serviceId)->leftJoin( 'staff', ['locations'] )->fetchAll();

            foreach ( $staffList AS $staffInf )
            {
                $locationsFilter = array_merge( $locationsFilter, explode(',', $staffInf->staff_locations) );
            }

            $locationsFilter = array_unique( $locationsFilter );
            $locationsFilter = empty( $locationsFilter ) ? [0] : $locationsFilter;

            $locations->where('id', $locationsFilter);
        }

        return $locations;
    }

}