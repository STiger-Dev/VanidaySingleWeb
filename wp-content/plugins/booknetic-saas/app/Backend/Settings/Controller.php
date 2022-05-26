<?php

namespace BookneticSaaS\Backend\Settings;

use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\SettingsMenuUI;

class Controller extends \BookneticApp\Providers\Core\Controller
{

	public function index()
	{
        SettingsMenuUI::get( 'general_settings' )
                      ->setPriority( 1 )
                      ->setTitle( bkntcsaas__('General settings') )
                      ->setDescription( bkntcsaas__('You can customize general settings about booking from here') )
                      ->setIcon( Helper::icon('general-settings.svg', 'Settings') );

        SettingsMenuUI::get( 'whitelabel_settings' )
                      ->setPriority( 2 )
                      ->setTitle( bkntcsaas__('White Label settings') )
                      ->setDescription( bkntcsaas__('You can change your business details from here') )
                      ->setIcon( Helper::icon('whitelabel-settings.svg', 'Settings') );

        SettingsMenuUI::get( 'payment_settings' )
                  ->setTitle( bkntcsaas__('Payment settings') )
                  ->setDescription( bkntcsaas__('Currency, price format , general settings about payment , paypal, stripe and so on') )
                  ->setIcon( Helper::icon('payments-settings.svg', 'Settings') )
                  ->setPriority( 3 );

        SettingsMenuUI::get( 'payment_settings' )
                      ->subItem( 'payments_settings' )
                      ->setTitle( bkntcsaas__( 'General' ) )
                      ->setPriority( 1 );

        SettingsMenuUI::get( 'email_settings' )
            ->setPriority( 4 )
            ->setTitle( bkntcsaas__('Email settings') )
            ->setDescription( bkntcsaas__('You must set this settings for email notifications ( wp_mail or SMTP settings )') )
            ->setIcon( Helper::icon('email-settings.svg', 'Settings') );

        SettingsMenuUI::get( 'payment_settings' )
                      ->subItem( 'payment_gateways_settings' )
                      ->setTitle( bkntcsaas__( 'Payment methods' ) )
                      ->setPriority( 2 );

        SettingsMenuUI::get( 'integrations' )
                      ->setTitle( bkntcsaas__('Integrations settings') )
                      ->setDescription( bkntcsaas__('You can change settings for integrated services from here.') )
                      ->setIcon( Helper::icon('integrations-settings.svg', 'Settings') )
                      ->setPriority( 4 );

        SettingsMenuUI::get( 'integrations' )
                      ->subItem( 'integrations_facebook_api_settings' )
                      ->setTitle( bkntcsaas__( 'Continue with Facebook' ) )
                      ->setPriority( 1 );

        SettingsMenuUI::get( 'integrations' )
                      ->subItem( 'integrations_google_login_settings' )
                      ->setTitle( bkntcsaas__( 'Continue with Google' ) )
                      ->setPriority( 2 );

        $this->view( 'index', [
            'menu' => SettingsMenuUI::getItems()
        ] );
	}

}
