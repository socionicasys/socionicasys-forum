<?php

/**
*
* @package - "Automatic Daylight Savings Time 2"
* @version $Id: convert_timezones.php 3 2009-03-28 MartectX $
* @copyright (C)2008-2009, MartectX ( http://mods.martectx.de/ )
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/automatic_dst');

if (!$auth->acl_get('a_'))
{
	trigger_error('NO_ADMIN');
}

if (defined('AUTOMATIC_DST_TEMP_TIMEZONE'))
{
	trigger_error($user->lang['AUTOMATIC_DST_SETUP'], E_USER_WARNING);
}

$timetable = automatic_dst_get_timetable();

$sql = 'SELECT user_id, user_timezone
	FROM ' . USERS_TABLE;
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	// Only convert entries that are actually stored in the old format
	if (array_key_exists($row['user_timezone'], $timetable))
	{
		// If there's no entry to convert to use the board setting
		$timezone = ($timetable[$row['user_timezone']]) ? $timetable[$row['user_timezone']] : AUTOMATIC_DST_BOARD_TIMEZONE;

		$sql = 'UPDATE ' . USERS_TABLE . "
			SET user_timezone = '$timezone'
			WHERE user_id = " . $row['user_id'];
		$db->sql_query($sql);
	}
}
$db->sql_freeresult($result);

trigger_error($user->lang['AUTOMATIC_DST_INSTALLED']);
?>