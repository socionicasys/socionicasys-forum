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
	'PRIME_BIRTHDATE_NAME'				=> 'Дата рождения',
	'PRIME_BIRTHDATE_ENTER'				=> 'Пожалуйста введите дату своего рождения',
	'PRIME_BIRTHDATE_EMPTY'				=> 'Дата рождения — обязательное поле.',
	'PRIME_BIRTHDATE_YOUNG'				=> 'Детям нельзя много знать про соционику.',
	'PRIME_BIRTHDATE_OLD'				=> 'Вы подозрительно много прожили. Может, вам уже и не нужна эта соционика?',
	'PRIME_BIRTHDATE_MIN'				=> 'Минимальный возраст',
	'PRIME_BIRTHDATE_MIN_EXPLAIN'		=> 'Минимальный возраст для регистрации на форуме. Это значение учитывется только если дата рождения выбрана обязательным полем.',
	'PRIME_BIRTHDATE_MAX'				=> 'Максимальный возраст',
	'PRIME_BIRTHDATE_MAX_EXPLAIN'		=> 'Максимальный возраст для регистрации на форуме. Это значение учитывется только если дата рождения выбрана обязательным полем.',
	'PRIME_BIRTHDATE_YEARS_OLD'			=> 'лет',
	'PRIME_BIRTHDATE_REQUIRE'			=> 'Обязательно',
	'PRIME_BIRTHDATE_LOCK'				=> 'Обязательно, нельзя менять',
	'PRIME_BIRTHDATE_SHOW_AGE'			=> 'Отображать возраст',
	'PRIME_BIRTHDATE_SHOW_AGE_EXPLAIN'	=> 'Отображать ли ваш возраст в публичном профиле.',
	'PRIME_BIRTHDATE_CONGRATS'			=> 'Поздравлять с днем рождения',
	'PRIME_BIRTHDATE_CONGRATS_EXPLAIN'	=> 'Отображать ли ваше никнейм в списке поздравлений на главной странице форума.',
	
	// Overwrite the original as the explanation no longer holds true.
	'BIRTHDAY_EXPLAIN'					=> 'Дата рождения не отображается в публичном профиле.', 
));

?>
