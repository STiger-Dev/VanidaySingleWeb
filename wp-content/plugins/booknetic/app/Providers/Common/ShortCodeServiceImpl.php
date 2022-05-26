<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentSmartObject;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;

class ShortCodeServiceImpl
{

    public static function replace($text, $data)
    {
        if( ! empty( $data['appointment_id'] ) )
        {
            $appointmentSO = AppointmentSmartObject::load($data['appointment_id']);
            $appointmentInf = $appointmentSO->getInfo();

            $extraServices = AppointmentExtra::leftJoin( 'extra', [ 'name' ] )
                ->where('appointment_id', $appointmentInf->id )
                ->fetchAll();

            $serviceExtraList = '';
            foreach ( $extraServices AS $extraInf )
            {
                $serviceExtraList .= $extraInf->extra_name . ( $extraInf->quantity > 1 ? ' x' . $extraInf->quantity : '' ) . ' - ' . Helper::price( $extraInf->price * $extraInf->quantity ) . '<br/>';
            }

            $addToGoogleCalendarURL = 'https://www.google.com/calendar/render?action=TEMPLATE&text='
                . urlencode( $appointmentSO->getServiceInf()->name)
                . '&dates=' . ( Date::UTCDateTime($appointmentInf->starts_at, 'Ymd\THis\Z') . '/'
                    . Date::UTCDateTime($appointmentInf->ends_at, 'Ymd\THis\Z') )
                . '&details=&location=' . urlencode( $appointmentSO->getLocationInf()->name ) . '&sprop=&sprop=name:';

            $arr = [
                '{appointment_id}'                          => $appointmentInf->id,

                '{appointment_date}'                        => Date::datee( $appointmentInf->starts_at ),
                '{appointment_date_time}'                   => Date::dateTime( $appointmentInf->starts_at ),
                '{appointment_start_time}'                  => Date::time( $appointmentInf->starts_at ),
                '{appointment_end_time}'                    => Date::time( $appointmentInf->ends_at ),

                '{appointment_date_client}'                 => Date::datee( $appointmentInf->starts_at, false, true, $appointmentInf->client_timezone ),
                '{appointment_date_time_client}'            => Date::dateTime( $appointmentInf->starts_at, false, true, $appointmentInf->client_timezone ),
                '{appointment_start_time_client}'           => Date::time( $appointmentInf->starts_at, false, true, $appointmentInf->client_timezone ),
                '{appointment_end_time_client}'             => Date::time( $appointmentInf->ends_at, false, true, $appointmentInf->client_timezone ),

                '{appointment_duration}'                    => Helper::secFormat($appointmentInf->ends_at - $appointmentInf->starts_at),
                '{appointment_buffer_before}'               => Helper::secFormat($appointmentInf->starts_at - $appointmentInf->busy_from),
                '{appointment_buffer_after}'                => Helper::secFormat($appointmentInf->busy_to - $appointmentInf->ends_at),
                '{appointment_status}'                      => $appointmentInf->status_name,
                '{appointment_service_price}'               => Helper::price( $appointmentSO->getPrice('service_price')->price ),
                '{appointment_extras_price}'                => Helper::price( $appointmentSO->getPrice('service_extra')->price ),
                '{appointment_extras_list}'                 => $serviceExtraList,
                '{appointment_discount_price}'              => Helper::price( $appointmentSO->getPrice('discount')->price ),
                '{appointment_sum_price}'                   => Helper::price( $appointmentSO->getTotalAmount() ),
                '{appointments_total_price}'                => Helper::price( $appointmentSO->getTotalAmount(true) ),
                '{appointment_paid_price}'                  => Helper::price( $appointmentSO->getPaidAmount() ),
                '{appointment_payment_method}'              => Helper::paymentMethod( $appointmentSO->getInfo()->payment_method ),

                '{appointment_created_date}'                => Date::datee( $appointmentInf->created_at ),
                '{appointment_created_time}'                => Date::time( $appointmentInf->created_at ),
                '{appointment_created_date_client}'         => Date::datee( $appointmentInf->created_at ),
                '{appointment_created_time_client}'         => Date::time( $appointmentInf->created_at ),

                '{add_to_google_calendar_link}'             => $addToGoogleCalendarURL,

                '{appointment_brought_people}'              => $appointmentInf->weight,

                '{appointment_notes}'                       => $appointmentInf->note,
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        if( ! empty( $data['service_id'] ) )
        {
            $serviceInf = Service::leftJoin('category', ['name'])->get( $data[ 'service_id' ] );

            $arr = [
                '{service_name}'                    => $serviceInf->name,
                '{service_price}'                   => Helper::price( $serviceInf->price ),
                '{service_duration}'                => Helper::secFormat( $serviceInf->duration * 60 ),
                '{service_notes}'                   => $serviceInf->notes,
                '{service_color}'                   => $serviceInf->color,
                '{service_image_url}'               => Helper::profileImage( $serviceInf->image, 'Services' ),
                '{service_category_name}'           => $serviceInf->category_name,
            ];

            $text = str_replace( array_keys($arr), array_values($arr), $text );
        }

        if( ! empty( $data['staff_id'] ) )
        {
            $staffInf = Staff::get( $data[ 'staff_id' ] );

            $arr = [
                '{staff_name}'                      => $staffInf->name,
                '{staff_email}'                     => $staffInf->email,
                '{staff_phone}'                     => $staffInf->phone_number,
                '{staff_about}'                     => $staffInf->about,
                '{staff_profile_image_url}'         => Helper::profileImage( $staffInf->profile_image, 'Staff' )
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text );
        }

        if( ! empty( $data['customer_id'] ) )
        {
            $customerInf = Customer::get( $data[ 'customer_id' ] );

            $arr = [
                '{customer_full_name}'              => $customerInf->full_name,
                '{customer_first_name}'             => $customerInf->first_name,
                '{customer_last_name}'              => $customerInf->last_name,
                '{customer_phone}'                  => $customerInf->phone_number,
                '{customer_email}'                  => $customerInf->email,
                '{customer_birthday}'               => $customerInf->birthdate,
                '{customer_notes}'                  => $customerInf->notes,
                '{customer_profile_image_url}'      => Helper::profileImage( $customerInf->profile_image, 'Customers' )
            ];

            $text = str_replace(array_keys($arr), array_values($arr), $text);
        }

        if( ! empty( $data['customer_password'] ) )
        {
            $text = str_replace( '{customer_password}', $data['customer_password'], $text );
        }

        if( ! empty( $data['location_id'] ) )
        {
            $locationInf = Location::get( $data[ 'location_id' ] );

            $arr = [
                '{location_name}'               => $locationInf->name,
                '{location_address}'            => $locationInf->address,
                '{location_image_url}'          => Helper::profileImage( $locationInf->image, 'Locations' ),
                '{location_phone_number}'       => $locationInf->phone_number,
                '{location_notes}'              => $locationInf->notes,
                '{location_google_maps_url}'    => 'https://maps.google.com/?q=' . $locationInf->latitude . ',' . $locationInf->longitude
            ];

            $text = str_replace(array_keys($arr) , array_values($arr), $text );
        }

        $arr = [
            '{company_name}'            => Helper::getOption('company_name', ''),
            '{company_image_url}'       => Helper::profileImage( Helper::getOption('company_image', ''), 'Settings'),
            '{company_website}'         => Helper::getOption('company_website', ''),
            '{company_phone}'           => Helper::getOption('company_phone', ''),
            '{company_address}'         => Helper::getOption('company_address', ''),
        ];

        $text = str_replace(array_keys($arr), array_values($arr), $text );


        return $text;
    }

}