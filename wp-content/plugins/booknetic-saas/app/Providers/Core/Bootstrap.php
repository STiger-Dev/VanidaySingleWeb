<?php

namespace
{
	/**
	 * @param $text
	 * @param array $params
	 * @param bool $esc
	 * @return mixed
	 */
	function bkntcsaas__( $text, $params = [], $esc = true )
	{
		if( empty( $params ) )
		{
            $result = __($text, 'booknetic-saas');
		}
		else
		{
			$args = array_merge( [ __($text, 'booknetic-saas') ] , (array)$params );
            $result = call_user_func_array('sprintf', $args );
		}

        return $esc ? htmlspecialchars($result) : $result;
	}
}

namespace BookneticSaaS\Providers\Core
{

    use BookneticSaaS\Config;
	use BookneticSaaS\Providers\Helpers\Helper;

	/**
	 * Class Bootstrap
	 * @package BookneticSaaS
	 */
	class Bootstrap
	{

		/**
		 * Bootstrap constructor.
		 */
		public function __construct()
		{
            Config::registerTextDomain();

            if ( Helper::getOption( 'saas_is_updating' ) !== null )
            {
                add_action( 'admin_notices', function () {
                    echo '<div class="notice notice-warning"><p>' . bkntcsaas__( 'Booknetic SaaS is updating, please wait.' ) . '</p></div>';
                } );

                return;
            }

            if ( version_compare( Helper::getOption( 'plugin_version', '1.0.0' ), '3.0.0', '<' ) )
            {
                add_action( 'admin_notices', function ()
                {
                    echo '<div class="notice notice-warning"><p>' . bkntcsaas__( 'Booknetic SaaS require Booknetic 3.0.0 or later to be installed, please update to latest version.' ) .  '</p></div>';
                } );

                return;
            }

            if( $this->isInstalled() )
			{
				if ( $this->checkLicense() === false )
				{
					add_action('init', [$this, 'initDisabledPage']);
				}
				else
				{
					add_action('init', [$this, 'initApp'], 9);
				}
			}
		}

		public function initApp()
		{
            if (!class_exists(\BookneticApp\Config::class))
            {
                add_action( 'admin_notices', function () {
                    echo '<div class="notice notice-warning"><p>' . bkntcsaas__( 'Booknetic SaaS requires Booknetic plugin to be installed and activated.' ) . '</p></div>';
                } );

                return;
            }

            Config::init();

            do_action( 'bkntcsaas_init' );

			if ( !is_admin() || ( $this->isAjax() && !$this->isUpdateProcess() ) )
			{
				Frontend::init();
			}
			else if( is_admin() && Permission::canUseBooknetic() )
			{
				Backend::init();
			}
		}

		public function initDisabledPage()
		{
			if( is_admin() )
			{
				Backend::initDisabledPage();
			}
		}

		private function isAjax()
		{
			return defined('DOING_AJAX') && DOING_AJAX;
		}

		private function isUpdateProcess()
		{
			return Helper::_post('action', '', 'string') == 'update-plugin';
		}

		private function isInstalled()
		{
			$purchase_code = Helper::getOption('purchase_code', '');
			$version = Helper::getOption('saas_plugin_version', '');

			if( empty( $purchase_code ) || empty( $version ) )
				return false;

			return true;
		}

		private function checkLicense()
		{
			$alert    = Helper::getOption( 'plugin_alert', '' );
			$disabled = Helper::getOption( 'plugin_disabled', '0' );

			if ( $disabled === '1' )
			{
				return false;
			}
			else if ( $disabled === '2' )
			{
				if ( ! empty( $alert ) )
				{
					echo $alert;
				}

				exit();
			}

			return true;
		}

	}

}


