<?php

namespace BookneticApp\Backend\Customers;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\Data;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Backend;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\UI\TabUI;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function add_new()
	{
		$cid = Helper::_post('id', '0', 'integer');

		if( $cid > 0 )
		{
			Capabilities::must( 'customers_edit' );

			$customerInfo = Customer::where('id', $cid)->fetch();
		}
		else
		{
			Capabilities::must( 'customers_add' );

			$customerInfo = [
				'id'                =>  null,
				'user_id'           =>  null,
				'first_name'        =>  null,
				'last_name'         =>  null,
				'phone_number'      =>  null,
				'email'             =>  null,
				'birthdate'         =>  null,
				'notes'             =>  null,
				'profile_image'     =>  null,
				'gender'            =>  null
			];
		}


        TabUI::get( 'customers_add_new' )
            ->item( 'customer_details' )
            ->setTitle( bkntc__( 'Customer details' ) )
            ->addView(__DIR__ . '/view/tab/add_customer_details.php')
            ->setPriority( 1 );


        $emailIsRequired = Helper::getOption('set_email_as_required', 'on');
		$phoneIsRequired = Helper::getOption('set_phone_as_required', 'off');

		$users = DB::DB()->get_results('SELECT * FROM `'.DB::DB()->base_prefix.'users`', ARRAY_A);

		return $this->modalView('add_new', [
			'customer'			=> $customerInfo,
			'email_is_required'	=> $emailIsRequired,
			'phone_is_required'	=> $phoneIsRequired,
			'users'             => $users,

			'show_only_name'    => Helper::getOption('separate_first_and_last_name', 'on') == 'off'
		]);
	}

    public function info()
    {
	    Capabilities::must( 'customers' );

        $cid = Helper::_post('id', '0', 'integer');

        $customer = Customer::get($cid);
        $customer_billing_datas = Appointment::where('customer_id', $cid)
            ->innerJoin(Data::getTableName(), ['data_value'], Appointment::getField('id'), Data::getField('row_id'))
            ->where(Data::getField('table_name'), Appointment::getTableName())
            ->where(Data::getField('data_key'), 'customer_billing_data')
            ->fetchAll();

        TabUI::get( 'customers_info' )
            ->item( 'info' )
            ->setTitle( bkntc__( 'Customer Info' ) )
            ->addView(__DIR__ . '/view/tab/customer_info_details.php')
            ->setPriority( 1 );

        return $this->modalView('info', [
            'customer' => $customer,
            'customer_billing_datas' => $customer_billing_datas
        ]);
    }

	public function import()
	{
		Capabilities::must( 'customers_import' );

		return $this->modalView('import' );
	}

	public function save_customer()
	{
		$id			                = Helper::_post('id', '0', 'integer');

		if( $id > 0 )
		{
			Capabilities::must( 'customers_edit' );
		}
		else
		{
			Capabilities::must( 'customers_add' );
		}

		$wp_user	                = Helper::_post('wp_user', '0', 'integer');
		$first_name	                = Helper::_post('first_name', '', 'string');
		$last_name	                = Helper::_post('last_name', '', 'string');
		$gender		                = Helper::_post('gender', '', 'string', ['male', 'female']);
		$birthday	                = Helper::_post('birthday', '', 'string');
		$phone		                = Helper::_post('phone', '', 'string');
		$email		                = Helper::_post('email', '', 'email');
		$allow_customer_to_login	= Helper::_post('allow_customer_to_login', '0', 'int', ['0', '1']);
		$wp_user_use_existing	    = Helper::_post('wp_user_use_existing', 'yes', 'string', ['yes', 'no']);
		$wp_user_password		    = Helper::_post('wp_user_password', '', 'string');
		$note		                = Helper::_post('note', '', 'string');

		$show_only_name = Helper::getOption('separate_first_and_last_name', 'on') == 'off';

		if( empty($first_name) || (empty($last_name) && !$show_only_name) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		$isEdit = $id > 0;

		if( $isEdit )
		{
			$getOldInf = Customer::get( $id );
			if( !$getOldInf )
			{
				return $this->response(false, bkntc__('Customer not found or permission denied!'));
			}
		}

        if( $wp_user > 0)
        {
            $selectedWpUser = Customer::where('user_id' , $wp_user)->fetch();

            if( $isEdit )
            {
                if( $getOldInf->user_id != $wp_user && ! empty( $selectedWpUser ) )
                {
                    return $this->response(false, bkntc__('This wordpress user is already connected to another booknetic customer (ID: %d)', [ $selectedWpUser->id ] ) );
                }
            }
            else
            {
                if( ! empty( $selectedWpUser ) )
                {
                    return $this->response(false, bkntc__('This wordpress user is already connected to another booknetic customer (ID: %d)', [ $selectedWpUser->id ] ) );
                }
            }
        }

		$emailIsRequired = Helper::getOption('set_email_as_required', 'on');
		$phoneIsRequired = Helper::getOption('set_phone_as_required', 'off');

		if( $emailIsRequired == 'on' && empty( $email ) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		if( $phoneIsRequired == 'on' && empty( $phone ) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		if( ( ! $isEdit || $email != $getOldInf->email ) && ( email_exists( $email ) !== false || username_exists( $email ) !== false) )
		{
            if(  ! ( $allow_customer_to_login && $wp_user_use_existing === 'yes' && $wp_user > 0 && get_userdata( $wp_user ) && ! in_array('booknetic_customer' , get_userdata( $wp_user )->roles ) ))
            {
			    return $this->response( false, bkntc__('There is another customer with the same email adress!') );
            }
		}

		if( !Permission::isAdministrator() )
		{
			$wp_user = $isEdit ? $getOldInf->user_id : 0;
		}
		else if( $allow_customer_to_login == 1 )
		{
			if( $wp_user_use_existing == 'yes' && !( $wp_user > 0 ) )
			{
				return $this->response( false, bkntc__('Please select WordPress user!') );
			}
            else if( $wp_user_use_existing == 'yes' && $wp_user > 0 )
            {
                get_userdata($wp_user )->add_role('booknetic_customer');
            }
			else if( $wp_user_use_existing == 'no' )
			{
				if( !($isEdit && $getOldInf->user_id > 0) && empty( $wp_user_password ) )
				{
					return $this->response( false, bkntc__('Please type the password of the WordPress user!') );
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

					if( $first_name != $getOldInf->first_name || $last_name != $getOldInf->last_name )
					{
						$updateData['display_name'] = trim( $first_name . ' ' . $last_name );
						$updateData['first_name'] = $first_name;
						$updateData['last_name'] = $last_name;
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
							return $this->response( false, $user_data->get_error_message() );
						}
					}
				}
				else
				{
					$wp_user = wp_insert_user( [
						'user_login'	=>	$email,
						'user_email'	=>	$email,
						'display_name'	=>	trim( $first_name . ' ' . $last_name ),
						'first_name'	=>	$first_name,
						'last_name'		=>	$last_name,
						'role'			=>	'booknetic_customer',
						'user_pass'		=>	$wp_user_password
					] );

					if( is_wp_error( $wp_user ) )
					{
						return $this->response( false, $wp_user->get_error_message() );
					}
				}
			}
		}
		else
		{
			if( $isEdit && $getOldInf->user_id > 0 )
			{
				$userData = get_userdata( $getOldInf->user_id );
				if( $userData && in_array( 'booknetic_customer', $userData->roles ) )
				{
					require_once ABSPATH.'wp-admin/includes/user.php';
					wp_delete_user( $getOldInf->user_id );
				}
			}

			$wp_user = 0;
		}

		$profile_image = '';

		if( isset($_FILES['image']) && is_string($_FILES['image']['tmp_name']) )
		{
			$path_info = pathinfo($_FILES["image"]["name"]);
			$extension = strtolower( $path_info['extension'] );

			if( !in_array( $extension, ['jpg', 'jpeg', 'png'] ) )
			{
				return $this->response(false, bkntc__('Only JPG and PNG images allowed!'));
			}

			$profile_image = md5( base64_encode(rand(1,9999999) . microtime(true)) ) . '.' . $extension;
			$file_name = Helper::uploadedFile( $profile_image , 'Customers' );

			move_uploaded_file( $_FILES['image']['tmp_name'], $file_name );
		}

		$sqlData = [
			'user_id'		=>	$wp_user > 0 ? $wp_user : 0,
			'first_name'	=>	trim($first_name),
			'last_name'		=>	trim($last_name),
			'phone_number'	=>	$phone,
			'email'			=>	$email,
			'birthdate'		=>	empty( $birthday ) ? null : Date::reformatDateFromCustomFormat($birthday),
			'notes'			=>	$note,
			'profile_image'	=>	$profile_image,
			'gender'		=>	$gender
		];

		if( $isEdit )
		{
			if( empty( $profile_image ) )
			{
				unset( $sqlData['profile_image'] );
			}
			else
			{
				if( !empty( $getOldInf['profile_image'] ) )
				{
					$filePath = Helper::uploadedFile( $getOldInf['profile_image'] , 'Customers' );

					if( is_file( $filePath ) && is_writable( $filePath ) )
					{
						unlink( $filePath );
					}
				}
			}

			Customer::where('id', $id)->update( $sqlData );
		}
		else
		{
			$sqlData['created_by'] = Permission::userId();
			$sqlData['created_at'] = date('Y-m-d H:i:s');
			Customer::insert( $sqlData );
		}

		return $this->response(true );
	}

	public function import_customers()
	{
		Capabilities::must( 'customers_import' );

		$delimiter	= Helper::_post('delimiter', ';', 'string', [';', ',']);
		$fields		= Helper::_post('fields', '', 'string');

		$fields1 = [];

		foreach( explode(',', $fields) AS $fieldName )
		{
			if( in_array( $fieldName, ['first_name', 'last_name', 'email', 'phone_number', 'gender', 'birthdate', 'notes'] ) )
			{
				$fields1[] = $fieldName;
			}
		}

		if( empty($fields1) )
		{
			return $this->response(false, bkntc__('Please fill in all required fields correctly!'));
		}

		$fieldsCount = count($fields1);

		if( !( isset($_FILES['csv']) && is_string($_FILES['csv']['tmp_name']) ) )
		{
			return $this->response(false, bkntc__('Please select CSV file!'));
		}

		$csvFile = $_FILES['csv']['tmp_name'];

		$csvArray = [];

		$file = fopen($csvFile, 'r');
		while ( ($result = fgetcsv($file, 0, $delimiter)) !== false)
		{
			if( count( $result ) > $fieldsCount )
			{
				return $this->response(false, bkntc__('Too many fields detected on CSV file!'));
			}

			$csvArray[] = $result;
		}
		fclose($file);
		unset($file);

		foreach( $csvArray AS $rows )
		{
			$insertData = [];

			foreach ($rows as $fieldNum => $data)
			{
				$fieldName = $fields1[$fieldNum];

				$insertData[ $fieldName ] = $data;
			}

			// check if email is correct...
			if( isset( $insertData[ 'email' ] ) && !empty( $insertData[ 'email' ] ) && !filter_var( $insertData[ 'email' ], FILTER_VALIDATE_EMAIL ) )
			{
				continue;
			}

			if( isset( $insertData[ 'phone_number' ] ) && !empty( $insertData[ 'phone_number' ] ) && strpos( $insertData[ 'phone_number' ], '+' ) !== 0 )
			{
				$insertData[ 'phone_number' ] = '+' . $insertData[ 'phone_number' ];
			}

			if( isset( $insertData[ 'birthdate' ] ) )
			{
				$insertData[ 'birthdate' ] = empty( $insertData[ 'birthdate' ] ) ? null : Date::dateSQL( $insertData[ 'birthdate' ] );
			}

            $insertData['created_by'] = Permission::userId();

			Customer::insert( $insertData );
		}

		return $this->response(true );
	}

}
