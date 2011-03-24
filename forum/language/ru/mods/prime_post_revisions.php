<?php
/**
*
* prime_post_revisions [Russian]
*
* @package language
* @version $Id: prime_post_revisions.php,v 1.2.0 2008/07/21 13:45:00 primehalo Exp $
* @copyright (c) 2007-2008 Ken F. Innes IV
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* @translated by Sergey aka Porutchik http://forum.aeroion.ru
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
	// Viewing posts
	'PRIME_POST_REVISIONS_VIEW'				=> 'Просмотр истории сообщения.',	// Text for the link to view the revision history

	// Viewing revisions
	'PRIME_POST_REVISIONS_VIEWING'			=> 'Просмотр истории сообщения',
	'PRIME_POST_REVISIONS_VIEWING_EXPLAIN'	=> 'На этой странице показаны все версии сообщения, начиная с текущей.',
	'PRIME_POST_REVISIONS_TITLE'			=> 'Просмотр истории сообщения: %s',	// The %s is the post title
	'PRIME_POST_REVISIONS_FIRST'			=> 'Первоначальный текст: %s',			// The %s is the post title
	'PRIME_POST_REVISIONS_FINAL'			=> 'Текущий текст: %s',			// The %s is the post title
	'PRIME_POST_REVISIONS_COUNT'			=> 'Версия %d: %s',			// The %s is the post title
	'PRIME_POST_REVISIONS_INFO'				=> 'Отредактировано %1$s %2$s.',
	'PRIME_POST_REVISIONS_NO_SUBJECT'		=> '[нет заголовка]',	

	// Delete a revision
	'PRIME_POST_REVISIONS_DELETE'			=> 'Удалить версию',
	'PRIME_POST_REVISIONS_DELETE_CONFIRM'	=> 'Вы уверены что хотите удалить эту версию?',
	'PRIME_POST_REVISIONS_DELETE_DENIED'	=> 'У вас отсутствуют права доступа для удаления этой версии сообщения.',
	'PRIME_POST_REVISIONS_DELETE_FAILED'	=> 'Произошла ошибка при попытке удаления версии сообщения.',
	'PRIME_POST_REVISIONS_DELETE_SUCCESS'	=> 'Версия сообщения успешно удалена.',
	'PRIME_POST_REVISIONS_DELETE_INVALID'	=> 'Не выбрана версия сообщения для удаления.',

	// Delete all revisions
	'PRIME_POST_REVISIONS_DELETES'			=> 'Удалить все версии.',
	'PRIME_POST_REVISIONS_DELETES_CONFIRM'	=> 'Вы уверены что хотите удалить все версии сообщения?',
	'PRIME_POST_REVISIONS_DELETES_DENIED'	=> 'У вас отсутствуют права доступа для удаления версий сообщения.',
	'PRIME_POST_REVISIONS_DELETES_FAILED'	=> 'Произошла ошибка при попытке удаления версий сообщения.',
	'PRIME_POST_REVISIONS_DELETES_SUCCESS'	=> 'Все версии сообщения успешно удалены.',
	'PRIME_POST_REVISIONS_DELETES_INVALID'	=> 'Не выбраны версии сообщения для удаления.',
));

?>