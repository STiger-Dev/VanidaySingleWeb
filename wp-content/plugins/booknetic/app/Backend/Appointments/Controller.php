<?php

namespace BookneticApp\Backend\Appointments;

use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		Capabilities::must( 'appointments' );

        $appointmentStatuses = Helper::getAppointmentStatuses();

        $statusOrders = implode(',' , array_map(function ($status){
            return "'" . $status['key'] . "'";
        } , $appointmentStatuses ));

		$customerSubQuery = AppointmentCustomer::leftJoin('customer', ['id', 'first_name', 'last_name', 'email', 'profile_image', 'phone_number'])
		                                       ->select("CONCAT(hex(first_name), ':', hex(last_name), ':', hex(email), ':', hex(IFNULL(profile_image, '')), ':', " . AppointmentCustomer::getField('id') . ")", true)
		                                       ->where(AppointmentCustomer::getField('appointment_id'), '=', DB::field(Appointment::getField('id')))
                                               ->orderBy("field(status, $statusOrders ) " )
		                                       ->limit(1);

		$appointments = Appointment::leftJoin('appointment_customers', ['id', 'status', 'created_at'])
		                           ->selectSubQuery($customerSubQuery, 'customer')
		                           ->leftJoin('customers', ['id', 'first_name', 'last_name', 'email', 'profile_image', 'phone_number'], DB::table('customers') . '.id', DB::table('appointment_customers') . '.customer_id')
		                           ->leftJoin('staff', ['name', 'profile_image'])
		                           ->leftJoin('location', ['name'])
		                           ->leftJoin('service', ['name'])
		                           ->select(['(select count(*) from ' . DB::table('appointment_customers') . ' where appointment_id = '.Appointment::getField('id').') as `customer_count`'])
                                    ->select(['(select sum(price * negative_or_positive) from '.DB::table('appointment_customer_prices').' where appointment_customer_id in (select id from '.DB::table('appointment_customers').' where appointment_id = ' . Appointment::getField('id') . " ) ) as `total_price`"])
		                           ->groupBy(Appointment::getField('id'));

		$dataTable = new DataTableUI( $appointments );

		$dataTable->activateExportBtn();

		$dataTable->addFilter( Appointment::getField('date'), 'date', bkntc__('Date'), '=' );
		$dataTable->addFilter( Service::getField('id'), 'select', bkntc__('Service'), '=', [ 'model' => new Service() ] );
		$dataTable->addFilter( Customer::getField('id'), 'select', bkntc__('Customer'), '=', [
			'model'			    =>	Customer::my(),
			'name_field'	    =>	'CONCAT(`first_name`, \' \', last_name)'
		] );



		$dataTable->addFilter( Staff::getField('id'), 'select', bkntc__('Staff'), '=', [ 'model' => new Staff() ] );

        $statusFilter = [];
        foreach ($appointmentStatuses as $k => $v)
        {
            $statusFilter[$k] = $v['title'];
        }
        $dataTable->addFilter( AppointmentCustomer::getField('status'), 'select', bkntc__('Status'), '=', [
			'list'	=>	$statusFilter
		], 1 );

        $dataTable->addFilter( 'if(timestamp(CONCAT('.Appointment::getField("date").', " ", '.Appointment::getField("start_time").')) >= "'.Date::dateTimeSQL().'", 1, 0)', 'select', bkntc__('Filter'), '=', [
            'list'	=>	[ 0=> 'Finished', 1 => 'Upcoming']
        ], 1 );

        ;

        $dataTable->addAction('info', bkntc__('Info'));
        $dataTable->addAction('edit', bkntc__('Edit'));
        $dataTable->addAction('delete', bkntc__('Delete'), [static::class , '_delete'], DataTableUI::ACTION_FLAG_SINGLE | DataTableUI::ACTION_FLAG_BULK);

		$dataTable->setTitle(bkntc__('Appointments'));
		$dataTable->addNewBtn(bkntc__('NEW APPOINTMENT'));

		$dataTable->searchBy([
			AppointmentCustomer::getField('id'),
			Location::getField('name'),
			Service::getField('name'),
			Staff::getField('name'),
			'CONCAT(' . Customer::getField('first_name') . ", ' ', " . Customer::getField('last_name') . ')',
			Customer::getField('email'),
			Customer::getField('phone_number')
		]);

		$dataTable->addColumns(bkntc__('ID'), function ($row)
        {
            return explode(':', $row['customer'])[4];
        });

		$dataTable->addColumns(bkntc__('DATE'), function( $appointment )
		{
			if( $appointment['duration'] >= 24 * 60 )
			{
				return Date::datee( $appointment['date'] );
			}
			else
			{
				return Date::dateTime( $appointment['date'] . ' ' . $appointment['start_time'] );
			}
		}, ['order_by_field' => 'date,start_time']);

		$dataTable->addColumns(bkntc__('CUSTOMER (S)'), function( $appointment ) use ($appointmentStatuses) {
			$customer_count = $appointment['customer_count'];
			if ($customer_count > 1)
			{
				$badge = '<button type="button" class="btn btn-xs btn-light-default more-customers"> ' . bkntc__('+ %d MORE', [ ($customer_count - 1) ]) . '</button>';
			}
			else
			{
                if (array_key_exists($appointment['appointment_customers_status'], $appointmentStatuses))
                {
                    $status = $appointmentStatuses[$appointment['appointment_customers_status']];
                    $badge = '<div class="appointment-status-icon ml-3" style="background-color: ' . htmlspecialchars( $status[ 'color' ] ) . '2b">
                                    <i style="color: ' . htmlspecialchars( $status[ 'color' ] ) . '" class="' . htmlspecialchars( $status[ 'icon' ] ) .  '"></i>
                                </div>';
//                    $badge = '<span class="badge badge-dark d-inline-flex align-items-center justify-content-center ml-3" style="background-color: "><i class=""></i></span>'; // ' <span class="appointment-status-' . htmlspecialchars( $appointment['appointment_customers_status'] ) .'"></span>';
                } else {
                    $badge = '<span class="badge badge-dark">' . $appointment['appointment_customers_status']  . '</span>';
                }
			}


			$customerBillingData = json_decode( AppointmentCustomer::getData($appointment['appointment_customers_id'], 'customer_billing_data') );

			$billingFirstName = ( empty($customerBillingData->customer_first_name) ? "" :  $customerBillingData->customer_first_name);
			$billingLastName = ( empty($customerBillingData->customer_last_name) ? "" :  $customerBillingData->customer_last_name);
			$billingPhone = ( empty($customerBillingData->customer_phone) ? "" :  $customerBillingData->customer_phone);


			if ( $billingFirstName != "" && $billingLastName != "" && $customer_count == 1)
			{
				$billingFullName = $billingFirstName . ' ' . $billingLastName;
				$badge .= '<div class="dropdown">';
				$badge .=   '<button type="button" class="btn btn-xs btn-dark-default ml-1" data-toggle="dropdown"> <i class="far fa-user-circle"></i> </button>';
				$badge .=   '<div class="dropdown-menu billing_names-popover">';
				$badge .=       '<h6>' . bkntc__('Billing info') . '</h6>';
				$badge .=       '<div class="billing_names-popover--cards">';
				$badge .=           "<div><h6>$billingFullName</h6><span>$billingPhone</span></div>";
				$badge .=       '</div>';
				$badge .=   '</div>';
				$badge .= '</div>';
			}

            if( substr_count($appointment['customer'],":") < 3 )
            {
                $appointment['customer'] = $appointment['customer'] . str_repeat(":" , 3 - substr_count($appointment['customer'] ,":") );
            }

			$customerColumns = explode(':', $appointment['customer']);
			$customerFullName = hex2bin($customerColumns[0]) . ' ' . hex2bin($customerColumns[1]);
			$customerEmail = hex2bin($customerColumns[2]);
			$customerProfileImage = hex2bin($customerColumns[3]);
			$customerHtml = Helper::profileCard( $customerFullName, $customerProfileImage, $customerEmail, 'Customers' ) . $badge;

			return '<div class="d-flex align-items-center justify-content-between">'.$customerHtml.'</div>';
		}, ['is_html' => true, 'order_by_field' => 'customer_count, customer'], true);

		$dataTable->addColumnsForExport(bkntc__('Customer'), function( $appointment )
		{
			return $appointment['customers_first_name'] . ' ' . $appointment['customers_last_name'];
		});

		$dataTable->addColumnsForExport(bkntc__('Customer Email'), 'customers_email');
		$dataTable->addColumnsForExport(bkntc__('Customer Phone Number'), 'customers_phone_number');

		$dataTable->addColumns(bkntc__('STAFF'), function($appointment)
		{
			return Helper::profileCard( $appointment['staff_name'], $appointment['staff_profile_image'], '', 'staff' );
		}, ['is_html' => true, 'order_by_field' => 'staff_name']);

		$dataTable->addColumns(bkntc__('SERVICE'), 'service_name');
		$dataTable->addColumns(bkntc__('PAYMENT'), function( $appointment )
		{
			$badge = ' <img class="invoice-icon" data-load-modal="payments.info" data-parameter-id="' . (int)$appointment['appointment_customers_id'] . '" src="' . Helper::icon('invoice.svg') . '"> ';
			if ($appointment['customer_count'] > 1)
			{
				$badge = ' <img class="invoice-icon" data-load-modal="appointments.group_payments_info" data-parameter-id="' . (int)$appointment['id'] . '" src="' . Helper::icon('invoice.svg') . '"> ';
			}
			return Helper::price( $appointment['total_price'] ) . $badge;
		}, ['is_html' => true]);

		$dataTable->addColumns(bkntc__('DURATION'), function( $appointment )
		{
			return Helper::secFormat( ((int)$appointment['duration'] + (int)$appointment['extras_duration']) * 60 );
		}, ['is_html' => true, 'order_by_field' => '( ' . Appointment::getField('duration') .  ' + extras_duration )']);

		$dataTable->addColumns(bkntc__('CREATED AT'), 'appointment_customers_created_at', ['type' => 'datetime']);

		$dataTable->setRowsPerPage(12);

		$table = $dataTable->renderHTML();

		$this->view( 'index', ['table' => $table] );
	}

	public static function _delete( $deleteIDs )
	{
		Capabilities::must( 'appointments_delete' );

		AppointmentService::deleteAppointment( $deleteIDs );

        /*doit add_action()*/
		do_action( 'bkntc_after_appointment_deleted', $deleteIDs );

		return false;
	}

}
