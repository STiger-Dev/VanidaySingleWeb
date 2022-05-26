<?php

namespace BookneticSaaS\Providers\Core;

use BookneticSaaS\Config;
use BookneticApp\Providers\Helpers\Curl;
use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Providers\Core\PluginUpdater;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\Core\Permission;

class Backend
{

	const MENU_SLUG			= 'booknetic-saas';
	const MODULES_DIR		= __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Backend' . DIRECTORY_SEPARATOR;
	const API_URL			= 'https://www.booknetic.com/api/saas/api.php';

	private static $installError = '';

    public static function init()
	{
		Permission::setAsBackEnd();

		self::initAdditionalData( true );

        $updateResult = self::updatePluginDB();
        if( $updateResult !== true )
        {
            add_action('admin_notices', function () use ($updateResult) {
                echo '<div class="notice notice-warning"><p>' . $updateResult[1] . '</p></div>';
            });
            return;
        }

		add_action( 'admin_menu', function()
		{
			add_menu_page(
				'Booknetic SaaS',
				'Booknetic SaaS',
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
				do_action( 'bkntcsaas_backend' );

				try
				{
					Route::init();
				}
				catch ( \Exception $e )
				{
					$errorMessage = $e->getMessage();
					if( empty( $errorMessage ) )
					{
						$errorMessage = bkntcsaas__( 'Page not found or access denied!');
					}

					echo json_encode( Helper::response( false, $errorMessage, true) );
				}

				exit();
			}
			else
			{
				self::initGutenbergBlocks();
			}
		});
	}

	public static function initDisabledPage()
	{
		self::initAdditionalData( false );

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

	public static function disabledMenu()
	{
		$select_options = [];

		require_once self::MODULES_DIR . 'Base/view/disabled.php';
	}

    public static function getSlugName()
    {
        return self::MENU_SLUG;
    }

	public static function initMenu()
	{
		return;
	}

	private static function initAdditionalData( $initUpdater )
	{
		if( $initUpdater )
		{
			$purchaseCode = Helper::getOption( 'purchase_code' );
			$updater = new PluginUpdater( self::getSlugName(), self::API_URL, $purchaseCode );
		}

		add_filter('plugin_action_links_booknetic-saas/init.php' , function ($links)
		{
			$newLinks = [
				'<a href="https://support.fs-code.com" target="_blank">' . __('Support', 'booknetic-saas') . '</a>',
				'<a href="https://www.booknetic.com/documentation/" target="_blank">' . __('Doc', 'booknetic-saas') . '</a>'
			];

			return array_merge($newLinks, $links);
		});
	}

	private static function updatePluginDB()
	{
		$code = Helper::getOption('purchase_code');
		$installed_version = Helper::getInstalledVersion();
		$current_version = Helper::getVersion();

		if($installed_version == $current_version)
		{
			return true;
		}

        ignore_user_abort( true );
        set_time_limit( 0 );

		$result2 = Curl::getURL( self::API_URL . '?act=update&version1=' . $installed_version . '&version2=' . $current_version . '&purchase_code=' . $code . '&domain=' . site_url() );

		$result = json_decode( $result2 , true );

		if( !is_array( $result ) )
		{
			if( empty( $result2 ) )
			{
				return [ false, bkntcsaas__('Booknetic! Your server can not access our license server via CURL! Our license server is "%s". Please contact your hosting provider/server administrator and ask them to solve the problem. If you are sure that problem is not your server/hosting side then contact Booknetic administrators.' , [ self::API_URL ] ) ];
			}

			return [ false, bkntcsaas__( 'Booknetic! Installation error! Response error! Response: %s' , [ $result2 ] ) ];
		}

		if( !($result['status'] == 'ok' && isset($result['migrations'])) )
		{
			return [ false, ( isset($result['error_msg']) ? $result['error_msg'] : bkntcsaas__('Error! Response: %s', [ $result2 ] ) ) ];
		}

        if ( Helper::getOption( 'saas_is_updating' ) !== null )
        {
            return [ false, bkntc__("Booknetic Database update is running, please wait. If this notice doesn't gone in few minutes contact support.")];
        }

        Helper::setOption( 'saas_is_updating', 1 );

        self::runMigrations( $result[ 'migrations' ] );

		Helper::setOption( 'saas_plugin_version', Helper::getVersion() );
        Helper::deleteOption( 'saas_is_updating', 0 );

		return true;
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

		register_block_type( 'booknetic/booking' , ['editor_script' => 'booknetic-blocks'] );
		register_block_type( 'booknetic/cabinet' , ['editor_script' => 'booknetic-blocks'] );
		register_block_type( 'booknetic/signin' , ['editor_script' => 'booknetic-blocks'] );
		register_block_type( 'booknetic/signup' , ['editor_script' => 'booknetic-blocks'] );
		register_block_type( 'booknetic/forgot-password' , ['editor_script' => 'booknetic-blocks'] );

        /**
         * Since WordPress 5.8 block_categories filter renamed to block_categories_all
         */
        $filterName = class_exists( 'WP_Block_Editor_Context' ) ? 'block_categories_all' : 'block_categories';

		add_filter( $filterName, function( $categories )
		{
			return array_merge(
				$categories, [[
					'slug' => 'booknetic',
					'title' => 'Booknetic'
				]]
			);
		}, 10, 2);
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

}

