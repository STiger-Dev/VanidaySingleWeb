<?php

namespace BookneticSaaS\Providers\Helpers;

use BookneticApp\Models\Customer;
use BookneticSaaS\Models\Tenant;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Core\Permission;


class Helper
{
	public static function floor( $number, $scale = 2 )
	{
		if( !is_numeric( $number ) )
			$number = 0;

		$mult = pow(10, $scale);
		return ($number * $mult) / $mult;
	}

	public static function response($status , $arr = [], $returnResult = false )
	{
		$arr = is_array($arr) ? $arr : ( is_string($arr) ? ['error_msg' => $arr] : [] );

		if( $status )
		{
			$arr['status'] = 'ok';
		}
		else
		{
			$arr['status'] = 'error';

			if( !isset($arr['error_msg']) )
			{
				$arr['error_msg'] = 'Error!';
			}

			if( self::isModal() )
			{
				$arr['status'] = 'ok';
				$arr['html'] = '
					<div class="fs-modal-body mt-5">
						<div class="text-center mt-5 text-secondary">' . $arr['error_msg'] . '</div>
					</div>
					<div class="fs-modal-footer">
						<button type="button" class="btn btn-lg btn-default" data-dismiss="modal">' . bkntcsaas__('CLOSE') . '</button>
					</div>';
				unset($arr['error_msg']);
			}
		}

        if( $returnResult )
        {
            return $arr;
        }
        else
        {
            echo json_encode( $arr );
            exit();
        }
	}

	public static function isModal()
	{
		$isModalRequest = Helper::_post('_mn', false, 'int');

		return $isModalRequest === false ? false : true;
	}

	public static function _post( $key , $default = null , $check_type = null , $whiteList = [] )
	{
		$res = isset($_POST[$key]) ? $_POST[$key] : $default;

		if( $res !== $default && !is_null( $check_type ) )
		{
			if( $check_type == 'num' || $check_type == 'int' || $check_type == 'integer' )
			{
				$res = is_numeric( $res ) ? (int)$res : $default;
			}
			else if($check_type == 'str' || $check_type == 'string')
			{
				$res = is_string( $res ) ? stripslashes_deep((string)$res) : $default;
			}
			else if($check_type == 'arr' || $check_type == 'array')
			{
				$res = is_array( $res ) ? stripslashes_deep((array)$res) : $default;
			}
			else if($check_type == 'float')
			{
				$res = is_numeric( $res ) ? (float)$res : $default;
			}
			else if($check_type == 'email')
			{
				$res = is_string( $res ) && filter_var($res, FILTER_VALIDATE_EMAIL) !== false ? (string)$res : $default;
			}
			else if($check_type == 'json')
			{
				$res = is_string( $res ) ? json_decode( stripslashes_deep( $res ), true ) : $default;
				$res = is_array( $res ) ? $res : $default;
			}
		}

		if( !empty( $whiteList ) && !in_array( $res , $whiteList ) )
		{
			$res = $default;
		}

		return $res;
	}

	public static function _get( $key , $default = null , $check_type = null , $whiteList = [] )
	{
		$res = isset($_GET[$key]) ? $_GET[$key] : $default;

		if( $res !== $default && !is_null( $check_type ) )
		{
			if( $check_type == 'num' || $check_type == 'int' || $check_type == 'integer' )
			{
				$res = is_numeric( $res ) ? (int)$res : $default;
			}
			else if($check_type == 'str' || $check_type == 'string')
			{
				$res = is_string( $res ) ? (string)$res : $default;
			}
			else if($check_type == 'arr' || $check_type == 'array')
			{
				$res = is_array( $res ) ? (array)$res : $default;
			}
			else if($check_type == 'float')
			{
				$res = is_numeric( $res ) ? (float)$res : $default;
			}
			else if($check_type == 'json')
			{
				$res = json_decode( (string)$res, true );
				$res = is_array( $res ) ? $res : $default;
			}
		}

		if( !empty( $whiteList ) && !in_array( $res , $whiteList ) )
		{
			$res = $default;
		}

		return $res;
	}

	public static function _any( $key , $default = null , $check_type = null , $whiteList = [] )
	{
		$res = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;

		if( $res !== $default && !is_null( $check_type ) )
		{
			if( $check_type == 'num' || $check_type == 'int' || $check_type == 'integer' )
			{
				$res = is_numeric( $res ) ? (int)$res : $default;
			}
			else if($check_type == 'str' || $check_type == 'string')
			{
				$res = is_string( $res ) ? (string)$res : $default;
			}
			else if($check_type == 'arr' || $check_type == 'array')
			{
				$res = is_array( $res ) ? (array)$res : $default;
			}
			else if($check_type == 'float')
			{
				$res = is_numeric( $res ) ? (float)$res : $default;
			}
			else if($check_type == 'json')
			{
				$res = json_decode( (string)$res, true );
				$res = is_array( $res ) ? $res : $default;
			}
		}

		if( !empty( $whiteList ) && !in_array( $res , $whiteList ) )
		{
			$res = $default;
		}

		return $res;
	}

	public static function getVersion()
	{
		$plugin_data = get_file_data(__DIR__ . '/../../../init.php' , array('Version' => 'Version') , false);

		return isset($plugin_data['Version']) ? $plugin_data['Version'] : '1.0.0';
	}

	public static function getInstalledVersion()
	{
		$ver = self::getOption('saas_plugin_version' , '1.0.0');

		return ( $ver === '1' || empty($ver) ) ? '1.0.0' : $ver;
	}

	public static function assets( $url, $module = 'Base', $is_addon = false )
	{
		if( preg_match('/\.(js|css)$/i', $url) )
		{
			$url .= '?v=' . self::getVersion();
		}

		if ( $is_addon === true )
		{
			return WP_PLUGIN_URL . '/' . $module . '/App/assets/' . ltrim( $url, '/' );
		}

		if( $module == 'front-end' )
		{
			return rtrim(plugin_dir_url( dirname( __DIR__ ) ), '/') . '/Frontend/assets/' . ltrim($url, '/');
		}

		return rtrim(plugin_dir_url( dirname( __DIR__ ) ), '/') . '/Backend/' . urlencode( ucfirst($module) ) . '/assets/' . ltrim($url, '/');
	}

	public static function icon( $icon, $module = 'Base', $is_addon = false )
	{
		if ( $is_addon === true )
		{
			return WP_PLUGIN_URL . '/booknetic-addon-' . self::camelToKebab( $module ) . '/App/assets/icons/' . ltrim($icon, '/');
		}

		if( $module == 'front-end' )
		{
			return rtrim(plugin_dir_url( dirname( __DIR__ ) ), '/') . '/Frontend/assets/icons/' . ltrim($icon, '/');
		}

		return rtrim(plugin_dir_url( dirname( __DIR__ ) ), '/') . '/Backend/' . urlencode( ucfirst($module) ) . '/assets/icons/' . ltrim($icon, '/');
	}

	public static function uploadFolderURL( $module )
	{
		$upload_dir	= wp_upload_dir();
		$upload_dir = $upload_dir['baseurl'] . '/booknetic_saas/' . strtolower( $module ) . ( empty($module) ? '' : '/' );

		if( !is_dir( $upload_dir ) )
			wp_mkdir_p( $upload_dir );

		return $upload_dir;
	}

	public static function uploadedFileURL( $fileName, $module = 'Base' )
	{
		return self::uploadFolderURL( $module ) . basename( $fileName );
	}

	public static function uploadFolder( $module )
	{
		$upload_dir	= wp_upload_dir();
		$upload_dir = $upload_dir['basedir'] . '/booknetic_saas/' . strtolower( $module ) . ( empty($module) ? '' : '/' );

		if( !is_dir( $upload_dir ) )
			wp_mkdir_p( $upload_dir );

		return $upload_dir;
	}

	public static function uploadedFile( $fileName, $module = 'Base' )
	{
		return self::uploadFolder( $module ) . basename( $fileName );
	}

	public static function getOption( $optionName, $default = null, $tenantId = null, $hideInDemoVersion = false )
	{
		/**
		 * Hide some options in demo version of Booknetic.
		 */
		if( Permission::isDemoVersion() && $hideInDemoVersion && in_array( $optionName, [
			'smtp_hostname', 'smtp_username', 'smtp_password',
			'paypal_client_id', 'paypal_client_secret', 'paypal_webhook_id',
			'stripe_client_id', 'stripe_client_secret', 'stripe_webhook_secret'
		] ) )
		{
			return '******';
		}

		$prefix = 'bkntc_';

		if( $tenantId > 0 )
		{
			$prefix .= 't' . $tenantId . '_';
		}

		return get_option( $prefix . $optionName, $default );
	}

	public static function setOption( $optionName, $optionValue, $tenantId = null, $autoLoad = null )
	{
		$prefix = 'bkntc_';

		if( $tenantId > 0 )
		{
			$prefix .= 't' . $tenantId . '_';
		}

		return update_option( $prefix . $optionName, $optionValue, $autoLoad );
	}

	public static function deleteOption( $optionName, $tenantId )
	{
		$prefix = 'bkntc_';

		if( $tenantId > 0 )
		{
			$prefix .= 't' . $tenantId . '_';
		}

		return delete_option( $prefix . $optionName );
	}

	public static function price( $price, $currency = null )
	{
		$scale				= self::getOption('price_number_of_decimals', '2');
		$priceNumberFormat	= self::getOption('price_number_format', '1');

		switch( $priceNumberFormat )
		{
			case '2':
				$decimalPoint		= '.';
				$thousandsSeparator	= ',';
				break;
			case '3':
				$decimalPoint		= ',';
				$thousandsSeparator	= ' ';
				break;
			case '4':
				$decimalPoint		= ',';
				$thousandsSeparator	= '.';
				break;
			default:
				$decimalPoint		= '.';
				$thousandsSeparator	= ' ';
				break;
		}

		$price = static::floor( $price,  $scale);

		$price = number_format($price, $scale, $decimalPoint, $thousandsSeparator);

		$currencyFormat	= self::getOption('currency_format', '1');
		$currency		= is_null( $currency ) ? self::currencySymbol() : $currency;

		switch ( $currencyFormat )
		{
			case '2':
				return $currency . ' ' . $price;
			case '3':
				return $price . $currency;
			case '4':
				return $price . ' ' . $currency;
			default:
				return $currency . $price;
		}
	}

	public static function currencySymbol( $currency = null )
	{
		$currency_symbol = Helper::getOption('currency_symbol', '');

		if( !empty( $currency_symbol ) )
		{
			return $currency_symbol;
		}

		$currency = is_null( $currency ) ? Helper::getOption('currency', 'USD') : $currency;

		$currencyInf = self::currencies( $currency );

		return isset($currencyInf['symbol']) ? $currencyInf['symbol'] : '$';
	}

	public static function currencies( $currency = null )
	{
		$currencies = [
			'USD' => [ 'name' => 'US Dollar', 'symbol' => '$'],
			'EUR' => [ 'name' => 'Euro', 'symbol' => '€'],
			'GBP' => [ 'name' => 'Pound Sterling', 'symbol' => '£'],
			'AFN' => [ 'name' => 'Afghani', 'symbol' => 'Af'],
			'DZD' => [ 'name' => 'Algerian Dinar', 'symbol' => 'د.ج'],
			'ARS' => [ 'name' => 'Argentine Peso', 'symbol' => '$'],
			'AMD' => [ 'name' => 'Armenian Dram', 'symbol' => 'Դ'],
			'AWG' => [ 'name' => 'Aruban Guilder/Florin', 'symbol' => 'ƒ'],
			'AUD' => [ 'name' => 'Australian Dollar', 'symbol' => '$'],
			'AZN' => [ 'name' => 'Azerbaijani Manat', 'symbol' => 'AZN'],
			'BSD' => [ 'name' => 'Bahamian Dollar', 'symbol' => '$'],
			'BHD' => [ 'name' => 'Bahraini Dinar', 'symbol' => 'ب.د'],
			'THB' => [ 'name' => 'Baht', 'symbol' => '฿'],
			'PAB' => [ 'name' => 'Balboa', 'symbol' => 'B/.'],
			'BBD' => [ 'name' => 'Barbados Dollar', 'symbol' => '$'],
			'BYN' => [ 'name' => 'Belarusian Ruble', 'symbol' => 'Br'],
			'BZD' => [ 'name' => 'Belize Dollar', 'symbol' => '$'],
			'BMD' => [ 'name' => 'Bermudian Dollar', 'symbol' => '$'],
			'VEF' => [ 'name' => 'Bolivar Fuerte', 'symbol' => 'Bs F'],
			'BOB' => [ 'name' => 'Boliviano', 'symbol' => 'Bs.'],
			'BRL' => [ 'name' => 'Brazilian Real', 'symbol' => 'R$'],
			'BND' => [ 'name' => 'Brunei Dollar', 'symbol' => '$'],
			'BGN' => [ 'name' => 'Bulgarian Lev', 'symbol' => 'лв'],
			'BIF' => [ 'name' => 'Burundi Franc', 'symbol' => '₣'],
			'CAD' => [ 'name' => 'Canadian Dollar', 'symbol' => '$'],
			'CVE' => [ 'name' => 'Cape Verde Escudo', 'symbol' => '$'],
			'KYD' => [ 'name' => 'Cayman Islands Dollar', 'symbol' => '$'],
			'GHS' => [ 'name' => 'Cedi', 'symbol' => '₵'],
			'XAF' => [ 'name' => 'CFA Franc BCEAO', 'symbol' => '₣'],
			'XPF' => [ 'name' => 'CFP Franc', 'symbol' => '₣'],
			'CLP' => [ 'name' => 'Chilean Peso', 'symbol' => '$'],
			'COP' => [ 'name' => 'Colombian Peso', 'symbol' => '$'],
			'CDF' => [ 'name' => 'Congolese Franc', 'symbol' => '₣'],
			'NIO' => [ 'name' => 'Cordoba Oro', 'symbol' => 'C$'],
			'CRC' => [ 'name' => 'Costa Rican Colon', 'symbol' => '₡'],
			'HRK' => [ 'name' => 'Croatian Kuna', 'symbol' => 'Kn'],
			'CUP' => [ 'name' => 'Cuban Peso', 'symbol' => '$'],
			'CZK' => [ 'name' => 'Czech Koruna', 'symbol' => 'Kč'],
			'GMD' => [ 'name' => 'Dalasi', 'symbol' => 'D'],
			'DKK' => [ 'name' => 'Danish Krone', 'symbol' => 'kr'],
			'MKD' => [ 'name' => 'Denar', 'symbol' => 'ден'],
			'DJF' => [ 'name' => 'Djibouti Franc', 'symbol' => '₣'],
			'STN' => [ 'name' => 'Dobra', 'symbol' => 'Db'],
			'DOP' => [ 'name' => 'Dominican Peso', 'symbol' => '$'],
			'VND' => [ 'name' => 'Dong', 'symbol' => '₫'],
			'XCD' => [ 'name' => 'East Caribbean Dollar', 'symbol' => '$'],
			'EGP' => [ 'name' => 'Egyptian Pound', 'symbol' => '£'],
			'ETB' => [ 'name' => 'Ethiopian Birr', 'symbol' => 'ETB'],
			'FKP' => [ 'name' => 'Falkland Islands Pound', 'symbol' => '£'],
			'FJD' => [ 'name' => 'Fiji Dollar', 'symbol' => '$'],
			'HUF' => [ 'name' => 'Forint', 'symbol' => 'Ft'],
			'GIP' => [ 'name' => 'Gibraltar Pound', 'symbol' => '£'],
			'HTG' => [ 'name' => 'Gourde', 'symbol' => 'G'],
			'PYG' => [ 'name' => 'Guarani', 'symbol' => '₲'],
			'GNF' => [ 'name' => 'Guinea Franc', 'symbol' => '₣'],
			'GYD' => [ 'name' => 'Guyana Dollar', 'symbol' => '$'],
			'HKD' => [ 'name' => 'Hong Kong Dollar', 'symbol' => '$'],
			'UAH' => [ 'name' => 'Hryvnia', 'symbol' => '₴'],
			'ISK' => [ 'name' => 'Iceland Krona', 'symbol' => 'Kr'],
			'INR' => [ 'name' => 'Indian Rupee', 'symbol' => '₹'],
			'IRR' => [ 'name' => 'Iranian Rial', 'symbol' => '﷼'],
			'IQD' => [ 'name' => 'Iraqi Dinar', 'symbol' => 'ع.د'],
			'JMD' => [ 'name' => 'Jamaican Dollar', 'symbol' => '$'],
			'JOD' => [ 'name' => 'Jordanian Dinar', 'symbol' => 'د.ا'],
			'KES' => [ 'name' => 'Kenyan Shilling', 'symbol' => 'Sh'],
			'PGK' => [ 'name' => 'Kina', 'symbol' => 'K'],
			'LAK' => [ 'name' => 'Kip', 'symbol' => '₭'],
			'BAM' => [ 'name' => 'Konvertibilna Marka', 'symbol' => 'КМ'],
			'KWD' => [ 'name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك'],
			'MWK' => [ 'name' => 'Kwacha', 'symbol' => 'MK'],
			'AOA' => [ 'name' => 'Kwanza', 'symbol' => 'Kz'],
			'MMK' => [ 'name' => 'Kyat', 'symbol' => 'K'],
			'GEL' => [ 'name' => 'Lari', 'symbol' => 'ლ'],
			'LBP' => [ 'name' => 'Lebanese Pound', 'symbol' => 'ل.ل'],
			'ALL' => [ 'name' => 'Lek', 'symbol' => 'L'],
			'HNL' => [ 'name' => 'Lempira', 'symbol' => 'L'],
			'SLL' => [ 'name' => 'Leone', 'symbol' => 'Le'],
			'RON' => [ 'name' => 'Leu', 'symbol' => 'L'],
			'LRD' => [ 'name' => 'Liberian Dollar', 'symbol' => '$'],
			'LYD' => [ 'name' => 'Libyan Dinar', 'symbol' => 'ل.د'],
			'SZL' => [ 'name' => 'Lilangeni', 'symbol' => 'L'],
			'LSL' => [ 'name' => 'Loti', 'symbol' => 'L'],
			'MGA' => [ 'name' => 'Malagasy Ariary', 'symbol' => 'MGA'],
			'MYR' => [ 'name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
			'TMT' => [ 'name' => 'Manat', 'symbol' => 'm'],
			'MUR' => [ 'name' => 'Mauritius Rupee', 'symbol' => '₨'],
			'MZN' => [ 'name' => 'Metical', 'symbol' => 'MTn'],
			'MXN' => [ 'name' => 'Mexican Peso', 'symbol' => '$'],
			'MDL' => [ 'name' => 'Moldovan Leu', 'symbol' => 'L'],
			'MAD' => [ 'name' => 'Moroccan Dirham', 'symbol' => 'د.م.'],
			'NGN' => [ 'name' => 'Naira', 'symbol' => '₦'],
			'ERN' => [ 'name' => 'Nakfa', 'symbol' => 'Nfk'],
			'NAD' => [ 'name' => 'Namibia Dollar', 'symbol' => '$'],
			'NPR' => [ 'name' => 'Nepalese Rupee', 'symbol' => '₨'],
			'ILS' => [ 'name' => 'New Israeli Shekel', 'symbol' => '₪'],
			'NZD' => [ 'name' => 'New Zealand Dollar', 'symbol' => '$'],
			'BTN' => [ 'name' => 'Ngultrum', 'symbol' => 'BTN'],
			'KPW' => [ 'name' => 'North Korean Won', 'symbol' => '₩'],
			'NOK' => [ 'name' => 'Norwegian Krone', 'symbol' => 'kr'],
			'PEN' => [ 'name' => 'Nuevo Sol', 'symbol' => 'S/.'],
			'MRU' => [ 'name' => 'Ouguiya', 'symbol' => 'UM'],
			'TOP' => [ 'name' => 'Pa’anga', 'symbol' => 'T$'],
			'PKR' => [ 'name' => 'Pakistan Rupee', 'symbol' => '₨'],
			'MOP' => [ 'name' => 'Pataca', 'symbol' => 'P'],
			'UYU' => [ 'name' => 'Peso Uruguayo', 'symbol' => 'UYU'],
			'PHP' => [ 'name' => 'Philippine Peso', 'symbol' => '₱'],
			'BWP' => [ 'name' => 'Pula', 'symbol' => 'P'],
			'PLN' => [ 'name' => 'PZloty', 'symbol' => 'zł'],
			'QAR' => [ 'name' => 'Qatari Rial', 'symbol' => 'ر.ق'],
			'GTQ' => [ 'name' => 'Quetzal', 'symbol' => 'Q'],
			'ZAR' => [ 'name' => 'Rand', 'symbol' => 'R'],
			'OMR' => [ 'name' => 'Rial Omani', 'symbol' => 'ر.ع.'],
			'KHR' => [ 'name' => 'Riel', 'symbol' => '៛'],
			'MVR' => [ 'name' => 'Rufiyaa', 'symbol' => 'ރ.'],
			'IDR' => [ 'name' => 'Rupiah', 'symbol' => 'Rp'],
			'RUB' => [ 'name' => 'Russian Ruble', 'symbol' => 'р.'],
			'RWF' => [ 'name' => 'Rwanda Franc', 'symbol' => '₣'],
			'SHP' => [ 'name' => 'Saint Helena Pound', 'symbol' => '£'],
			'SAR' => [ 'name' => 'Saudi Riyal', 'symbol' => 'ر.س'],
			'RSD' => [ 'name' => 'Serbian Dinar', 'symbol' => 'din'],
			'SCR' => [ 'name' => 'Seychelles Rupee', 'symbol' => '₨'],
			'SGD' => [ 'name' => 'Singapore Dollar', 'symbol' => '$'],
			'SBD' => [ 'name' => 'Solomon Islands Dollar', 'symbol' => '$'],
			'KGS' => [ 'name' => 'Som', 'symbol' => 'KGS'],
			'SOS' => [ 'name' => 'Somali Shilling', 'symbol' => 'Sh'],
			'TJS' => [ 'name' => 'Somoni', 'symbol' => 'ЅМ'],
			'KRW' => [ 'name' => 'South Korean Won', 'symbol' => '₩'],
			'LKR' => [ 'name' => 'Sri Lanka Rupee', 'symbol' => 'Rs'],
			'SDG' => [ 'name' => 'Sudanese Pound', 'symbol' => '£'],
			'SRD' => [ 'name' => 'Suriname Dollar', 'symbol' => '$'],
			'SEK' => [ 'name' => 'Swedish Krona', 'symbol' => 'kr'],
			'CHF' => [ 'name' => 'Swiss Franc', 'symbol' => '₣'],
			'SYP' => [ 'name' => 'Syrian Pound', 'symbol' => 'ل.س'],
			'TWD' => [ 'name' => 'Taiwan Dollar', 'symbol' => '$'],
			'BDT' => [ 'name' => 'Taka', 'symbol' => '৳'],
			'WST' => [ 'name' => 'Tala', 'symbol' => 'T'],
			'TZS' => [ 'name' => 'Tanzanian Shilling', 'symbol' => 'Sh'],
			'KZT' => [ 'name' => 'Tenge', 'symbol' => '〒'],
			'TTD' => [ 'name' => 'Trinidad and Tobago Dollar', 'symbol' => '$'],
			'MNT' => [ 'name' => 'Tugrik', 'symbol' => '₮'],
			'TND' => [ 'name' => 'Tunisian Dinar', 'symbol' => 'د.ت'],
			'TRY' => [ 'name' => 'Turkish Lira', 'symbol' => '₤'],
			'AED' => [ 'name' => 'UAE Dirham', 'symbol' => 'د.إ'],
			'UGX' => [ 'name' => 'Uganda Shilling', 'symbol' => 'Sh'],
			'UZS' => [ 'name' => 'Uzbekistan Sum', 'symbol' => 'UZS'],
			'VUV' => [ 'name' => 'Vatu', 'symbol' => 'Vt'],
			'YER' => [ 'name' => 'Yemeni Rial', 'symbol' => '﷼'],
			'JPY' => [ 'name' => 'Yen', 'symbol' => '¥'],
			'CNY' => [ 'name' => 'Yuan', 'symbol' => '¥'],
			'ZMW' => [ 'name' => 'Zambian Kwacha', 'symbol' => 'ZK'],
			'ZWL' => [ 'name' => 'Zimbabwe Dollar', 'symbol' => '$']
		];

		if( is_null( $currency ) )
		{
			return $currencies;
		}
		else
		{
			return isset( $currencies[ $currency ] ) ? $currencies[ $currency ] : false;
		}
	}

	public static function pluginTables()
	{
		return [
			'plans',
			'tenant_billing',
			'tenant_custom_data',
			'tenant_form_input_choices',
            'tenant_form_inputs',
            'tenants'
		];
	}

	public static function uninstallPlugin()
	{
		// drop tables...
		$deleteTables = self::pluginTables();

		foreach( $deleteTables AS $tableName )
		{
			DB::DB()->query("DROP TABLE IF EXISTS `" . DB::table( $tableName ) . "`");
		}
	}

	public static function redirect( $url )
	{
		header('Location: ' . $url);
		exit();
	}

	public static function secureFileFormats( $formats )
	{
		$newFormats = [];
		foreach ( $formats AS $format )
		{
			$format = strtolower($format);

			if( !preg_match( '/^(php[0-9]*)|(htaccess)|(htpasswd)|(ini)$/', $format ) )
			{
				$newFormats[] = $format;
			}
		}

		return $newFormats;
	}

	public static function addUrlParameter( $url, $params )
	{
		if( strpos( $url, '?' ) !== false )
		{
			return $url . '&' . $params;
		}

		return $url . '?' . $params;
	}

	public static function getHostName( $url = null )
	{
		$url = is_null( $url ) ? site_url() : $url;
		$url = str_replace( ['https://', 'http://'], '', $url );

		return trim( $url, '/' );
	}

	public static function isPermalinkStructure()
	{
		return get_option( 'permalink_structure' );
	}

	public static function getCurrentDomain()
	{
		if ( Helper::isPermalinkStructure() )
		{
			$request_url = $_SERVER['REQUEST_URI'];
			if( !empty( $_SERVER['QUERY_STRING'] ) )
			{
				$request_url = str_replace( '?' . $_SERVER['QUERY_STRING'], '', $request_url );
			}

			$tenantDomain = trim( substr( $request_url, strlen( parse_url( home_url('/'), PHP_URL_PATH ) ) ), '/' );

			$tenantDomain = explode('/', $tenantDomain);

			if( count( $tenantDomain ) > 2 || $tenantDomain[0] == 'wp-admin' )
			{
				$tenantDomain = '';
			}
			else
			{
				$tenantDomain = $tenantDomain[0];
			}
		}
		else
		{
			$tenantDomain = Helper::_get( 'c', '', 'string' );
		}

		return $tenantDomain;
	}

	public static function paymentMethod( $key )
	{
		switch( $key )
		{
			case "paypal":
				return bkntcsaas__("PayPal");
				break;
			case "credit_card":
				return bkntcsaas__("Credit card");
				break;
			case "woocommerce":
				return bkntcsaas__("WooCommerce");
				break;
			case "balance":
				return bkntcsaas__("From balance");
				break;
			case "offline":
				return bkntcsaas__("Offline");
				break;
			default:
				return '?';
		}
	}

	public static function getURLOfUsersDashboard( $user = null )
	{
		if( is_null( $user ) )
		{
			$user = wp_get_current_user();
		}

		if( in_array( 'booknetic_saas_tenant', $user->roles ) )
		{
			$redirect_url = admin_url( '/admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() );
		}
		else if( in_array( 'booknetic_staff', $user->roles ) )
		{
			$redirect_url = admin_url( '/admin.php?page=' . \BookneticApp\Providers\Helpers\Helper::getSlugName() );
		}
		else if( in_array( 'booknetic_customer', $user->roles ) )
		{
			$redirect_url = \BookneticApp\Providers\Helpers\Helper::customerPanelURL();
		}
		else
		{
			$redirect_url = admin_url('/');
		}

		return $redirect_url;
	}

	public static function snakeCaseToCamel( $snakeCaseString )
	{
		return lcfirst( str_replace(' ', '', ucwords( str_replace('_', ' ', $snakeCaseString) ) ) );
	}

    public static function showChangelogs ()
    {
        $url = Helper::getOption( 'saas_changelogs_url', false, false );

        if ( ! empty( $url ) )
        {
            Helper::deleteOption( 'saas_changelogs_url', false );

            return $url;
        }

        return $url;
    }

}
