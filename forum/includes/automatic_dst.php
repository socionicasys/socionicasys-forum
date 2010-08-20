<?php

/**
*
* @package - "Automatic Daylight Savings Time 2"
* @version $Id: automatic_dst.php 9 2009-11-18 MartectX $
* @copyright (C)2008-2009, MartectX ( http://mods.martectx.de/ )
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

function automatic_dst_session()
{
	if (!defined('AUTOMATIC_DST_TIMEZONE'))
	{
		global $config, $db, $user;

		$user->add_lang('mods/automatic_dst');

		if ($user->data['user_id'] != ANONYMOUS)
		{
			if (is_numeric($user->data['user_timezone']))
			{
				// Time zone not yet converted, so lets temporarily do just that -  if there's no entry to convert to reset to board time
				$timetable = automatic_dst_get_timetable();
				$user->data['user_timezone'] = ($timetable[$user->data['user_timezone']]) ? $timetable[$user->data['user_timezone']] : AUTOMATIC_DST_BOARD_TIMEZONE;
			}

			if ($user->data['user_timezone'] != AUTOMATIC_DST_BOARD_TIMEZONE)
			{
				if (version_compare(PHP_VERSION, '5.2.0', '>'))
				{
					date_default_timezone_set($user->data['user_timezone']);
					$january = new DateTime(date('Y') . '-01-01 12:00:00');
					$july = new DateTime(date('Y') . '-07-01 12:00:00');
					$offset = ($january->getOffset() < $july->getOffset()) ? $january->getOffset() / 3600 : $july->getOffset() / 3600;
				}
				else
				{
					putenv('TZ=' . $user->data['user_timezone']);
					$offset = (date('Z', mktime(0, 0, 0, 01, 01)) < date('Z', mktime(0, 0, 0, 07, 01))) ? date('Z', mktime(0, 0, 0, 01, 01)) / 3600 : date('Z', mktime(0, 0, 0, 07, 01)) / 3600;
				}
			}
			else
			{
				$offset = $config['board_timezone'];
			}

			define('AUTOMATIC_DST_TIMEZONE', $user->data['user_timezone']);
			define('AUTOMATIC_DST_ISDST', date('I'));

			// Set all variables you can think of to the automatically determined - you never know which ones may be used by keen MOD authors... ;-)
			$user->data['user_timezone'] = $offset;
			$user->data['user_dst'] = AUTOMATIC_DST_ISDST;

			$user->timezone = $offset * 3600;
			$user->dst = AUTOMATIC_DST_ISDST * 3600;
		}
		else
		{
			if ($user->data['user_timezone'] != AUTOMATIC_DST_BOARD_TIMEZONE)
			{
				// Set the time zone of the anonymous user to the board's time zone
				$sql = 'UPDATE ' . USERS_TABLE . "
					SET user_timezone = '" . AUTOMATIC_DST_BOARD_TIMEZONE . "'
					WHERE user_id = " . ANONYMOUS;
				$db->sql_query($sql);

				$user->data['user_timezone'] = AUTOMATIC_DST_BOARD_TIMEZONE;
			}

			define('AUTOMATIC_DST_TIMEZONE', AUTOMATIC_DST_BOARD_TIMEZONE);
			define('AUTOMATIC_DST_ISDST', AUTOMATIC_DST_BOARD_ISDST);

			// Set all variables you can think of to the automatically determined - you never know which ones may be used by keen MOD authors... ;-)
			$user->data['user_timezone'] = $config['board_timezone'];
			$user->data['user_dst'] = AUTOMATIC_DST_ISDST;

			$user->timezone = $config['board_timezone'] * 3600;
			$user->dst = AUTOMATIC_DST_ISDST * 3600;
		}
	}
}

function automatic_dst_cache($timezone)
{
	if (is_numeric($timezone))
	{
		// Time zone not yet converted, so lets temporarily do just that -  if there's no entry to convert to reset to GMT
		$timetable = automatic_dst_get_timetable();
		$timezone = number_format($timezone, 2);
		$timezone = ($timetable[$timezone]) ? $timetable[$timezone] : 'Europe/London';

		// It's important for the converter that this time zone is temporary, so lets tell it
		define('AUTOMATIC_DST_TEMP_TIMEZONE', TRUE);
	}

	if (version_compare(PHP_VERSION, '5.2.0', '>'))
	{
		date_default_timezone_set($timezone);
		$january = new DateTime(date('Y') . '-01-01 12:00:00');
		$july = new DateTime(date('Y') . '-07-01 12:00:00');
		$offset = ($january->getOffset() < $july->getOffset()) ? $january->getOffset() / 3600 : $july->getOffset() / 3600;
	}
	else
	{
		putenv('TZ=' . $timezone);
		$offset = (date('Z', mktime(0, 0, 0, 01, 01)) < date('Z', mktime(0, 0, 0, 07, 01))) ? date('Z', mktime(0, 0, 0, 01, 01)) / 3600 : date('Z', mktime(0, 0, 0, 07, 01)) / 3600;
	}

	define('AUTOMATIC_DST_BOARD_TIMEZONE', $timezone);
	define('AUTOMATIC_DST_BOARD_ISDST', date('I'));

	// Return time zone offset
	return $offset;
}

function automatic_dst_get_timezones()
{
	// From http://us.php.net/manual/en/function.date-default-timezone-set.php#84459
	// Credit to Rob Kaper

	global $user;

	if (version_compare(PHP_VERSION, '5.2.0', '>'))
	{
		$timezoneslist = DateTimeZone::listAbbreviations();

		$cities = array();

		foreach($timezoneslist as $key => $zones)
		{
			foreach($zones as $id => $zone)
			{
				if (preg_match('/^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $zone['timezone_id']))
				{
					$cities[$zone['timezone_id']] = strtr($zone['timezone_id'], $user->lang['automatic_dst_timezones']);
				}
			}
		}
	}
	else
	{
		// Sad, but that's how it has to be done...
		$timezoneslist = array(
			'Africa/Abidjan', 'Africa/Accra', 'Africa/Addis_Ababa', 'Africa/Algiers', 'Africa/Asmara', 'Africa/Asmera', 'Africa/Bamako', 'Africa/Bangui', 'Africa/Banjul', 'Africa/Bissau', 'Africa/Blantyre', 'Africa/Brazzaville', 'Africa/Cairo', 'Africa/Casablanca', 'Africa/Ceuta', 'Africa/Conakry', 'Africa/Dakar', 'Africa/Dar_es_Salaam', 'Africa/Djibouti', 'Africa/Douala', 'Africa/El_Aaiun', 'Africa/Freetown', 'Africa/Gaborone', 'Africa/Harare', 'Africa/Johannesburg', 'Africa/Kampala', 'Africa/Khartoum', 'Africa/Kigali', 'Africa/Lagos', 'Africa/Libreville', 'Africa/Luanda', 'Africa/Lusaka', 'Africa/Malabo', 'Africa/Maputo', 'Africa/Maseru', 'Africa/Mbabane', 'Africa/Mogadishu', 'Africa/Monrovia', 'Africa/Nairobi', 'Africa/Ndjamena', 'Africa/Niamey', 'Africa/Nouakchott', 'Africa/Ouagadougou', 'Africa/Porto-Novo', 'Africa/Sao_Tome', 'Africa/Timbuktu', 'Africa/Tripoli', 'Africa/Tunis', 'Africa/Windhoek',
			'America/Adak', 'America/Anchorage', 'America/Anguilla', 'America/Antigua', 'America/Araguaina', 'America/Argentina/Buenos_Aires', 'America/Argentina/Catamarca', 'America/Argentina/ComodRivadavia', 'America/Argentina/Cordoba', 'America/Argentina/Jujuy', 'America/Argentina/La_Rioja', 'America/Argentina/Mendoza', 'America/Argentina/Rio_Gallegos', 'America/Argentina/San_Juan', 'America/Argentina/Tucuman', 'America/Argentina/Ushuaia', 'America/Aruba', 'America/Asuncion', 'America/Atikokan', 'America/Atka', 'America/Bahia', 'America/Barbados', 'America/Belem', 'America/Belize', 'America/Blanc-Sablon', 'America/Boa_Vista', 'America/Bogota', 'America/Boise', 'America/Buenos_Aires', 'America/Cambridge_Bay', 'America/Campo_Grande', 'America/Cancun', 'America/Caracas', 'America/Catamarca', 'America/Cayenne', 'America/Cayman', 'America/Chicago', 'America/Chihuahua', 'America/Coral_Harbour', 'America/Cordoba', 'America/Costa_Rica', 'America/Cuiaba', 'America/Curacao', 'America/Danmarkshavn', 'America/Dawson', 'America/Dawson_Creek', 'America/Denver', 'America/Detroit', 'America/Dominica', 'America/Edmonton', 'America/Eirunepe', 'America/El_Salvador', 'America/Ensenada', 'America/Fort_Wayne', 'America/Fortaleza', 'America/Glace_Bay', 'America/Godthab', 'America/Goose_Bay', 'America/Grand_Turk', 'America/Grenada', 'America/Guadeloupe', 'America/Guatemala', 'America/Guayaquil', 'America/Guyana', 'America/Halifax', 'America/Havana', 'America/Hermosillo', 'America/Indiana/Indianapolis', 'America/Indiana/Knox', 'America/Indiana/Marengo', 'America/Indiana/Petersburg', 'America/Indiana/Vevay', 'America/Indiana/Vincennes', 'America/Indiana/Winamac', 'America/Indianapolis', 'America/Inuvik', 'America/Iqaluit', 'America/Jamaica', 'America/Jujuy', 'America/Juneau', 'America/Kentucky/Louisville', 'America/Kentucky/Monticello', 'America/Knox_IN', 'America/La_Paz', 'America/Lima', 'America/Los_Angeles', 'America/Louisville', 'America/Maceio', 'America/Managua', 'America/Manaus', 'America/Martinique', 'America/Mazatlan', 'America/Mendoza', 'America/Menominee', 'America/Merida', 'America/Mexico_City', 'America/Miquelon', 'America/Moncton', 'America/Monterrey', 'America/Montevideo', 'America/Montreal', 'America/Montserrat', 'America/Nassau', 'America/New_York', 'America/Nipigon', 'America/Nome', 'America/Noronha', 'America/North_Dakota/Center', 'America/North_Dakota/New_Salem', 'America/Panama', 'America/Pangnirtung', 'America/Paramaribo', 'America/Phoenix', 'America/Port-au-Prince', 'America/Port_of_Spain', 'America/Porto_Acre', 'America/Porto_Velho', 'America/Puerto_Rico', 'America/Rainy_River', 'America/Rankin_Inlet', 'America/Recife', 'America/Regina', 'America/Rio_Branco', 'America/Rosario', 'America/Santiago', 'America/Santo_Domingo', 'America/Sao_Paulo', 'America/Scoresbysund', 'America/Shiprock', 'America/St_Johns', 'America/St_Kitts', 'America/St_Lucia', 'America/St_Thomas', 'America/St_Vincent', 'America/Swift_Current', 'America/Tegucigalpa', 'America/Thule', 'America/Thunder_Bay', 'America/Tijuana', 'America/Toronto', 'America/Tortola', 'America/Vancouver', 'America/Virgin', 'America/Whitehorse', 'America/Winnipeg', 'America/Yakutat', 'America/Yellowknife',
			'Antarctica/Casey', ' Antarctica/Davis', ' Antarctica/DumontDUrville', ' Antarctica/Mawson', ' Antarctica/McMurdo', ' Antarctica/Palmer', ' Antarctica/Rothera', ' Antarctica/South_Pole', ' Antarctica/Syowa', ' Antarctica/Vostok',
			'Arctic/Longyearbyen',
			'Asia/Aden', 'Asia/Almaty', 'Asia/Amman', 'Asia/Anadyr', 'Asia/Aqtau', 'Asia/Aqtobe', 'Asia/Ashgabat', 'Asia/Ashkhabad', 'Asia/Baghdad', 'Asia/Bahrain', 'Asia/Baku', 'Asia/Bangkok', 'Asia/Beirut', 'Asia/Bishkek', 'Asia/Brunei', 'Asia/Calcutta', 'Asia/Choibalsan', 'Asia/Chongqing', 'Asia/Chungking', 'Asia/Colombo', 'Asia/Dacca', 'Asia/Damascus', 'Asia/Dhaka', 'Asia/Dili', 'Asia/Dubai', 'Asia/Dushanbe', 'Asia/Gaza', 'Asia/Harbin', 'Asia/Hong_Kong', 'Asia/Hovd', 'Asia/Irkutsk', 'Asia/Istanbul', 'Asia/Jakarta', 'Asia/Jayapura', 'Asia/Jerusalem', 'Asia/Kabul', 'Asia/Kamchatka', 'Asia/Karachi', 'Asia/Kashgar', 'Asia/Katmandu', 'Asia/Krasnoyarsk', 'Asia/Kuala_Lumpur', 'Asia/Kuching', 'Asia/Kuwait', 'Asia/Macao', 'Asia/Macau', 'Asia/Magadan', 'Asia/Makassar', 'Asia/Manila', 'Asia/Muscat', 'Asia/Nicosia', 'Asia/Novosibirsk', 'Asia/Omsk', 'Asia/Oral', 'Asia/Phnom_Penh', 'Asia/Pontianak', 'Asia/Pyongyang', 'Asia/Qatar', 'Asia/Qyzylorda', 'Asia/Rangoon', 'Asia/Riyadh', 'Asia/Saigon', 'Asia/Sakhalin', 'Asia/Samarkand', 'Asia/Seoul', 'Asia/Shanghai', 'Asia/Singapore', 'Asia/Taipei', 'Asia/Tashkent', 'Asia/Tbilisi', 'Asia/Tehran', 'Asia/Tel_Aviv', 'Asia/Thimbu', 'Asia/Thimphu', 'Asia/Tokyo', 'Asia/Ujung_Pandang', 'Asia/Ulaanbaatar', 'Asia/Ulan_Bator', 'Asia/Urumqi', 'Asia/Vientiane', 'Asia/Vladivostok', 'Asia/Yakutsk', 'Asia/Yekaterinburg', 'Asia/Yerevan',
			'Atlantic/Azores', 'Atlantic/Bermuda', 'Atlantic/Canary', 'Atlantic/Cape_Verde', 'Atlantic/Faeroe', 'Atlantic/Faroe', 'Atlantic/Jan_Mayen', 'Atlantic/Madeira', 'Atlantic/Reykjavik', 'Atlantic/St_Helena', 'Atlantic/Stanley',
			'Australia/ACT', 'Australia/Adelaide', 'Australia/Brisbane', 'Australia/Broken_Hill', 'Australia/Canberra', 'Australia/Currie', 'Australia/Darwin', 'Australia/Eucla', 'Australia/Hobart', 'Australia/LHI', 'Australia/Lindeman', 'Australia/Lord_Howe', 'Australia/Melbourne', 'Australia/NSW', 'Australia/North', 'Australia/Perth', 'Australia/Queensland', 'Australia/South', 'Australia/Sydney', 'Australia/Tasmania', 'Australia/Victoria', 'Australia/West', 'Australia/Yancowinna',
			'Europe/Amsterdam', 'Europe/Andorra', 'Europe/Athens', 'Europe/Belfast', 'Europe/Belgrade', 'Europe/Berlin', 'Europe/Bratislava', 'Europe/Brussels', 'Europe/Bucharest', 'Europe/Budapest', 'Europe/Chisinau', 'Europe/Copenhagen', 'Europe/Dublin', 'Europe/Gibraltar', 'Europe/Guernsey', 'Europe/Helsinki', 'Europe/Isle_of_Man', 'Europe/Istanbul', 'Europe/Jersey', 'Europe/Kaliningrad', 'Europe/Kiev', 'Europe/Lisbon', 'Europe/Ljubljana', 'Europe/London', 'Europe/Luxembourg', 'Europe/Madrid', 'Europe/Malta', 'Europe/Mariehamn', 'Europe/Minsk', 'Europe/Monaco', 'Europe/Moscow', 'Europe/Nicosia', 'Europe/Oslo', 'Europe/Paris', 'Europe/Podgorica', 'Europe/Prague', 'Europe/Riga', 'Europe/Rome', 'Europe/Samara', 'Europe/San_Marino', 'Europe/Sarajevo', 'Europe/Simferopol', 'Europe/Skopje', 'Europe/Sofia', 'Europe/Stockholm', 'Europe/Tallinn', 'Europe/Tirane', 'Europe/Tiraspol', 'Europe/Uzhgorod', 'Europe/Vaduz', 'Europe/Vatican', 'Europe/Vienna', 'Europe/Vilnius', 'Europe/Volgograd', 'Europe/Warsaw', 'Europe/Zagreb', 'Europe/Zaporozhye', 'Europe/Zurich',
			'Indian/Antananarivo', 'Indian/Chagos', 'Indian/Comoro', 'Indian/Kerguelen', 'Indian/Mahe', 'Indian/Maldives', 'Indian/Mauritius', 'Indian/Mayotte', 'Indian/Reunion',
			'Pacific/Apia', 'Pacific/Auckland', 'Pacific/Chatham', 'Pacific/Easter', 'Pacific/Efate', 'Pacific/Enderbury', 'Pacific/Fiji', 'Pacific/Galapagos', 'Pacific/Gambier', 'Pacific/Guadalcanal', 'Pacific/Guam', 'Pacific/Honolulu', 'Pacific/Kiritimati', 'Pacific/Kosrae', 'Pacific/Kwajalein', 'Pacific/Majuro', 'Pacific/Marquesas', 'Pacific/Midway', 'Pacific/Nauru', 'Pacific/Niue', 'Pacific/Norfolk', 'Pacific/Noumea', 'Pacific/Pago_Pago', 'Pacific/Pitcairn', 'Pacific/Rarotonga', 'Pacific/Saipan', 'Pacific/Samoa', 'Pacific/Tahiti', 'Pacific/Tongatapu',
		);

		foreach ($timezoneslist as $zone)
		{
			$cities[$zone] = strtr($zone, $user->lang['automatic_dst_timezones']);
		}
	}

	// We have to sort the array because of possible translation order changes
	asort($cities);

	return $cities;
}

function automatic_dst_get_timetable()
{
	return array(
		/**
		* Time zone conversion table (don't flame me if your city isn't here - I had to pick one for every time zone!)
		*/
		'-12.00'	=> '',						// [UTC - 12] Baker Island Time
		'-11.00'	=> 'Pacific/Samoa',			// [UTC - 11] Niue Time, Samoa Standard Time
		'-10.00'	=> 'Pacific/Tahiti',		// [UTC - 10] Hawaii-Aleutian Standard Time, Cook Island Time
		'-9.50'		=> '',						// [UTC - 9:30] Marquesas Islands Time
		'-9.00'		=> 'America/Anchorage',		// [UTC - 9] Alaska Standard Time, Gambier Island Time
		'-8.00'		=> 'America/Los_Angeles',	// [UTC - 8] Pacific Standard Time
		'-7.00'		=> 'America/Denver',		// [UTC - 7] Mountain Standard Time
		'-6.00'		=> 'America/Detroit',		// [UTC - 6] Central Standard Time
		'-5.00'		=> 'America/Chicago',		// [UTC - 5] Eastern Standard Time
		'-4.50'		=> '',						// [UTC - 4:30] Venezuelan Standard Time
		'-4.00'		=> 'America/Grenada',		// [UTC - 4] Atlantic Standard Time
		'-3.50'		=> '',						// [UTC - 3:30] Newfoundland Standard Time
		'-3.00'		=> 'America/Sao_Paulo',		// [UTC - 3] Amazon Standard Time, Central Greenland Time
		'-2.00'		=> 'America/Scoresbysund',	// [UTC - 2] Fernando de Noronha Time, South Georgia & the South Sandwich Islands Time
		'-1.00'		=> 'Atlantic/Cape_Verde',	// [UTC - 1] Azores Standard Time, Cape Verde Time, Eastern Greenland Time
		'0.00'		=> 'Europe/London',			// [UTC] Western European Time, Greenwich Mean Time
		'1.00'		=> 'Europe/Berlin',			// [UTC + 1] Central European Time, West African Time
		'2.00'		=> 'Europe/Kiev',			// [UTC + 2] Eastern European Time, Central African Time
		'3.00'		=> 'Europe/Moscow',			// [UTC + 3] Moscow Standard Time, Eastern African Time
		'3.50'		=> 'Asia/Tehran',			// [UTC + 3:30] Iran Standard Time
		'4.00'		=> 'Asia/Dubai',			// [UTC + 4] Gulf Standard Time, Samara Standard Time
		'4.50'		=> 'Asia/Kabul',			// [UTC + 4:30] Afghanistan Time
		'5.00'		=> 'Asia/Karachi',			// [UTC + 5] Pakistan Standard Time, Yekaterinburg Standard Time
		'5.50'		=> 'Asia/Calcutta',			// [UTC + 5:30] Indian Standard Time, Sri Lanka Time
		'5.75'		=> 'Asia/Katmandu',			// [UTC + 5:45] Nepal Time
		'6.00'		=> 'Asia/Novosibirsk',		// [UTC + 6] Bangladesh Time, Bhutan Time, Novosibirsk Standard Time
		'6.50'		=> 'Asia/Rangoon',			// [UTC + 6:30] Cocos Islands Time, Myanmar Time
		'7.00'		=> 'Asia/Bangkok',			// [UTC + 7] Indochina Time, Krasnoyarsk Standard Time
		'8.00'		=> 'Asia/Shanghai',			// [UTC + 8] Chinese Standard Time, Australian Western Standard Time, Irkutsk Standard Time
		'8.75'		=> '',						// [UTC + 8:45] Southeastern Western Australia Standard Time
		'9.00'		=> 'Asia/Tokyo',			// [UTC + 9] Japan Standard Time, Korea Standard Time, Chita Standard Time
		'9.50'		=> '',						// [UTC + 9:30] Australian Central Standard Time
		'10.00'		=> 'Asia/Vladivostok',		// [UTC + 10] Australian Eastern Standard Time, Vladivostok Standard Time
		'10.50'		=> 'Australia/Lord_Howe',	// [UTC + 10:30] Lord Howe Standard Time
		'11.00'		=> 'Pacific/Guadalcanal',	// [UTC + 11] Solomon Island Time, Magadan Standard Time
		'11.50'		=> 'Pacific/Norfolk',		// [UTC + 11:30] Norfolk Island Time
		'12.00'		=> 'Pacific/Auckland',		// [UTC + 12] New Zealand Time, Fiji Time, Kamchatka Standard Time
		'12.75'		=> 'Pacific/Chatham',		// [UTC + 12:45] Chatham Islands Time
		'13.00'		=> 'Pacific/Tongatapu',		// [UTC + 13] Tonga Time, Phoenix Islands Time
		'14.00'		=> 'Pacific/Kiritimati'		// [UTC + 14] Line Island Time
	);
}
?>