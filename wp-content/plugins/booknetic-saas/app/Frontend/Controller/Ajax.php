<?php

namespace BookneticSaaS\Frontend\Controller;

use BookneticSaaS\Models\TenantFormInput;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Frontend;
use BookneticSaaS\Models\TenantFormInputChoice;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Core\FrontendAjax;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Models\TenantCustomData;

class Ajax extends FrontendAjax
{

	public function signin()
	{
		$email      = Helper::_post('email', '', 'email');
		$password   = Helper::_post('password', '', 'string');

		if ( empty($email) || empty($password) )
		{
			return $this->response(false, bkntcsaas__('Please enter your email and password correctly!'));
		}

		$user = get_user_by('email', $email);

		if( !$user || !wp_check_password( $password, $user->data->user_pass, $user->ID ) )
		{
			return $this->response(false, bkntcsaas__('Email or password is incorrect!'));
		}

		if( in_array( 'booknetic_saas_tenant', $user->roles ) )
		{
			$tenantInfo = Tenant::where('email', $email)->fetch();
			if( !$tenantInfo || empty( $tenantInfo->domain ) )
			{
				return $this->response( false, bkntcsaas__('To access your account, you must first complete your registration. Please log in to your email to complete your registration.') );
			}
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		return $this->response( true, [
			'url'   => Helper::getURLOfUsersDashboard( $user )
		]);
	}

	public function signup()
	{
		$full_name		=	Helper::_post('full_name', '', 'string');
		$email			=	Helper::_post('email', '', 'email');
		$password		=	Helper::_post('password', '', 'string');

		if( empty( $full_name ) || empty( $email ) || empty( $password ) )
		{
			return $this->response( false, bkntcsaas__('Please fill in all required fields correctly!') );
		}

		$checkEmail = Tenant::where('email', $email)->fetch();
		if( $checkEmail )
		{
			return $this->response( false, bkntcsaas__('This email is already registered!') );
		}

		$tenantUserId = wp_insert_user( [
			'user_login'	=>	$email,
			'user_email'	=>	$email,
			'display_name'	=>	$full_name,
			'first_name'	=>	$full_name,
			'role'			=>	'booknetic_saas_tenant',
			'user_pass'		=>	$password
		]);

		if( is_wp_error( $tenantUserId ) )
		{
			return $this->response( false, $tenantUserId->get_error_message() );
		}

		$activation_token = md5( microtime(1) . uniqid() );

		$defaultPlan = Plan::where('is_default', 1)->fetch();
		$trialPeriod = Helper::getOption('trial_period', 30);

		Tenant::insert([
			'user_id'				        =>	$tenantUserId,
			'plan_id'				        =>	$defaultPlan->id,
			'expires_in'                    =>  Date::dateTimeSQL( '+' . $trialPeriod . ' days'),
			'email'					        =>	$email,
			'full_name'				        =>	$full_name,
			'inserted_at'			        =>	Date::dateTimeSQL(),
			'activation_last_sent_time'		=>	Date::format( 'Y-m-d H:i:s' ),
			'activation_token'		        =>	$activation_token
		]);

		$tenantId = Tenant::lastId();

        do_action( 'bkntcsaas_tenant_sign_up_confirm', $tenantId, $password );

		return $this->response( true );
	}

	public function resend_activation_link()
	{
		$email      = Helper::_post('email', '', 'email');
		$password	= Helper::_post('password', '', 'string');

		if( empty( $email ) )
		{
			return $this->response( false );
		}

		$checkEmail = Tenant::where('email', $email)
			->where('activation_token', 'is not', null)
			->where('activation_last_sent_time', '<=', Date::format( 'Y-m-d H:i:s', '-60 second'))
			->fetch();

		if( !$checkEmail )
		{
			return $this->response( false );
		}

		$tenantId = $checkEmail->id;

        do_action( 'bkntcsaas_tenant_sign_up_confirm', $tenantId, $password );

		Tenant::where( 'id', $tenantId )->update( [
			'activation_last_sent_time' => Date::format( 'Y-m-d H:i:s' )
		] );

		return $this->response( true );
	}

	public function complete_signup()
	{
		$token		=	Helper::_post('token', '', 'string');
		$domain		=	Helper::_post('domain', '', 'string');

		$tenantInfo = Tenant::where('activation_token', $token)->fetch();

		if( !$tenantInfo )
		{
			return $this->response( false, bkntcsaas__('Invalid request!') );
		}

		if( !(strlen( $domain ) >= 3 && strlen( $domain ) <= 35) )
		{
			return $this->response( false, bkntcsaas__('Minimum symbol length is 3 and maximum symbol length is 35.') );
		}

		if( !preg_match( '/^[A-Za-z0-9\-_]+$/', $domain ) )
		{
			return $this->response( false, bkntcsaas__('Allowed characters for domain name is [a-z A-Z 0-9 - _]') );
		}

		$checkDomainExist = Tenant::where('domain', $domain)->fetch();
		if( $checkDomainExist )
		{
			return $this->response( false, bkntcsaas__('This domain name is already taken. Please choose another one.') );
		}

		$checkIfTheSlugIsTaken = DB::DB()->get_row( DB::DB()->prepare('SELECT * FROM `'.DB::DB()->base_prefix.'posts` WHERE `post_name`=%s', [ $domain ]) );

		if( $checkIfTheSlugIsTaken )
		{
			return $this->response(false, bkntcsaas__('This domain name is already taken. Please choose another one.'));
		}

		Tenant::where('activation_token', $token )->update([ 'domain' => $domain ]);

		return $this->response( true );
	}

	public function complete_signup_company_details()
	{
		$token			= Helper::_post('token', '', 'string');
		$company_name	= Helper::_post('company_name', '', 'string');
		$address		= Helper::_post('address', '', 'string');
		$phone_number	= Helper::_post('phone_number', '', 'string');
		$website		= Helper::_post('website', '', 'string');
		$website		= Helper::_post('website', '', 'string');
		$custom_fields  = Helper::_post('custom_fields', [], 'arr');

		if( empty( $company_name ) )
		{
			return $this->response( false, bkntcsaas__('Please fill in all required fields correctly!') );
		}

		$tenantInfo = Tenant::where('activation_token', $token)->fetch();

		if( !$tenantInfo )
		{
			return $this->response( false, bkntcsaas__('Invalid request!') );
		}


		$customFiles = isset($_FILES['custom_fields']) ? $_FILES['custom_fields']['tmp_name'] : [];

		foreach ( $custom_fields AS $field_id => $value )
		{
			if( !( is_numeric($field_id) && $field_id > 0 && is_string( $value ) ) )
			{
				return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
			}

			$customFieldInf = TenantFormInput::get( $field_id );

			if( !$customFieldInf )
			{
				return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
			}

			if( $customFieldInf['type'] == 'file' )
			{
				continue;
			}

			$isRequired = (int)$customFieldInf['is_required'];

			if( $isRequired && empty( $value ) )
			{
				return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!' ));
			}

			$options = $customFieldInf['options'];
			$options = json_decode( $options, true );

			if( isset( $options['min_length'] ) && is_numeric( $options['min_length'] ) && $options['min_length'] > 0 && !empty( $value ) && mb_strlen( $value, 'UTF-8' ) < $options['min_length'] )
			{
				return $this->response(false, bkntcsaas__('Minimum length of "%s" field is %d!', [ $customFieldInf['label'], (int)$options['min_length'] ]));
			}

			if( isset( $options['max_length'] ) && is_numeric( $options['max_length'] ) && $options['max_length'] > 0 && mb_strlen( $value, 'UTF-8' ) > $options['max_length'] )
			{
				return $this->response(false, bkntcsaas__('Maximum length of "%s" field is %d!', [ $customFieldInf['label'], (int)$options['max_length'] ]));
			}

		}



		foreach( $customFiles AS $field_id => $value )
		{
			if( !( is_numeric($field_id) && $field_id > 0 && is_string( $value ) ) )
			{
				return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
			}

			$customFieldInf = TenantFormInput::get( $field_id );

			if( !$customFieldInf || $customFieldInf['type'] != 'file' )
			{
				return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!'));
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

			if( $isRequired && empty( $value ) )
			{
				return $this->response(false, bkntcsaas__('Please fill in all required fields correctly!' ));
			}

			$customFileName = $_FILES['custom_fields']['name'][ $field_id ];
			$extension = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );

			if( !in_array( $extension, $allowedFileFormats ) )
			{
				return $this->response(false, bkntcsaas__('File extension is not allowed!' ));
			}
		}


		$company_image = '';

		if( isset($_FILES['company_image']) && is_string($_FILES['company_image']['tmp_name']) )
		{
			$path_info = pathinfo( $_FILES["company_image"]["name"] );
			$extension = strtolower( $path_info['extension'] );

			if( !in_array( $extension, ['jpg', 'jpeg', 'png'] ) )
			{
				return $this->response(false, bkntcsaas__('Only JPG and PNG images allowed!'));
			}

			$company_image = md5( base64_encode(rand(1, 9999999) . microtime(true)) ) . '.' . $extension;
			$file_name = \BookneticApp\Providers\Helpers\Helper::uploadedFile( $company_image, 'Settings' );

			move_uploaded_file( $_FILES['company_image']['tmp_name'], $file_name );
		}

		Helper::setOption('company_name', $company_name, $tenantInfo->id);
		Helper::setOption('company_address', $address, $tenantInfo->id);
		Helper::setOption('company_phone', $phone_number, $tenantInfo->id);
		Helper::setOption('company_website', $website, $tenantInfo->id);

		if( $company_image != '' )
		{
			Helper::setOption('company_image', $company_image, $tenantInfo->id);
		}

		Tenant::where('activation_token', $token)->update([
			'activation_token'	=>	null
		]);


		foreach( $custom_fields AS $customFieldId => $customFieldValue )
		{
			TenantCustomData::insert([
				'tenant_id'		    =>	$tenantInfo->id,
				'form_input_id'		=>	$customFieldId,
				'input_value'		=>	$customFieldValue
			]);
		}



		foreach( $customFiles AS $customFieldId => $customFieldValue )
		{
			$customFileName = $_FILES['custom_fields']['name'][ $customFieldId ];
			$extension = strtolower( pathinfo($customFileName, PATHINFO_EXTENSION) );

			$newFileName = md5( base64_encode( microtime(1) . rand(1000,9999999) . uniqid() ) ) . '.' . $extension;

			$result01 = move_uploaded_file( $customFieldValue, Helper::uploadedFile( $newFileName, 'TenantCustomForms' ) );

			if( $result01 )
			{
				TenantCustomData::insert([
					'tenant_id'			=>	$tenantInfo->id,
					'form_input_id'		=>	$customFieldId,
					'input_value'		=>	$newFileName,
					'input_file_name'	=>	$customFileName
				]);
			}
		}

        do_action( 'bkntcsaas_tenant_sign_up_completed', $tenantInfo->id );

		Tenant::createInitialData( $tenantInfo->id );

		/**
		 * Sign in to wordpress
		 */
		$user = get_user_by('id', $tenantInfo->user_id);

		wp_set_current_user( $tenantInfo->user_id );
		wp_set_auth_cookie( $tenantInfo->user_id );
		do_action( 'wp_login', $user->user_login, $user );

		return $this->response( true );
	}

	public function forgot_password()
	{
		$email = Helper::_post('email', '', 'email');

		if( empty( $email ) )
		{
			return $this->response( false, bkntcsaas__('Please fill in all required fields correctly!') );
		}

		$tenantInf = Tenant::where('email', $email)->fetch();
		if( !$tenantInf )
		{
			return $this->response( false, bkntcsaas__('The email address is not registered!') );
		}

		$activation_token = md5( microtime(1) . uniqid() );

		Tenant::where('id', $tenantInf->id)->update([
			'activation_last_sent_time'		=>	Date::format( 'Y-m-d H:i:s' ),
			'activation_token'		        =>	$activation_token
		]);

        do_action( 'bkntcsaas_tenant_reset_password', $tenantInf->id );

		return $this->response( true );
	}

	public function resend_forgot_password_link()
	{
		$email      = Helper::_post('email', '', 'email');

		if( empty( $email ) )
		{
			return $this->response( false );
		}

		$checkEmail = Tenant::where('email', $email)
		                    ->where('activation_token', 'is not', null)
		                    ->where('activation_last_sent_time', '<=', Date::format( 'Y-m-d H:i:s', '-60 second'))
		                    ->fetch();

		if( !$checkEmail )
		{
			return $this->response( false );
		}

		$tenantId = $checkEmail->id;

		Tenant::where('id', $tenantId)->update([
			'activation_last_sent_time' => Date::format( 'Y-m-d H:i:s' )
		]);

        do_action( 'bkntcsaas_tenant_reset_password', $tenantId );

		return $this->response( true );
	}

	public function complete_forgot_password()
	{
		$token		    =	Helper::_post('token', '', 'string');
		$password1		=	Helper::_post('password1', '', 'string');
		$password2		=	Helper::_post('password2', '', 'string');

		if( empty( $token ) || empty( $password1 ) || empty( $password2 ) )
		{
			return $this->response( false, bkntcsaas__('Please fill in all required fields correctly!') );
		}

		if( $password1 !== $password2 )
		{
			return $this->response( false, bkntcsaas__('Please fill in all required fields correctly!') );
		}

		$tenantInfo = Tenant::where('activation_token', $token)->fetch();

		if( !$tenantInfo )
		{
			return $this->response( false, bkntcsaas__('Invalid request!') );
		}

		Tenant::where('activation_token', $token)->update([
			'activation_token'	=>	null
		]);

		wp_update_user([
			'ID'            =>  $tenantInfo->user_id,
			'user_pass'     =>  $password1
		]);

        do_action( 'bkntcsaas_tenant_reset_password_completed', $tenantInfo->id, $password1 );

		return $this->response( true );
	}

	public function get_tenant_custom_field_choices()
	{
		$inputId    = Helper::_post('input_id', '0', 'int');
		$query      = Helper::_post( 'q', '', 'str' );

		$choices = TenantFormInputChoice::where( 'form_input_id', $inputId );

		if ( ! empty( trim( $query ) ) )
		{
			$choices = $choices->where( 'title', 'like', '%' . DB::DB()->esc_like( $query ) . '%' );
		}

		$choices = $choices->orderBy('order_number')->fetchAll();

		$result = [];

		foreach ($choices AS $choice)
		{
			$result[] = [
				'id'  => (int)$choice['id'],
				'text'  => htmlspecialchars($choice['title'])
			];
		}

		return $this->response(true, [
			'results' => $result
		]);
	}

}
