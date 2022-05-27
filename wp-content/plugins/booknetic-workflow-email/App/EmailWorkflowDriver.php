<?php

namespace BookneticAddon\EmailWorkflow;

use Booknetic_PHPMailer\PHPMailer\PHPMailer;
use BookneticApp\Models\WorkflowLog;
use BookneticApp\Providers\Common\WorkflowDriver;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Curl;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Date;
use function BookneticAddon\EmailWorkflow\bkntc__;

class EmailWorkflowDriver extends WorkflowDriver
{

	protected $driver = 'email';

	public static $cacheFiles = [];

	public function __construct()
	{
		$this->setName( bkntc__('Send Email') );
		$this->setEditAction( 'email_workflow', 'workflow_action_edit_view' );
	}

	public function __destruct()
	{
		foreach ( static::$cacheFiles AS $cacheFile )
		{
			unlink( $cacheFile );
		}
	}

	public function handle( $eventData, $actionSettings, $shortCodeService )
	{
        if ( empty( $actionSettings ) )
        {
            return;
        }
    
		$sendTo         = $shortCodeService->replace( $actionSettings['to'], $eventData );
		$subject        = $shortCodeService->replace( $actionSettings['subject'], $eventData );
		$body           = $shortCodeService->replace( $actionSettings['body'], $eventData );
		$attachments    = $shortCodeService->replace( $actionSettings['attachments'], $eventData );
		$attachmentsArr = [];

		$allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'gif', 'png', 'bmp', 'xls', 'xlsx', 'csv', 'zip', 'rar'];

		if( !empty( $attachments ) )
		{
			$attachments = explode(',', $attachments);
			foreach ( $attachments AS $attachment )
			{
				$attachment = trim( $attachment );

				if( file_exists( $attachment ) && is_readable( $attachment ) )
				{
					$extension = strtolower( pathinfo( $attachment, PATHINFO_EXTENSION ) );
					if( in_array( $extension, $allowedExtensions ) )
					{
						$attachmentsArr[] = $attachment;
					}
				}
				else if( filter_var( $attachment, FILTER_VALIDATE_URL ) )
				{
					$fileName = preg_replace( '[^a-zA-Z0-9\-\_\(\)]','', basename( $attachment ) );
					if ( empty( $fileName ) )
					{
						$fileName = uniqid();
					}

					$extension = strtolower( pathinfo( $attachment, PATHINFO_EXTENSION ) );
					if( ! in_array( $extension, $allowedExtensions ) )
					{
						$extension = 'tmp';
					}

					$fileName .= '.' . $extension;

					$cacheFilePath = Helper::uploadFolder('tmp') . $fileName;

					file_put_contents( $cacheFilePath, Curl::getURL( $attachment ) );

					$attachmentsArr[] = $cacheFilePath;

					static::$cacheFiles[] = $cacheFilePath;
				}
			}
		}

		if( ! empty( $sendTo ) )
		{
			$sendToArr = explode( ',', $sendTo );
			foreach ( $sendToArr AS $sendTo )
			{
                $this->send( trim( $sendTo ) , strip_tags( htmlspecialchars_decode(  str_replace('&nbsp;' ,' ' ,$subject ) ) ) , $body , $attachmentsArr );
			}
		}
	}

	public function send( $sendTo, $subject, $body, $attachments = [] )
	{
		if( empty( $sendTo ) )
			return false;

		$logCount = $this->getUsage();
		if( Capabilities::getLimit( 'email_allowed_max_number' ) <= $logCount && Capabilities::getLimit( 'email_allowed_max_number' ) > -1 )
		{
			return false;
		}

		$mailGateway	= Helper::getOption('mail_gateway', 'wp_mail', false);
		$senderEmail	= Helper::getOption('sender_email', '', false);
		$senderName		= Helper::getOption('sender_name', '', false);

		if( Capabilities::tenantCan('email_settings') )
		{
			$tenantSenderName = Helper::getOption('sender_name', '');
			if( !empty( $tenantSenderName ) )
			{
				$senderName = $tenantSenderName;
			}
		}

		$headers = 'From: ' . $senderName . ' <' . $senderEmail . '>' . "\r\n" .
		           "Content-Type: text/html; charset=UTF-8\r\n";

		if( $mailGateway == 'wp_mail' )
		{
			wp_mail( $sendTo, $subject, $body, $headers, $attachments );
		}
		else // SMTP
		{
			$mail = new PHPMailer();

			$mail->isSMTP();

			$mail->Host			= Helper::getOption('smtp_hostname', '', false);
			$mail->Port			= Helper::getOption('smtp_port', '', false);
			$mail->SMTPSecure	= Helper::getOption('smtp_secure', '', false);
			$mail->SMTPAuth		= true;
			$mail->Username		= Helper::getOption('smtp_username', '', false);
			$mail->Password		= Helper::getOption('smtp_password', '', false);

			$mail->setFrom( $senderEmail, $senderName );
			$mail->addAddress( $sendTo );

			$mail->Subject		= $subject;
			$mail->Body			= $body;

			$mail->IsHTML(true);
			$mail->CharSet = 'UTF-8';

			foreach ( $attachments AS $attachment )
			{
				$mail->AddAttachment( $attachment, basename( $attachment ) );
			}

			$mail->send();
		}

        WorkflowLog::insert([
            'driver'    =>  $this->getDriver(),
            'date_time' =>  Date::dateTimeSQL()
        ]);

        return true;
	}

    public function getUsage()
    {
        $startDateToCheck = Date::format( 'Y-m-01 00:00' );
        $endDateToCheck = Date::format( 'Y-m-t 23:59:59' );
        return  WorkflowLog::where( 'driver', $this->getDriver() )
            ->where( 'date_time', 'BETWEEN', DB::field( "'{$startDateToCheck}' AND '{$endDateToCheck}'" ) )
            ->count();
    }
}
