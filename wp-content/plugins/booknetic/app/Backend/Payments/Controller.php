<?php

namespace BookneticApp\Backend\Payments;

use BookneticApp\Backend\Appointments\Helpers\AppointmentCustomerSmartObject;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\AppointmentCustomerPrice;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Math;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		Capabilities::must( 'payments' );

        $totalAmountQuery = AppointmentCustomerPrice::where('appointment_customer_id', DB::field('appointment_customers_id'))
            ->select('sum(price * negative_or_positive)', true);

		$appointments = Appointment::
				leftJoin('appointment_customers', ['id', 'status', 'payment_status', 'created_at', 'payment_method', 'paid_amount', 'customer_id'])
                ->leftJoin('customers', ['first_name', 'last_name', 'email', 'profile_image', 'phone_number'], DB::table('customers') . '.id', 'customer_id')
                ->leftJoin('staff', ['name', 'profile_image'])
                ->leftJoin('location', ['name'])
                ->leftJoin('service', ['name'])
                ->selectSubQuery($totalAmountQuery, 'total_amount')
                ->select("if(payment_status = 'paid', paid_amount, 0) as real_paid_amount");

		$dataTable = new DataTableUI( $appointments );

        $dataTable->addAction('info', bkntc__('Info'));

		$dataTable->setIdField( 'appointment_customers_id' )
			->activateExportBtn()
			->setTitle(bkntc__('Payments'))
			->searchBy([ AppointmentCustomer::getField('id'), Location::getField('name'), Service::getField('name'), Staff::getField('name'), Customer::getField('first_name'), Customer::getField('last_name'), Customer::getField('email'), Customer::getField('phone_number')]);

		$dataTable->addFilter( 'date', 'date', bkntc__('Date'), '=' );
		$dataTable->addFilter( 'service_id', 'select', bkntc__('Service'), '=', [ 'model' => new Service() ] );
		$dataTable->addFilter( AppointmentCustomer::getField("customer_id"), 'select', bkntc__('Customer'), '=', [
			'model'			    =>	Customer::my(),
			'name_field'	    =>	'CONCAT(`first_name`, \' \', last_name)'
		] );
		$dataTable->addFilter( 'staff_id', 'select', bkntc__('Staff'), '=', [ 'model' => new Staff() ] );
		$dataTable->addFilter( AppointmentCustomer::getField('payment_status'), 'select', bkntc__('Status'), '=', [
			'list'	=>	[
				'pending'		=>	bkntc__('Pending'),
				'paid'			=>	bkntc__('Paid'),
				'canceled'		=>	bkntc__('Canceled')
			]
		] );

		$dataTable->addColumns(bkntc__('ID'), 'appointment_customers_id');
		$dataTable->addColumns(bkntc__('APPOINTMENT DATE'), function( $appointment )
		{
			if( $appointment->duration >= 24 * 60 )
			{
				return Date::datee( $appointment['date'] );
			}

			return Date::dateTime( $appointment['date'] . ' ' . $appointment['start_time'] );
		}, ['order_by_field' => 'date, start_time']);
		$dataTable->addColumns(bkntc__('CUSTOMER'), function( $appointment )
		{
			return Helper::profileCard( $appointment['customers_first_name'] . ' ' . $appointment['customers_last_name'], $appointment['customers_profile_image'], $appointment['customers_email'], 'Customers' );
		}, ['is_html' => true, 'order_by_field' => 'customers_first_name, customers_last_name'], true);

		$dataTable->addColumnsForExport(bkntc__('Customer'), function( $appointment )
		{
			return $appointment['customers_first_name'] . ' ' . $appointment['customers_last_name'];
		});
		$dataTable->addColumnsForExport(bkntc__('Customer Email'), 'customers_email');
		$dataTable->addColumnsForExport(bkntc__('Customer Phone Number'), 'customers_phone_number');

		$dataTable->addColumns(bkntc__('STAFF'), 'staff_name');
		$dataTable->addColumns(bkntc__('SERVICE'), 'service_name');
		$dataTable->addColumns(bkntc__('METHOD'), function ( $appointment )
		{
			return Helper::paymentMethod( $appointment['appointment_customers_payment_method'] );
		}, ['order_by_field' => 'appointment_customers_payment_method', 'is_html' => true]);
		$dataTable->addColumns(bkntc__('TOTAL AMOUNT'), function( $appointment )
		{
			return Helper::price($appointment['total_amount']);
		});
		$dataTable->addColumns(bkntc__('PAID AMOUNT'), function( $appointment )
		{
			return Helper::price( $appointment['real_paid_amount'] );
		});
		$dataTable->addColumns(bkntc__('DUE AMOUNT'), function( $appointment )
		{
			return Helper::price( Math::sub($appointment['total_amount'], $appointment['real_paid_amount']) );
		});
		$dataTable->addColumns(bkntc__('STATUS'), function( $appointment )
		{
            $totalAmount = (float) $appointment['total_amount'];
            $paidAmount = (float) $appointment['real_paid_amount'];

			if( $appointment['appointment_customers_payment_status'] == 'pending' )
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-warning">'.bkntc__('Pending').'</button>';
			}
			else if( $appointment['appointment_customers_payment_status'] == 'paid' )
			{
                if ($paidAmount < $totalAmount)
                {
                    $statusBtn = '<button type="button" class="btn btn-xs btn-light-primary">'.bkntc__('Paid (deposit)').'</button>';
                }
                else
                {
                    $statusBtn = '<button type="button" class="btn btn-xs btn-light-success">'.bkntc__('Paid').'</button>';
                }
			}
			else
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">'.bkntc__('Canceled').'</button>';
			}

			return $statusBtn;
		}, ['is_html' => true, 'order_by_field' => 'appointment_customers_payment_status']);

		$table = $dataTable->renderHTML();

		$this->view( 'index', ['table' => $table] );
	}

}
