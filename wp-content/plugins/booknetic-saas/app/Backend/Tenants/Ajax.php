<?php

namespace BookneticSaaS\Backend\Tenants;

use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\TenantFormInputChoice;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Models\TenantCustomData;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\TenantFormInput;
use BookneticSaaS\Providers\UI\TabUI;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function add_new()
	{
		$tenantId = Helper::_post('id', '0', 'integer');

		if( $tenantId > 0 )
		{
			$tenantInfo = Tenant::get( $tenantId );
			if( !$tenantInfo )
			{
				return $this->response(false, bkntcsaas__('Tenant not found!'));
			}
		}
		else
		{
			$tenantInfo = [
				'id'            =>  null,
				'full_name'     =>  null,
				'user_id'       =>  null,
				'email'         =>  null,
				'domain'        =>  null,
				'plan_id'       =>  Plan::where('is_default', 1)->fetch()->id,
				'expires_in'    =>  Date::dateSQL('+' . Helper::getOption('trial_period', 30) . ' days')
			];

		}




		$custom_fields =  DB::DB()->get_results(
			DB::DB()->prepare('
				SELECT fi.type type, cd.id, cd.input_file_name, fi.label label, fi.help_text help_text, fi.options, fi.is_required, fi.id form_input_id, cd.input_value value 
				FROM ' . DB::table('tenant_form_inputs') . ' fi 
				LEFT JOIN ( SELECT * FROM ' . DB::table('tenant_custom_data') . ' WHERE tenant_id = %d ) cd
				ON fi.id = cd.form_input_id
				ORDER BY fi.order_number;', [ $tenantId ]
			),
			ARRAY_A
		);


		foreach ( $custom_fields AS $fKey => $formInput )
		{
			if( in_array( $formInput['type'], ['select', 'checkbox', 'radio'] ) )
			{
				$choicesList = TenantFormInputChoice::where('form_input_id', $formInput['form_input_id'])->orderBy('order_number')->fetchAll();

				$custom_fields[ $fKey ]['choices'] = [];

				foreach( $choicesList AS $choiceInf )
				{
					$custom_fields[ $fKey ]['choices'][] = [ (int)$choiceInf['id'], htmlspecialchars($choiceInf['title']) ];
				}
			}
		}

        TabUI::get( 'tenants_add' )
            ->item( 'details' )
            ->setTitle( bkntcsaas__( 'Tenant details' ) )
            ->addView( __DIR__ . '/view/tab/details.php' )
            ->setPriority( 1 );

        TabUI::get( 'tenants_add' )
            ->item( 'custom_fields' )
            ->setTitle( bkntcsaas__( 'Custom fields' ) )
            ->addView( __DIR__ . '/view/tab/custom_fields.php' )
            ->setPriority( 2 );

        return $this->modalView('add_new', [
			'id'		    =>	$tenantId,
			'tenant'	    =>	$tenantInfo,
			'users'		    =>	get_users( [ 'role__not_in' => ['administrator', 'booknetic_customer', 'booknetic_staff'] ] ),
			'plans'         =>  Plan::fetchAll(),
			'custom_fields' =>  $custom_fields
		]);
	}

	public function save_tenant()
	{
		$id						    = Helper::_post('id', '0', 'integer');
		$wp_user				    = Helper::_post('wp_user', '0', 'integer');
		$wp_user_use_existing	    = Helper::_post('wp_user_use_existing', 'yes', 'string', ['yes', 'no']);
		$wp_user_password		    = Helper::_post('wp_user_password', '', 'string');
		$full_name				    = Helper::_post('full_name', '', 'string');
		$email					    = Helper::_post('email', '', 'email');
		$domain					    = Helper::_post('domain', '', 'string');
		$expires_in					= Helper::_post('expires_in', '', 'string');
		$plan_id		            = Helper::_post('plan_id', '', 'int');
		$customFields				= Helper::_post('custom_fields', [], 'array');
		$save_custom_data	     	= Helper::_post('save_custom_data', '', 'string');

		if( empty($full_name) || empty($email) || empty($domain) || empty($plan_id) || empty( $expires_in ) || !Date::isValid( $expires_in ) )
		{
			return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
		}

		$isEdit = $id > 0;

		if( $isEdit )
		{
			$getOldInf = Tenant::get( $id );

			if( !$getOldInf )
			{
				return $this->response(false, bkntcsaas__('Tenant not found or permission denied!'));
			}
		}

		$checkDomain = Tenant::where('domain', $domain)->where('id', '<>', $id)->fetch();
		if( $checkDomain )
		{
			return $this->response(false, bkntcsaas__('Domain name must be unique!'));
		}

		if( $wp_user_use_existing == 'yes' )
		{
			if( !( $wp_user > 0 ) )
			{
				return $this->response( false, bkntcsaas__('Please select WordPress user!') );
			}

			if( !( $wp_user_inf = new \WP_User( $wp_user ) ) )
			{
				return $this->response( false, bkntcsaas__('Please select WordPress user!') );
			}

			$checkWpUser = Tenant::where('user_id', $wp_user)->where('id', '<>', $id)->fetch();
			if( $checkWpUser )
			{
				return $this->response( false, bkntcsaas__('One WordPress user can be assigned to only one tenant!') );
			}

			if(
				in_array( 'administrator', $wp_user_inf->roles ) ||
				in_array( 'booknetic_customer', $wp_user_inf->roles ) ||
				in_array( 'booknetic_staff', $wp_user_inf->roles )
				//in_array( 'booknetic_saas_tenant', $wp_user_inf->roles )
			)
			{
				return $this->response( false, bkntcsaas__('Please select WordPress user!') );
			}

			$wp_user_inf->add_role( 'booknetic_saas_tenant' );
		}
		else if( $wp_user_use_existing == 'no' )
		{
			if( !($isEdit && $getOldInf->user_id > 0) && empty( $wp_user_password ) )
			{
				return $this->response( false, bkntcsaas__('Please type the password of the WordPress user!') );
			}
			else if( (!$isEdit || $email != $getOldInf->email) && (email_exists( $email ) !== false || username_exists( $email ) !== false) )
			{
				return $this->response( false, bkntcsaas__('The WordPress user with the same email address already exists!') );
			}

			if( $isEdit && $getOldInf->user_id > 0 )
			{
				$wp_user = $getOldInf->user_id;
				$updateData = [];

				if( $email != $getOldInf->email )
				{
					$updateData['user_login'] = $email;
					$updateData['user_email'] = $email;
				}

				if( $full_name != $getOldInf->full_name )
				{
					$updateData['display_name'] = $getOldInf->full_name;
				}

				if( !empty( $wp_user_password ) )
				{
					$updateData['user_pass'] = $wp_user_password;
				}

				if( !empty( $updateData ) )
				{
					$updateData['ID'] = $getOldInf->user_id;
					$user_data = wp_update_user( $updateData );

					if( isset( $updateData['user_login'] ) )
					{
						DB::DB()->update( DB::DB()->users, ['user_login' => $email], ['ID' => $updateData['ID']] );
					}

					if( is_wp_error( $user_data ) )
					{
						return $this->response( false, bkntcsaas__('An error occurred when saving data!') );
					}
				}
			}
			else
			{
				$wp_user = wp_insert_user( [
					'user_login'	=>	$email,
					'user_email'	=>	$email,
					'display_name'	=>	$full_name,
					'role'			=>	'booknetic_saas_tenant',
					'user_pass'		=>	$wp_user_password
				] );

				if( is_wp_error( $wp_user ) )
				{
					return $this->response( false, bkntcsaas__('An error occurred when saving data!') );
				}
			}
		}

		$sqlData = [
			'user_id'		=>	$wp_user,
			'full_name'		=>	$full_name,
			'email'			=>	$email,
			'domain'		=>	$domain,
			'plan_id'	    =>	$plan_id,
			'expires_in'    =>  Date::dateSQL( $expires_in )
		];

		if( $id > 0 )
		{
			$sqlData['inserted_at'] = Date::dateTimeSQL();
			Tenant::where( 'id', $id )->update( $sqlData );
			$tenantId = $id;
		}
		else
		{
			Tenant::insert( $sqlData );

			$tenantId = DB::lastInsertedId();
			Tenant::createInitialData( $tenantId );
		}



		// SAVE CUSTOM FIELDS


		$customFiles = isset($_FILES['custom_fields']) ? $_FILES['custom_fields']['tmp_name'] : [];


		$getRequiredFilesFields = TenantFormInput::where('is_required', '1')->where('type', 'file')->fetchAll();


		foreach( $customFields AS $inputId => $customFieldValue )
		{
			if( !is_numeric( $inputId ) || !is_string( $customFieldValue ) )
			{
				return $this->response(false, bkntcsaas__('Please fill custom fields form correctly!'));
			}


			if( !( is_numeric($inputId) && $inputId > 0 && is_string( $customFieldValue ) ) )
			{
				return $this->response(false, bkntcsaas__('Please fill custom fields form correctly!'));
			}

			$customFieldInf = TenantFormInput::get( $inputId );

			if( !$customFieldInf )
			{
				return $this->response(false, bkntcsaas__('Selected custom field not found!'));
			}

			if( $customFieldInf['type'] == 'file' )
			{
				continue;
			}

			
		}

		foreach( $customFiles AS $inputId => $customFieldValue )
		{
			if( !is_numeric( $inputId ) )
			{
				return $this->response(false, bkntcsaas__('Please fill custom fields form correctly!'));
			}


			if( !( is_numeric($inputId) && $inputId > 0 && is_string( $customFieldValue ) ) )
			{
				return $this->response(false, bkntcsaas__('Please fill custom fields form correctly!'));
			}

			$customFieldInf = TenantFormInput::get( $inputId );

			if( !$customFieldInf || $customFieldInf['type'] != 'file' )
			{
				return $this->response(false, bkntcsaas__('Selected custom field not found!'));
			}

			$isRequired = (int)$customFieldInf['is_required'];
			$options = json_decode( $customFieldInf['options'], true );

			if( isset( $options['allowed_file_formats'] ) && !empty( $options['allowed_file_formats'] ) && is_string( $options['allowed_file_formats'] ) )
			{
				$allowedFileFormats = Helper::secureFileFormats( explode(',', str_replace(' ', '', $options['allowed_file_formats'])) );
			}
			else
			{
				$allowedFileFormats = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'zip', 'rar', 'csv'];
			}

			$customFileName = $_FILES['custom_fields']['name'][ $inputId ];
			$extension = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );

			if( !in_array( $extension, $allowedFileFormats ) )
			{
				return $this->response(false, bkntcsaas__('File extension is not allowed!'));
			}
			

		}

		$file_form_inputs = TenantFormInput::where('type', 'file')->fetchAll();

		$file_field_ids = [];

		foreach( $file_form_inputs as $inp)
		{
			$file_field_ids[] = $inp->id;
		}

		$save_custom_data_sql = !empty(trim($save_custom_data)) > 0 ? (' AND form_input_id NOT IN (' . $save_custom_data . ')') : '';

		DB::DB()->query( 'DELETE FROM ' . DB::table('tenant_custom_data') . ' WHERE tenant_id = ' . (int)$tenantId .  $save_custom_data_sql);

		foreach( $customFields AS $inputId => $customFieldValue )
		{
			if( !in_array($inputId, $file_field_ids ))
			{
				TenantCustomData::insert([
					'tenant_id'			=>	$tenantId,
					'form_input_id'		=>	$inputId,
					'input_value'		=>	$customFieldValue
				]);
			}
		}

		if( count( $customFiles ) > 0 )
		{
			foreach( $customFiles AS $inputId => $customFieldValue )
			{
				$customFileName = $_FILES['custom_fields']['name'][ $inputId ];
				$extension = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );
	
				$newFileName = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;
	
				$result01 = move_uploaded_file( $customFieldValue, Helper::uploadedFile( $newFileName, 'TenantCustomForms' ) );
	
				if( $result01 )
				{
	
					// TenantCustomData::where( 'form_input_id', (int)$inputId )
					// 				->where( 'tenant_id', (int)$tenantId )
					// 				->delete();
	
					TenantCustomData::insert([
						'tenant_id'		    =>  $tenantId,
						'form_input_id'		=>	$inputId,
						'input_value'		=>	$newFileName,
						'input_file_name'	=>	$customFileName
					]);
				}
	
			}
		}


		return $this->response(true );
	}

}
