<?php

/**
*
* automatic_dst.php [Russian]
*
* @package - "Automatic Daylight Savings Time 2"
* @version $Id: automatic_dst.php 3 2009-03-28 MartectX $
* @copyright (C)2008-2009, MartectX ( http://mods.martectx.de/ )
* @author 2009-08-05 - Translated by FladeX ( http://fladex.ru/ )
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
	'AUTOMATIC_DST_DISPLAY'		=> 'Часовой пояс: %1$s %2$s',
	'AUTOMATIC_DST_SETUP'		=> 'Перейдите в Настройки Конференции и выберите новый правильный часовой пояс форума (если он уже правильный, то просто нажмите на кнопку "Продолжить".<br /><br /><strong>Часовые пояса не сконвертировались!</strong>',
	'AUTOMATIC_DST_INSTALLED'	=> 'Конвертация часовых поясов завершена.<br /><br /><strong>Пожалуйста, удалите этот файл с вашего сервера!</strong>',

	'automatic_dst_timezones'	=> array(
		'Africa'		=> 'Африка',
		'America'		=> 'Америка',
		'Antarctica'	=> 'Антарктика',
		'Arctic'		=> 'Арктика',
		'Asia'			=> 'Азия',
		'Atlantic'		=> 'Атлантический океан',
		'Australia'		=> 'Австралия',
		'Europe'		=> 'Европа',
		'Indian'		=> 'Индия',
		'Pacific'		=> 'Тихий океан'
	)
));

?>