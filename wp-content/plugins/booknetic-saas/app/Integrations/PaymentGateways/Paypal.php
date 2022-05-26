<?php

namespace BookneticSaaS\Integrations\PaymentGateways;

use PayPal\Rest\ApiContext;
use BookneticSaaS\Models\Tenant;
use PayPal\Auth\OAuthTokenCredential;
use BookneticApp\Providers\Helpers\Curl;
use BookneticApp\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\Helper;
use PayPal\Api\Payer;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Common\PayPalModel;

class Paypal
{

	private $_paymentId;
	private $_price;
	private $_first_month_price;
	private $_currency;
	private $_itemId;
	private $_itemName;
	private $_payment_cycle;
	private $_itemDescription;
	private $_apiContext;
	private $_successURL;
	private $_cancelURL;

	public static function webhookUrl()
	{
		return site_url() . '/?booknetic_saas_action=paypal_webhook';
	}

	public function __construct()
	{
		$clientId		= Helper::getOption( 'paypal_client_id', null );
		$clientSecret	= Helper::getOption( 'paypal_client_secret', null );
		$mode			= Helper::getOption( 'paypal_mode', null ) == 'live' ? 'live' : 'sandbox';

		$this->_apiContext = new ApiContext(
			new OAuthTokenCredential( $clientId , $clientSecret )
		);

		$this->_apiContext->setConfig([ 'mode' => $mode ]);
	}

	public function setId( $paymentId )
	{
		$this->_paymentId = $paymentId;

		return $this;
	}

	public function setAmount( $price, $first_month_price , $currency = 'USD' )
	{
		$this->_price = $price;
		$this->_first_month_price = $first_month_price;
		$this->_currency = $currency;

		return $this;
	}

	public function setItem( $itemId , $itemName , $itemDescription )
	{
		$this->_itemId = $itemId;
		$this->_itemName = $itemName;
		$this->_itemDescription = $itemDescription;

		return $this;
	}

	public function setSuccessURL( $url )
	{
		$this->_successURL = $url;

		return $this;
	}

	public function setCancelURL( $url )
	{
		$this->_cancelURL = $url;

		return $this;
	}

	public function setCycle( $cycle )
	{
		$this->_payment_cycle = ($cycle == 'monthly' ? 'MONTH' : 'YEAR');

		return $this;
	}

	public function createRecurringPayment()
	{
		$plan = new Plan();

		$plan->setName( $this->_itemName )
			->setDescription( Helper::price( $this->_first_month_price, $this->_currency ) . ' / ' . $this->_payment_cycle )
			->setType( 'INFINITE' );

		$paymentDefinition = new PaymentDefinition();
		$paymentDefinition->setName( $this->_itemName )
			->setType( 'REGULAR' )
			->setFrequency( $this->_payment_cycle )
			->setFrequencyInterval( "1" )
			->setCycles( 0 )
			->setAmount( new Currency( ['value' => $this->_price, 'currency' => $this->_currency] ) );

		$merchantPreferences = new MerchantPreferences();

		$merchantPreferences->setReturnUrl( $this->_successURL )
			->setCancelUrl( $this->_cancelURL )
			->setAutoBillAmount( "yes" )
			->setInitialFailAmountAction( "CONTINUE" )
			->setMaxFailAttempts( "0" )
			->setSetupFee( new Currency( ['value' => $this->_first_month_price, 'currency' => $this->_currency] ) );

		$plan->setPaymentDefinitions([ $paymentDefinition ]);
		$plan->setMerchantPreferences( $merchantPreferences );

		try
		{
			$output = $plan->create( $this->_apiContext );
		}
		catch (\Exception $ex)
		{
			return [
				'status'	=> false,
				'error'		=> 'Colud\'t create the billing plan! ' . htmlspecialchars($ex->getMessage())
			];
		}

		$activatePlan = $this->activatePlan( $output );
		if( !$activatePlan[0] )
		{
			return [
				'status'	=> false,
				'error'		=> 'Colud\'t activate the billing plan! '
			];
		}

		$planId = $output->getId();

		$agreement = $this->createAgreement( $planId );

		if( !$agreement[0] )
		{
			return [
				'status'	=> false,
				'error'		=> 'Colud\'t create agreement! ' . ( isset( $agreement[1] ) ? htmlspecialchars($agreement[1]) : '' )
			];
		}

		return [
			'status'	=> true,
			'url'		=> $agreement[1]
		];
	}

	public function executeAgreement( $token )
	{
		$agreement = new \PayPal\Api\Agreement();
		try
		{
			$result = $agreement->execute($token, $this->_apiContext);

			$id = $agreement->getId();
			$desc = $result->description;
			preg_match( '/\#([0-9]+)$/' , $desc , $idFromDesc );

			if( $result->state == 'Active' && $idFromDesc[1] == $this->_paymentId )
			{
				return [
					'status'    => true ,
					'id'        => $id
				];
			}
			else
			{
				return ['status' => false , 'message' => 'not_approved'];
			}
		}
		catch (\Exception $ex)
		{
			return [
				'status'    => false,
				'message'   => $ex->getMessage()
			];
		}
	}

	public function cancelSubscription( $agreementId )
	{
		$agreementStateDescriptor = new AgreementStateDescriptor();
		$agreementStateDescriptor->setNote("Suspending the agreement");

		try
		{
			$agreement = Agreement::get( $agreementId , $this->_apiContext );
			$agreement->suspend( $agreementStateDescriptor , $this->_apiContext );
		}
		catch (\Exception $e)
		{
			return [
				'status'    =>  false,
				'error'     =>  $e->getMessage()
			];
		}

		return [
			'status'    =>  true
		];
	}

	public function webhook()
	{
		$requestBody    = file_get_contents( 'php://input' );
		$headers        = getallheaders();
		$headers        = array_change_key_case( $headers, CASE_UPPER );

		$validateParams = ['PAYPAL-AUTH-ALGO', 'PAYPAL-TRANSMISSION-ID', 'PAYPAL-CERT-URL', 'PAYPAL-TRANSMISSION-SIG', 'PAYPAL-TRANSMISSION-TIME'];

		foreach ( $validateParams AS $param )
		{
			if( empty( $headers[ $param ] ) )
			{
				return;
			}
		}

		$signatureVerification = new VerifyWebhookSignature();

		$signatureVerification->setAuthAlgo( $headers['PAYPAL-AUTH-ALGO'] );
		$signatureVerification->setTransmissionId( $headers['PAYPAL-TRANSMISSION-ID'] );
		$signatureVerification->setCertUrl( $headers['PAYPAL-CERT-URL'] );
		$signatureVerification->setWebhookId( Helper::getOption( 'paypal_webhook_id', '' ) );
		$signatureVerification->setTransmissionSig( $headers['PAYPAL-TRANSMISSION-SIG'] );
		$signatureVerification->setTransmissionTime( $headers['PAYPAL-TRANSMISSION-TIME'] );

		$signatureVerification->setRequestBody( $requestBody );
		$request = clone $signatureVerification;

		try
		{
			$output = $signatureVerification->post( $this->_apiContext );
		}
		catch (\Exception $ex)
		{
			http_response_code( 400 );
			exit();
		}

		if( $output->getVerificationStatus() !== 'SUCCESS' )
		{
			http_response_code( 400 );
			exit();
		}

		$requestData = json_decode( $requestBody, true );

		$eventType  = $requestData['event_type'];
		$agreementId  = $requestData['resource']['billing_agreement_id'];

		if( $eventType === 'PAYMENT.SALE.COMPLETED' )
		{
			if( !Tenant::paymentSucceded( $agreementId ) )
			{
				http_response_code( 400 );
				exit();
			}
		}
		else if( $eventType === 'BILLING.SUBSCRIPTION.CANCELLED' )
		{
			Tenant::unsubscribed( $agreementId );
		}

		http_response_code( 200 );
		exit();
	}


	private function activatePlan( $plan )
	{
		try
		{
			$patch = new Patch();
			$value = new PayPalModel( '{"state": "ACTIVE"}' );

			$patch->setOp( 'replace' )
				->setPath( '/' )
				->setValue( $value );

			$patchRequest = new PatchRequest();
			$patchRequest->addPatch( $patch );

			$plan->update( $patchRequest, $this->_apiContext );
		}
		catch (\Exception $ex)
		{
			return [false , $ex->getMessage()];
		}

		return [true];
	}

	private function createAgreement( $planId )
	{
		$agreement = new Agreement();

		$description = $this->_itemName . ' - ' . Helper::price( $this->_price, $this->_currency );

		if( $this->_first_month_price != $this->_price )
		{
			$description .= ' ( ' . bkntcsaas__( 'first month %s', Helper::price( $this->_first_month_price, $this->_currency ) ) . ' )';
		}

		$description .= ' #' . $this->_paymentId;

		$agreement->setName($this->_itemName . ' - ' . $this->_payment_cycle . ' #' . $this->_paymentId)
			->setDescription( $description )
			->setStartDate( $this->agreemntStartDate() );

		$plan = new Plan();
		$plan->setId( $planId );
		$agreement->setPlan( $plan );

		$payer = new Payer();
		$payer->setPaymentMethod( 'paypal' );
		$agreement->setPayer( $payer );

		try
		{
			$agreement = $agreement->create( $this->_apiContext );
			$approvalUrl = $agreement->getApprovalLink();
		}
		catch(\Exception $e)
		{
			return [false , $e->getMessage()];
		}

		return [true , $approvalUrl];
	}

	private function agreemntStartDate()
	{
		$addTime = '+1 ' . strtolower( $this->_payment_cycle );

		$tryToGetWithApi = Curl::getURL('https://www.booknetic.com/api/time.php');
		if( is_numeric( $tryToGetWithApi ) && strlen( (string)$tryToGetWithApi ) == 10 )
		{
			return Date::format( 'Y-m-d\TH:i:s\Z', $tryToGetWithApi, $addTime );
		}

		return Date::UTCDateTime( $addTime, 'Y-m-d\TH:i:s\Z' );
	}


}