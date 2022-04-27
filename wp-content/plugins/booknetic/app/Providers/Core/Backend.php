<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Boostore\Helpers\BoostoreHelper;
use BookneticApp\Backend\Settings\Helpers\LocalizationService;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Curl;
use BookneticSaaS\Providers\Helpers\Helper as SaasHelper;

class Backend
{

	const MENU_SLUG			= 'booknetic';
	const MODULES_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR;
	const API_URL			= 'https://www.booknetic.com/api/api.php';

	private static $installError = '';

	public static function init()
	{
		Permission::setAsBackEnd();

		self::initAdditionalData( true );

        $updateResult = self::updatePluginDB();

		if ( $updateResult !== true )
        {
            add_action( 'admin_notices', function () use ( $updateResult ) {
                echo '<div class="notice notice-warning"><p>' . $updateResult[ 1 ] . '</p></div>';
            } );

            return;
        }

        if ( Permission::isSuperAdministrator() && ! Route::isAjax() && Route::getCurrentAction() !== 'my_purchases' && ! empty( Helper::getOption( 'migration_v3', false, false ) ) )
        {
            $currentPage = Helper::_get( 'page', '', 'string' );

            if ( Helper::isSaaSVersion() && $currentPage === 'booknetic-saas' )
            {
                Helper::redirect( admin_url( 'admin.php?page=booknetic-saas&module=boostore&action=my_purchases' ) );
            }
            else if ( ! Helper::isSaaSVersion() && $currentPage == self::MENU_SLUG )
            {
                Helper::redirect( admin_url( 'admin.php?page=' . Helper::getSlugName() . '&module=boostore&action=my_purchases' ) );
            }
        }

		if( ! Permission::canUseBooknetic() )
			return;

		add_action( 'admin_menu', function()
		{
			add_menu_page(
				'Booknetic',
				'Booknetic',
				'read',
				self::getSlugName(),
				[ self::class , 'initMenu' ],
				Helper::assets('images/logo-sm.svg'),
				90
			);
		});

		add_action('admin_init', function ()
		{
			$page = Helper::_get('page' , '', 'string');

			if( $page == self::getSlugName() && is_user_logged_in() )
			{
				do_action( 'bkntc_backend' );

				try
				{
					Route::init();
				}
				catch ( \Exception $e )
				{
				    if( $_SERVER['REQUEST_METHOD'] === 'GET')
				    {
                        $childViewFile = Backend::MODULES_DIR . 'Base/view/404.php';
                        $currentModule = 'base';
                        $currentAction = '404';
                        $fullViewPath = Backend::MODULES_DIR . 'Base' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'index' . '.php';
                        require_once $fullViewPath;
                    }
				    else
				    {
                        $errorMessage = $e->getMessage();
                        if( empty( $errorMessage ) )
                        {
                            $errorMessage = bkntc__( 'Page not found or access denied!');
                        }

                        echo json_encode( Helper::response( false, $errorMessage, true) );
                    }
				}

				exit();
			}
			else
			{
				self::initGutenbergBlocks();
				self::initPopupBookingGutenbergBlocks();
			}
		});
	}

	public static function initInstallation()
	{
		self::initAdditionalData( false );

		self::checkInstallationRequest();

		add_action( 'admin_menu', function()
		{
			add_menu_page(
				'Booknetic',
				'Booknetic',
				'read',
				self::getSlugName(),
				array( self::class , 'installationMenu' ),
				Helper::assets('images/logo-sm.svg'),
				90
			);
		});

		if( Helper::_get('page', '', 'string') == self::getSlugName() )
		{
			wp_enqueue_script( 'booknetic-install', Helper::assets('js/install.js'), ['jquery'] );
			wp_enqueue_style('booknetic-install', Helper::assets('css/install.css') );
		}
	}

	private static function checkInstallationRequest()
	{
		add_action( 'wp_ajax_booknetic_install_plugin', function ()
		{
			$purchase_code = Helper::_post('purchase_code', '', 'string');
			$where_find = Helper::_post('where_find', '', 'string');
			$email = Helper::_post('email', '', 'string');

			if( empty( $purchase_code ) || empty( $where_find ) )
			{
				Helper::response(false, 'Please fill the form correctly!');
			}

            set_time_limit( 0 );

			$request = wp_remote_get( self::API_URL . '?act=install&version=' . Helper::getVersion() . '&purchase_code=' . $purchase_code . '&domain=' . site_url() . '&statistic=' . $where_find . '&email=' . $email );

			if ( !is_wp_error( $request ) && isset( $request['response']['code'] ) && $request['response']['code'] == 200 && !empty( $request['body'] ) )
			{
				$result = json_decode( $request['body'], true );

				if( !is_array( $result ) )
				{
					Helper::response(false, 'Installation error! Rosponse error! Response: ' . htmlspecialchars( $request['body'] ));
				}

				if( !($result['status'] == 'ok' && isset($result['migrations'])) )
				{
					Helper::response(false, isset($result['error_msg']) ? $result['error_msg'] : 'Error! Response: ' . htmlspecialchars( $request['body'] ) );
				}

                self::runMigrations( $result['migrations'] );

				register_uninstall_hook( dirname( dirname( dirname( __DIR__ ) ) ) . '/init.php', [ Helper::class, 'uninstallPlugin' ]);

				Helper::setOption( 'plugin_version', Helper::getVersion(), false );
				Helper::setOption( 'purchase_code', $purchase_code, false );

                if ( ! empty( $result[ 'access_token' ] ) )
                {
                    Helper::setOption( 'access_token', $result[ 'access_token' ], false );
                }

				if( isset( $result['saas_url'] ) && !empty( $result['saas_url'] ) )
				{
					$saasInstaller = new PluginInstaller( $result['saas_url'] , '/booknetic-saas/init.php' );

					if( $saasInstaller->install() === false )
					{
						Helper::response(false, bkntc__('An error occurred, please try again later'));
					}

					Helper::setOption('saas_plugin_version', '0.0.0', false);
				}

				Helper::response(true);
			}
			else
			{
				Helper::response(false, bkntc__('An error occurred, please try again later'));
			}
		});
	}

	public static function installationMenu()
	{
		$select_options = [];

		$data = wp_remote_get(self::API_URL . '?act=statistic_option');

		if ( !is_wp_error( $data ) && isset( $data['response']['code'] ) && $data['response']['code'] == 200 && !empty( $data['body'] ) )
		{
			$select_options = json_decode( $data['body'], true );
		}

		$hasError = self::$installError;
		require_once self::MODULES_DIR . 'Base/view/install.php';
	}

	public static function initDisabledPage()
	{
		self::initAdditionalData( false );

		self::checkReActivateAction();

		add_action( 'admin_menu', function()
		{
			add_menu_page(
				'Booknetic (!)',
				'Booknetic (!)',
				'read',
				self::getSlugName(),
				[ self::class , 'disabledMenu' ],
				Helper::assets('images/logo-sm.svg'),
				90
			);
		});

		if( Helper::_get('page', '', 'string') == self::getSlugName() )
		{
			wp_enqueue_script( 'booknetic-disabled', Helper::assets('js/disabled.js'), ['jquery'] );
			wp_enqueue_style('booknetic-disabled', Helper::assets('css/disabled.css') );
		}
	}

	private static function checkReActivateAction()
	{
		add_action( 'wp_ajax_booknetic_reactivate_plugin', function ()
		{
			$code = Helper::_post( 'code', '', 'string' );

			if ( empty( $code ) )
			{
				Helper::response( false, bkntc__( 'Please enter the purchase code!' ) );
			}

			set_time_limit( 0 );

			$check_purchase_code = self::API_URL . '?act=reactivate&version=' . urlencode( Helper::getVersion() ) . '&purchase_code=' . urlencode( $code ) . '&domain=' . urlencode( site_url() );
			$api_result          = Curl::getURL( $check_purchase_code );

			if ( empty( $api_result ) )
			{
				Helper::response( false, bkntc__( 'Your server can not access our license server via CURL! Our license server is "%s". Please contact your hosting provider/server administrator and ask them to solve the problem. If you are sure that problem is not your server/hosting side then contact FS Poster administrators.', [ self::API_URL ] ) );
			}

			$result = json_decode( $api_result, true );

			if ( ! is_array( $result ) )
			{
				Helper::response( false, bkntc__( 'Reactivation failed! Response: %s', [ $api_result ] ) );
			}

			if ( $result[ 'status' ] !== 'ok' )
			{
				Helper::response( false, isset( $result[ 'error_msg' ] ) ? $result[ 'error_msg' ] : bkntc__( 'Error! Response: %s', [ $api_result ] ) );
			}

			Helper::setOption( 'plugin_disabled', '0', false );
			Helper::setOption( 'plugin_alert', '', false );
			Helper::setOption( 'poster_plugin_installed', Helper::getVersion(), false );
			Helper::setOption( 'poster_plugin_purchase_key', $code, false );

			Helper::response( true, [ 'msg' => bkntc__( 'Plugin reactivated!' ) ] );
		});
	}

	public static function disabledMenu()
	{
		$select_options = [];

		require_once self::MODULES_DIR . 'Base/view/disabled.php';
	}

	public static function initMenu()
	{
		return;
	}

	private static function initAdditionalData( $initUpdater )
	{
		if( $initUpdater )
		{
			$purchaseCode = Helper::getOption('purchase_code', null, false);
			$updater = new PluginUpdater( 'booknetic', self::API_URL, $purchaseCode );
		}

		add_filter('plugin_action_links_booknetic/init.php' , function ($links)
		{
			$newLinks = [
				'<a href="https://support.fs-code.com" target="_blank">' . __('Support', 'booknetic') . '</a>',
				'<a href="https://www.booknetic.com/documentation/" target="_blank">' . __('Doc', 'booknetic') . '</a>'
			];

			return array_merge($newLinks, $links);
		});
	}

	private static function updatePluginDB()
	{
		$code = Helper::getOption( 'purchase_code', null, false );

		$installed_version = Helper::getInstalledVersion();
		$current_version = Helper::getVersion();

		if ( $installed_version == $current_version )
		{
			return true;
		}

        ignore_user_abort( true );
        set_time_limit( 0 );

		$result2 = Curl::getURL( self::API_URL . '?act=update&version1=' . $installed_version . '&version2=' . $current_version . '&purchase_code=' . $code . '&domain=' . site_url() );

		$result = json_decode( $result2 , true );

		if( ! is_array( $result ) )
		{
			if( empty( $result2 ) )
			{
				return [ false, bkntc__('Booknetic! Your server can not access our license server via CURL! Our license server is "%s". Please contact your hosting provider/server administrator and ask them to solve the problem. If you are sure that problem is not your server/hosting side then contact Booknetic administrators.' , [ self::API_URL ] ) ];
			}

			return [ false, bkntc__( 'Booknetic! Installation error! Rosponse error! Response: %s' , [ $result2 ] ) ];
		}

		if( !($result['status'] == 'ok' && isset($result['migrations'])) )
		{
			return [ false, ( isset($result['error_msg']) ? $result['error_msg'] : bkntc__('Error! Response: %s', [ $result2 ] ) ) ];
		}

        if (Helper::getOption( 'is_updating', null, false ) !== null)
        {
            return [ false, bkntc__("Booknetic Database update is running, please wait. If this notice doesn't gone in few minutes contact support.")];
        }

        Helper::setOption( 'is_updating', 1, false );

        self::runMigrations( $result[ 'migrations' ] );

		self::restoreLocalizations();

		Helper::setOption( 'plugin_version', Helper::getVersion(), false );

        if ( ! empty( $result[ 'access_token' ] ) )
        {
            Helper::setOption( 'access_token', $result[ 'access_token' ], false );
        }

        if ( ! empty( $result[ 'changelogs_url' ] ) )
        {
            Helper::setOption( 'changelogs_url', $result[ 'changelogs_url' ] . '&' . http_build_query( [ 'purchase_code' => $code, 'domain' => site_url() ] ), false );
        }

        Helper::deleteOption( 'is_updating', false );

		return true;
	}

    public static function updateAddonsDB()
    {
        foreach (Bootstrap::$addons as $addon)
        {
            $slug = $addon->getAddonSlug();
            $currentVersion = $addon->getVersion();
            $versionOnDb = Helper::getOption("addon_{$slug}_version", '0.0.0', false);

            if (version_compare($currentVersion, $versionOnDb, '>'))
            {
                set_time_limit( 0 );

                $migrations = BoostoreHelper::get( 'get_migrations/' . $slug, [
                    'domain' => site_url(),
                    'from' => $versionOnDb,
                    'to' => $currentVersion
                ] );

                self::runMigrations( $migrations );

                Helper::setOption( "addon_{$slug}_version", $currentVersion, false );
            }
        }
    }

    private static function runMigrations ( $migrations )
    {
        $migrationFiles = [];

        foreach ( $migrations as $migrationStep )
        {
            if ( $migrationStep[ 'type' ] === 'sql' )
            {
                $sql = str_replace( [ '{tableprefix}', '{tableprefixbase}' ] , [ DB::DB()->base_prefix . DB::PLUGIN_DB_PREFIX, DB::DB()->base_prefix ] , base64_decode( $migrationStep[ 'data' ] ) );

                foreach( preg_split( '/;\n|;\r/', $sql, -1, PREG_SPLIT_NO_EMPTY ) AS $sqlQueryOne )
                {
                    $sqlQueryOne = trim( $sqlQueryOne );

                    if ( empty( $sqlQueryOne ) ) continue;

                    DB::DB()->query( $sqlQueryOne );
                }
            }
            else if  ( $migrationStep[ 'type' ] === 'script' )
            {
                $migrationFile  = base64_decode( $migrationStep[ 'data' ] );
                $fileName       = __DIR__ . DIRECTORY_SEPARATOR . 'bkntc_migration_' . time() . '_' . count( $migrationFiles ) . '.php';

                $migrationFiles[] = $fileName;

                file_put_contents( $fileName, $migrationFile );

                include $fileName;
            }
        }

        foreach ( $migrationFiles as $migrationFile )
        {
            @unlink( $migrationFile );
        }
    }

	private static function initGutenbergBlocks()
	{
		if( !function_exists('register_block_type') )
			return;

		wp_register_script(
			'booknetic-blocks',
			plugins_url( 'assets/gutenberg-block.js', dirname(dirname(dirname(__DIR__))) . '/init.php' ),
			[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
		);
		wp_localize_script( 'booknetic-blocks', 'BookneticData', [
			'appearances'	    =>	DB::fetchAll('appearance', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'staff'			    =>	DB::fetchAll('staff', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'services'		    =>	DB::fetchAll('services', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'service_categs'	=>	DB::fetchAll('service_categories', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'locations'		    =>	DB::fetchAll('locations', null, null, ['`id` AS `value`', '`name` AS `label`'])
		] );

		register_block_type( 'booknetic/booking' , ['editor_script' => 'booknetic-blocks'] );

        /**
         * Since WordPress 5.8 block_categories filter renamed to block_categories_all
         */
        $filterName = class_exists( 'WP_Block_Editor_Context' ) ? 'block_categories_all' : 'block_categories';

		add_filter( $filterName, function( $categories )
		{
			return array_merge(
				$categories,
				[
					[
						'slug' => 'booknetic',
						'title' => 'Booknetic',
					],
				]
			);
		}, 10, 2);
	}

	private static function initPopupBookingGutenbergBlocks()
	{
		if( !function_exists('register_block_type') )
			return;

		wp_register_script(
			'booknetic-popup-blocks',
			plugins_url( 'assets/popup-booking-gutenberg-block.js', dirname(dirname(dirname(__DIR__))) . '/init.php' ),
			[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ]
		);
		wp_localize_script( 'booknetic-popup-blocks', 'BookneticData', [
			'appearances'	    =>	DB::fetchAll('appearance', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'staff'			    =>	DB::fetchAll('staff', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'services'		    =>	DB::fetchAll('services', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'service_categs'	=>	DB::fetchAll('service_categories', null, null, ['`id` AS `value`', '`name` AS `label`']),
			'locations'		    =>	DB::fetchAll('locations', null, null, ['`id` AS `value`', '`name` AS `label`'])
		] );

		register_block_type( 'booknetic/popup-booking' , ['editor_script' => 'booknetic-popup-blocks'] );

        /**
         * Since WordPress 5.8 block_categories filter renamed to block_categories_all
         */
        $filterName = class_exists( 'WP_Block_Editor_Context' ) ? 'block_categories_all' : 'block_categories';

		add_filter( $filterName, function( $categories )
		{
			return array_merge(
				$categories,
				[
					[
						'slug' => 'booknetic',
						'title' => 'Booknetic',
					],
				]
			);
		}, 10, 2);
	}

	private static function restoreLocalizations()
	{
		$languages = glob( Helper::uploadedFile( 'booknetic_*.lng', 'languages' ) );

		foreach( $languages AS $language )
		{
			if( !preg_match( '/booknetic_([a-zA-Z0-9\-\_]+)\.lng$/', $language, $lang_name ) )
				continue;

			$lang_name = $lang_name[1];

			if( !LocalizationService::isLngCorrect( $lang_name ) )
				continue;

			$translations = file_get_contents( $language );
			$translations = json_decode( base64_decode( $translations ), true );

			if( is_array( $translations ) && !empty( $translations ) )
			{
				LocalizationService::saveFiles( $lang_name, $translations );
			}
		}
	}

	public static function getSlugName()
	{
		return Helper::isSaaSVersion() ? SaasHelper::getOption( 'backend_slug', 'booknetic' ) : self::MENU_SLUG;
	}

}
