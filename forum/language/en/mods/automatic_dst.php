<?php

/**
*
* automatic_dst.php [English]
*
* @package - "Automatic Daylight Savings Time 2"
* @version $Id: automatic_dst.php 4 2009-11-18 MartectX $
* @copyright (C)2008-2009, MartectX ( http://mods.martectx.de/ )
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'AUTOMATIC_DST_DISPLAY'		=> 'Time zone: %1$s %2$s',
	'AUTOMATIC_DST_SETUP'		=> 'Go to your Board Settings and choose a new valid board time zone first (if it is already correct then just approve it by hitting "Submit").<br /><br /><strong>Time zones have not been converted!</strong>',
	'AUTOMATIC_DST_INSTALLED'	=> 'Time zone conversion complete.<br /><br /><strong>Please remove this file from your server!</strong>',

	// I know this is pointless for English but here goes nonetheless...
	'automatic_dst_timezones'	=> array(
		'Africa/'		=> 'Africa/',
		'America/'		=> 'America/',
		'Antarctica/'	=> 'Antarctica/',
		'Arctic/'		=> 'Arctic/',
		'Asia/'			=> 'Asia/',
		'Atlantic/'		=> 'Atlantic/',
		'Australia/'	=> 'Australia/',
		'Europe/'		=> 'Europe/',
		'Indian/'		=> 'Indian/',
		'Pacific/'		=> 'Pacific/',

		// You may translate city names, too:
		'Vienna'		=> 'Vienna',
	)
));

?>