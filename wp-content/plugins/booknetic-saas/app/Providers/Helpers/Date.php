<?php

namespace BookneticSaaS\Providers\Helpers;


class Date extends \BookneticApp\Providers\Helpers\Date
{

	public static function getTimeZoneStringAndOffset()
	{
		$tz_string = get_option( 'timezone_string' );
		$tz_offset = get_option( 'gmt_offset', 0 );

		return [ $tz_string, $tz_offset ];
	}

}
