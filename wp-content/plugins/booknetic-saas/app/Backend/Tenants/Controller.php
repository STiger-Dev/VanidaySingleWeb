<?php

namespace BookneticSaaS\Backend\Tenants;

use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\UI\DataTableUI;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\Tenant;

class Controller extends \BookneticApp\Providers\Core\Controller
{

    private static $tenantTables = [
        'data',
        'tenant_custom_data',
        'appearance',
        'appointments',
//        'coupons',
//        'giftcards',
        'customers',
//        'forms',
        'holidays',
//        'invoices',
        'locations',
//        'notifications',
//        'notification_tabs',
        'service_categories',
        'services',
        'special_days',
        'staff',
        'timesheet',
        'tenant_billing',
//        'taxes',
        'workflows',
        'workflow_logs',
    ];

	public function index()
	{
		$dataTable = new DataTableUI(
			Tenant::leftJoin('plan', 'name')
		);

        $dataTable->addAction('billing_history', bkntcsaas__('Payment history'));
        $dataTable->addAction('edit', bkntcsaas__('Edit'));
        $dataTable->addAction('delete', bkntcsaas__('Delete'), [static::class , '_delete'], DataTableUI::ACTION_FLAG_BULK_SINGLE );

		$dataTable->setTitle( bkntcsaas__('Tenants') );

		$dataTable->addNewBtn(bkntcsaas__('ADD TENANT'));

		$dataTable->searchBy(["full_name", 'email', 'domain']);

		$dataTable->addColumns(bkntcsaas__('ID'), 'id');
		$dataTable->addColumns(bkntcsaas__('DOMAIN'), 'domain');
		$dataTable->addColumns(bkntcsaas__('FULL NAME'), 'full_name');
		$dataTable->addColumns(bkntcsaas__('EMAIL'), 'email');
		$dataTable->addColumns(bkntcsaas__('PLAN'), 'plan_name');
		$dataTable->addColumns(bkntcsaas__('EXPIRES IN'), 'expires_in', ['type' => 'date']);
		$dataTable->addColumns(bkntcsaas__('STATUS'), function( $tenant )
		{
			if( !empty( $tenant['active_subscription'] ) )
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-success">'.bkntcsaas__('Subscribed').'</button>';
			}
			else
			{
				$statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">'.bkntcsaas__('Not subscribed').'</button>';
			}

			return $statusBtn;
		}, ['is_html' => true, 'order_by_field' => 'active_subscription']);

		$table = $dataTable->renderHTML();

		$this->view( 'index', [
			'table' => $table
		] );
	}

	public static function _delete( $ids )
	{
		foreach ( $ids AS $id )
		{
			$tenantInf = Tenant::get( $id );

			foreach ( self::$tenantTables as $table )
			{
				DB::DB()->delete( DB::table( $table ), [ 'tenant_id' => $id ] );
			}

			if( $tenantInf->user_id > 0 )
			{
				$userData = get_userdata( $tenantInf->user_id );
				if( $userData && $userData->roles == ['booknetic_saas_tenant'] )
				{
					require_once ABSPATH . 'wp-admin/includes/user.php';
					wp_delete_user( $tenantInf->user_id );
				}
			}

            Tenant::where('id', $id)->delete();
		}
	}

}
