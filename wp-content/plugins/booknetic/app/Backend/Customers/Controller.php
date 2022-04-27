<?php

namespace BookneticApp\Backend\Customers;

use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\UI\DataTableUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
		Capabilities::must( 'customers' );

        $lastAppDateSubQuery = AppointmentCustomer::innerJoin('appointment', ['date'], null, null, true)->where('customer_id', '=', DB::field('id', 'customers'))->orderBy('created_at desc')->limit(1);
		$dataTable = new DataTableUI( Customer::select('*')->selectSubQuery($lastAppDateSubQuery, 'last_appointment_date') );

		$dataTable->setTitle(bkntc__('Customers'));
		$dataTable->addNewBtn(bkntc__('ADD CUSTOMER'));
		$dataTable->activateExportBtn();
		$dataTable->activateImportBtn();

        $dataTable->addAction('info', bkntc__('Info'));
        $dataTable->addAction('edit', bkntc__('Edit'));
		$dataTable->addAction('delete', bkntc__('Delete'), [static::class , '_delete'], DataTableUI::ACTION_FLAG_BULK_SINGLE );

		$dataTable->searchBy(["CONCAT(first_name, ' ', last_name)", 'email', 'phone_number']);

		$dataTable->addColumns(bkntc__('ID'), 'id', [], true);
		$dataTable->addColumns(bkntc__('CUSTOMER NAME'), function( $customer )
		{
			return Helper::profileCard( $customer['first_name'] . ' ' . $customer['last_name'], $customer['profile_image'], $customer['email'], 'Customers' );
		}, ['is_html' => true, 'order_by_field' => "first_name,last_name"], true);

		$dataTable->addColumnsForExport(bkntc__('First name'), 'first_name');
		$dataTable->addColumnsForExport(bkntc__('Last name'), 'last_name');
		$dataTable->addColumnsForExport(bkntc__('Email'), 'email');

		$dataTable->addColumns(bkntc__('PHONE'), 'phone_number');
		$dataTable->addColumns(bkntc__('LAST APPOINTMENT'), 'last_appointment_date', ['type' => 'date'], true);
		// $dataTable->addColumns(bkntc__('GENDER'), 'gender');
        $dataTable->addColumns(bkntc__('GENDER'), function( $customer )
        {
            return bkntc__($customer['gender']);
        }, ['is_html' => true, 'order_by_field' => "gender"], true);
		$dataTable->addColumns(bkntc__('Date of birth'), 'birthdate', ['type' => 'date']);

		$dataTable->addColumnsForExport(bkntc__('Note'), 'notes');

		$table = $dataTable->renderHTML();

		$this->view( 'index', ['table' => $table] );
	}

	public static function _delete( $ids )
	{
        $deleteWpUser = Helper::_post('delete_wp_user', 1, 'int');
		Capabilities::must( 'customers_delete' );

		// check if appointment exist
		foreach ( $ids AS $id )
		{
			$checkAppointments = AppointmentCustomer::where('customer_id', $id)->fetch();
			if( $checkAppointments )
			{
				Helper::response(false, bkntc__('The Customer has been added some Appointments. Firstly remove them!'));
			}
		}

		foreach ( $ids AS $id )
		{
			$customerInf = Customer::get( $id );
			if( $customerInf->user_id > 0 )
			{
				$userData = get_userdata( $customerInf->user_id );
                $customerCountForWPUser = Customer::noTenant()->where('user_id', $customerInf->user_id)->select('count(*) as count', true)->fetch();

                if( $userData && $customerCountForWPUser['count'] == 1 && in_array( 'booknetic_customer', $userData->roles ) )
				{
					require_once ABSPATH.'wp-admin/includes/user.php';
                    if ( $deleteWpUser == 1 && count($userData->roles) == 1 )
                    {
                        wp_delete_user( $customerInf->user_id );
                    }
                    else
                    {
                        $userData->remove_role('booknetic_customer');
                    }
				}
			}
		}

        Customer::where('id', $ids)->delete();
	}

}
