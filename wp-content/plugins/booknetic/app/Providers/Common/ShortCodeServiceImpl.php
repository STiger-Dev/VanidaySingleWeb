<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentCustomerSmartObject;
use BookneticApp\Models\AppointmentCustomer;
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
        if( ! empty( $data['appointment_customer_id'] ) )
        {
            $appointmentCustomerData = AppointmentCustomerSmartObject::load( $data['appointment_customer_id'] );

            $extraServices = AppointmentExtra::leftJoin( 'extra', [ 'name' ] )
                ->where('appointment_customer_id', $data['appointment_customer_id'] )
                ->fetchAll();

            $serviceExtraList = '';
            foreach ( $extraServices AS $extraInf )
            {
                $serviceExtraList .= $extraInf->extra_name . ( $extraInf->quantity > 1 ? ' x' . $extraInf->quantity : '' ) . ' - ' . Helper::price( $extraInf->price * $extraInf->quantity ) . '<br/>';
            }

            $addToGoogleCalendarURL = 'https://www.google.com/calendar/render?action=TEMPLATE&text='
                . urlencode( $appointmentCustomerData->getServiceInf()->name)
                . '&dates=' . ( Date::UTCDateTime($appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time, 'Ymd\THis\Z') . '/'
                    . Date::UTCDateTime($appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time, 'Ymd\THis\Z', '+' . ($appointmentCustomerData->getServiceInf()->duration + $appointmentCustomerData->getAppointmentInfo()->extras_duration) . ' minutes') )
                . '&details=&location=' . urlencode( $appointmentCustomerData->getLocationInf()->name ) . '&sprop=&sprop=name:';

            $arr = [
                '{appointment_id}'                          => $appointmentCustomerData->getInfo()->id,
                '{appointment_date}'                        => Date::datee( $appointmentCustomerData->getAppointmentInfo()->date ),
                '{appointment_date_time}'                   => Date::dateTime( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time ),
                '{appointment_start_time}'                  => Date::time( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time ),
                '{appointment_end_time}'                    => Date::time(Date::epoch( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time ) + $appointmentCustomerData->getAppointmentInfo()->duration * 60 +  $appointmentCustomerData->getAppointmentInfo()->extras_duration * 60 ),
                '{appointment_date_client}'                 => Date::datee( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time, false, true, $appointmentCustomerData->getInfo()->client_timezone ),
                '{appointment_date_time_client}'            => Date::dateTime( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time, false, true, $appointmentCustomerData->getInfo()->client_timezone ),
                '{appointment_start_time_client}'           => Date::time( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time, false, true, $appointmentCustomerData->getInfo()->client_timezone ),
                '{appointment_end_time_client}'             => Date::time(Date::epoch( $appointmentCustomerData->getAppointmentInfo()->date . ' ' . $appointmentCustomerData->getAppointmentInfo()->start_time ) + $appointmentCustomerData->getAppointmentInfo()->duration * 60 +  $appointmentCustomerData->getAppointmentInfo()->extras_duration * 60, false, true, $appointmentCustomerData->getInfo()->client_timezone ),
                '{appointment_duration}'                    => Helper::secFormat( $appointmentCustomerData->getAppointmentInfo()->duration * 60 + $appointmentCustomerData->getAppointmentInfo()->extras_duration * 60 ),
                '{appointment_buffer_before}'               => Helper::secFormat( $appointmentCustomerData->getAppointmentInfo()->buffer_before * 60 ),
                '{appointment_buffer_after}'                => Helper::secFormat( $appointmentCustomerData->getAppointmentInfo()->buffer_after * 60 ),
                '{appointment_status}'                      => $appointmentCustomerData->getInfo()->status_name,
                '{appointment_service_price}'               => Helper::price( $appointmentCustomerData->getPrice('service_price')->price ),
                '{appointment_extras_price}'                => Helper::price( $appointmentCustomerData->getPrice('service_extra')->price ),
                '{appointment_extras_list}'                 => $serviceExtraList,
                '{appointment_discount_price}'              => Helper::price( $appointmentCustomerData->getPrice('discount')->price ),
                '{appointment_sum_price}'                   => Helper::price( $appointmentCustomerData->getTotalAmount() ),
                '{appointments_total_price}'                => Helper::price( $appointmentCustomerData->getTotalAmount( true ) ),
                '{appointment_paid_price}'                  => Helper::price( $appointmentCustomerData->getInfo()->paid_amount ),
                '{appointment_payment_method}'              => Helper::paymentMethod( $appointmentCustomerData->getInfo()->payment_method ),
                '{appointment_created_date}'                => Date::datee( $appointmentCustomerData->getInfo()->created_at ),
                '{appointment_created_time}'                => Date::time($appointmentCustomerData->getInfo()->created_at ),
                '{appointment_created_date_client}'         => Date::datee( $appointmentCustomerData->getInfo()->created_at, false, true, $appointmentCustomerData->getInfo()->client_timezone ),
                '{appointment_created_time_client}'         => Date::time($appointmentCustomerData->getInfo()->created_at, false, true, $appointmentCustomerData->getInfo()->client_timezone ),
                '{add_to_google_calendar_link}'             => $addToGoogleCalendarURL,
                '{appointment_brought_people}'              => $appointmentCustomerData->getInfo()->number_of_customers
            ];

            $text = str_replace( array_keys($arr), array_values($arr), $text );

            $billingData = AppointmentCustomer::getData($data['appointment_customer_id'], 'customer_billing_data');
            if (!empty($billingData))
            {
                $billingData = json_decode($billingData, true);

                $arr = [
                    '{customer_full_name}'          => $billingData['customer_first_name'] . ' ' . $billingData['customer_last_name'],
                    '{customer_first_name}'         => $billingData['customer_first_name'],
                    '{customer_last_name}'          => $billingData['customer_last_name'],
                    '{customer_phone}'              => $billingData['customer_phone'],
                ];

                $text = str_replace(array_keys($arr), array_values($arr), $text);
            }
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
                '{location_notes}'              => $locationInf->notes
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