<?php

namespace BookneticSaaS\Providers\Common;

use BookneticSaaS\Backend\Tenants\Helpers\TenantSmartObject;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Models\TenantCustomData;
use BookneticSaaS\Models\TenantFormInput;
use BookneticSaaS\Models\TenantFormInputChoice;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;

class ShortCodeServiceImpl
{
    public static function replace( $text, $data )
    {
        if ( !empty($data['tenant_id']) )
        {
            $tenantId = $data['tenant_id'];
            $tenantData = TenantSmartObject::load( $data['tenant_id'] );
            $activeBillingInfo = TenantBilling::noTenant()->where( 'agreement_id', $tenantData->getInfo()->active_subscription )->fetch();

            $text = str_replace( [
                '{tenant_id}',
                '{tenant_full_name}',
                '{tenant_email}',
                '{tenant_registration_date}',
                '{subscription_expires_in}',
                '{plan_id}',
                '{plan_name}',
                '{plan_color}',
                '{plan_description}',
                '{payment_amount}',
                '{payment_method}',
                '{payment_cycle}',
                '{url_to_complete_signup}',
                '{url_to_reset_password}',
                '{tenant_domain}',
                '{company_name}',
                '{company_address}',
                '{company_phone_number}',
                '{company_website}',
                '{company_image_url}'
            ], [
                $tenantData->getInfo()->id,
                $tenantData->getInfo()->full_name,
                $tenantData->getInfo()->email,
                Date::dateTime( $tenantData->getInfo()->inserted_at ),
                Date::datee( $tenantData->getInfo()->expires_in ),
                (int) $tenantData->getInfo()->plan_id,
                $tenantData->getPlanInfo() ? htmlspecialchars($tenantData->getPlanInfo()->name) : '',
                $tenantData->getPlanInfo() ? htmlspecialchars($tenantData->getPlanInfo()->color) : '',
                $tenantData->getPlanInfo() ? $tenantData->getPlanInfo()->description : '',
                $activeBillingInfo ? Helper::price( $activeBillingInfo->amount ) : '',
                $activeBillingInfo ? ( $activeBillingInfo->payment_method == 'paypal' ? bkntcsaas__('Paypal') : bkntcsaas__('Credit card') ) : '',
                $activeBillingInfo ? ( $activeBillingInfo->payment_cycle == 'monthly' ? bkntcsaas__('Monthly') : bkntcsaas__('Annually') ) : '',
                Helper::addUrlParameter( get_page_link( Helper::getOption('sign_up_page', '') ), 'activation_token=' . urlencode( $tenantData->getInfo()->activation_token ) ),
                Helper::addUrlParameter( get_page_link( Helper::getOption('forgot_password_page', '') ), 'token=' . urlencode( $tenantData->getInfo()->activation_token ) ),
                $tenantData->getInfo()->domain,
                Helper::getOption('company_name', '', $tenantId),
                Helper::getOption('company_address', '', $tenantId),
                Helper::getOption('company_phone', '', $tenantId),
                Helper::getOption('company_website', '', $tenantId),
                \BookneticApp\Providers\Helpers\Helper::profileImage( Helper::getOption('company_image', ''), 'Settings')
            ], $text );

            $text = preg_replace_callback('/{tenant_custom_field_([0-9]+)}/', function ( $found ) use ($data)
            {
                $formInput = TenantFormInput::get($found[1]);
                if (!$formInput)
                    return $found[0];

                $cdata = TenantCustomData::where('tenant_id', $data['tenant_id'])
                    ->where('form_input_id', $formInput->id)
                    ->fetch();

                if (!$cdata)
                    return $found[0];

                if (in_array($formInput->type, ['select', 'checkbox', 'radio']))
                {
                    $uiValues = TenantFormInputChoice::where('id', explode(',', $cdata->input_value))
                        ->fetchAll();

                    $uiValues = array_map(function ($row) {
                        return $row->title;
                    }, $uiValues);

                    return implode(',', $uiValues);
                }

                if ($formInput->type === 'file')
                {
                    return $found[0];
                }

                return $cdata->input_value;

            }, $text);

            $text = preg_replace_callback('/{tenant_custom_field_([0-9]+)_(url|path|name)}/', function ( $found ) use ($data)
            {
                $formInput = TenantFormInput::get($found[1]);
                if (!$formInput)
                    return $found[0];

                $cdata = TenantCustomData::where('tenant_id', $data['tenant_id'])
                    ->where('form_input_id', $formInput->id)
                    ->fetch();

                if (!$cdata)
                    return $found[0];

                if ($formInput->type === 'file')
                {
                    if ($found[2] === 'url')
                        return Helper::uploadedFileURL( $cdata->input_value, 'TenantCustomForms' );

                    if ($found[2] === 'path')
                        return Helper::uploadedFile( $cdata->input_value, 'TenantCustomForms' );

                    if ($found[2] === 'name')
                        return $cdata->input_file_name;
                }

                return $found[0];
            }, $text);

        }

        if ( !empty($data['tenant_password']) )
        {
            $text = str_replace('{tenant_password}', $data['tenant_password'], $text);
        }

        return $text;
    }
}
