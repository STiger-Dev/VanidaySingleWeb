<?php

namespace BookneticApp\Models;

use BookneticApp\Models\Customer;
use BookneticApp\Models\Appointment;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\Helpers\Helper;

/**
 * @property-read int $id
 * @property int $appointment_id
 * @property int $customer_id
 * @property string $status
 * @property string $status_name
 * @property int $number_of_customers
 * @property float $paid_amount
 * @property string $payment_method
 * @property float $payment_status
 * @property float $created_at
 * @property string $locale
 * @property string $client_timezone
 * @property string $recurring_id
 * @property string $recurring_payment_type
 */
class AppointmentCustomer extends Model
{

	public static $relations = [
		'appointment'   =>  [ Appointment::class, 'id', 'appointment_id' ],
		'customer'		=>	[ Customer::class, 'id', 'customer_id' ],
		'prices'        =>  [ AppointmentCustomerPrice::class, 'appointment_customer_id', 'id' ]
	];

	public static function getStatusNameAttribute( $appointmentCustomerInf )
	{
        $statuses = Helper::getAppointmentStatuses();

		if ( array_key_exists( $appointmentCustomerInf->status, $statuses ) )
        {
            return $statuses[$appointmentCustomerInf->status]['title'];
        }

        return $appointmentCustomerInf->status;
	}

    public static function getStatusColorAttribute( $appointmentCustomerInf )
    {
        $statuses = Helper::getAppointmentStatuses();

        if ( array_key_exists( $appointmentCustomerInf->status, $statuses ) )
        {
            return $statuses[$appointmentCustomerInf->status]['color'];
        }

        return '#000';
    }

}
