<?php
/**
*
* prime_birthdate [English]
*
* @package language
* @version $Id: prime_birthdate.php,v 1.2.0 2008/07/25 17:30:00 primehalo Exp $
* @copyright (c) 2007-2008 Ken F. Innes IV
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
	'PRIME_BIRTHDATE_NAME'				=> 'Birth Date',
	'PRIME_BIRTHDATE_ENTER'				=> 'Please enter your birth date',
	'PRIME_BIRTHDATE_EMPTY'				=> 'You must enter your birth date to continue.',
	'PRIME_BIRTHDATE_YOUNG'				=> 'You are too young to be registered here.',
	'PRIME_BIRTHDATE_OLD'				=> 'You are too old to be registered here.',
	'PRIME_BIRTHDATE_MIN'				=> 'Minimum age',
	'PRIME_BIRTHDATE_MIN_EXPLAIN'		=> 'Require user to be this age in order to register. Only functions if birthdays are required.',
	'PRIME_BIRTHDATE_MAX'				=> 'Maximum age',
	'PRIME_BIRTHDATE_MAX_EXPLAIN'		=> 'Require user to be no older than this age in order to register. Enter 0 for unlimited. Only functions if birthdays are required.',
	'PRIME_BIRTHDATE_YEARS_OLD'			=> 'years old',
	'PRIME_BIRTHDATE_REQUIRE'			=> 'Require',
	'PRIME_BIRTHDATE_LOCK'				=> 'Require &amp; Lock',
	'PRIME_BIRTHDATE_SHOW_AGE'			=> 'Display age',
	'PRIME_BIRTHDATE_SHOW_AGE_EXPLAIN'	=> 'Determines if your age is publicly viewable.',
	'PRIME_BIRTHDATE_CONGRATS'			=> 'Display Birthday Congrats',
	'PRIME_BIRTHDATE_CONGRATS_EXPLAIN'	=> 'Determines if your username will appear on the congratulations list when it is your birthday.',
	
	// Overwrite the original as the explanation no longer holds true.
	'BIRTHDAY_EXPLAIN'					=> 'This will not be publicly viewable.', 
));

?>