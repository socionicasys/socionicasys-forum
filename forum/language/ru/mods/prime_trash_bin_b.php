<?php
/**
*
* prime_trash_bin_b [Russian]
*
* @package language
* @version $Id: prime_trash_bin_b.php,v 0.0.0 2007/07/30 22:30:00 primehalo Exp $
* @copyright (c) 2007 Ken F. Innes IV
* @translation 2007 Russian by Stipendiat (autoir@mail.ru)
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

//Logs
$lang = array_merge($lang, array(
	// Overwrite
	'LOG_TOPIC_DELETED'		=> '<strong>Удалена тема</strong><br />» %s',
	'LOG_DELETE_TOPIC'		=> '<strong>Удалена тема</strong><br />» %s',
	'LOG_DELETE_POST'		=> '<strong>Удалено сообщение</strong><br />» %s',

	// New
	'LOG_TOPIC_STIFLED'		=> '<strong>Удаленная тема</strong><br />» %1$s',
	'LOG_TOPIC_TRASHED'		=> '<strong>Топик удален в корзину</strong><br />» %1$s',
	'LOG_TOPIC_UNSTIFLED'	=> '<strong>Восстановлен топик</strong><br />» %1$s',

	'LOG_POST_STIFLED'		=> '<strong>Удалено сообщение</strong><br />» %1$s',
	'LOG_POST_TRASHED'		=> '<strong>Сообщение было перемещено в корзину</strong><br />» %1$s',
	'LOG_POST_UNSTIFLED'	=> '<strong>Восстановлено сообщение</strong><br />» %1$s',
));

$lang = array_merge($lang, array(
	'LOG_TOPIC_STIFLED_2'	=> $lang['LOG_TOPIC_STIFLED'] . '<br />» » <strong>Причина:</strong> %2$s',
	'LOG_TOPIC_TRASHED_2'	=> $lang['LOG_TOPIC_TRASHED'] . '<br />» » <strong>Причина:</strong> %2$s',
	'LOG_TOPIC_UNSTIFLED_2'	=> $lang['LOG_TOPIC_UNSTIFLED'] . '<br />» » <strong>Причина:</strong> %2$s',

	'LOG_POST_STIFLED_2'	=> $lang['LOG_POST_STIFLED'] . '<br />» » <strong>Причина:</strong> %2$s',
	'LOG_POST_TRASHED_2'	=> $lang['LOG_POST_TRASHED'] . '<br />» » <strong>Причина:</strong> %2$s',
	'LOG_POST_UNSTIFLED_2'	=> $lang['LOG_POST_UNSTIFLED'] . '<br />» » <strong>Причина:</strong> %2$s',
));


// Administration
$lang = array_merge($lang, array(
	'PRIME_FAKE_DELETE'					=> 'Удаление тем',
	'PRIME_FAKE_DELETE_EXPLAIN'			=> 'Определяет принцип удаления тем на форуме.',
	'PRIME_FAKE_DELETE_DISABLE'			=> 'Безвозвратно удалять темы',
	'PRIME_FAKE_DELETE_ENABLE'			=> 'Помечать темы как удаленные', 
	'PRIME_FAKE_DELETE_AUTO_TRASH'		=> 'Перемещать темы в корзину',
	'PRIME_FAKE_DELETE_SHADOW_ON'		=> 'Перемещать тему в корзину с возможностью оставить ссылку на нее',
	'PRIME_FAKE_DELETE_SHADOW_OFF'		=> 'Перемещать тему в корзину без возможности оставить ссылку на нее',

	'PRIME_TRASH_FORUM'					=> 'Форум корзины',
	'PRIME_TRASH_FORUM_EXPLAIN'			=> 'Если выбран, то удаленная ветка будет перенесена в корзину и сообщения будут скрыты от просмотра. Удаление ветки из корзины приведет к полному удаленю без возможности восстановления.',
	'PRIME_TRASH_FORUM_DISABLE'			=> 'Не использовать корзину',
	'PRIME_TRASH_FORUM_DIVIDER'			=> '---------------------------',
	'PRIME_NO_TRASH_FORUM_ERROR'		=> 'Вы должны настроить корзину прежде чем выбрать "%s" опцию',

	'PRIME_FOREVER_WHEN_DELETE_USER'	=> 'Навсегда удалить должностей',
));

// Moderation
$lang = array_merge($lang, array(

	// Topics - Deleting
	'PRIME_DELETE_TOPIC_REASON'			=> 'Укажите причину удаления',
	'PRIME_DELETE_TOPIC_FOREVER'		=> 'Навсегда удалить тему',
	'PRIME_DELETE_TOPICS_FOREVER'		=> 'Навсегда удалить темы',
	'PRIME_DELETE_TO_TRASH_BIN'			=> 'Переместить тему в корзину',
	'PRIME_DELETE_TOPIC_FOREVER_DENIED'	=> 'Вы не можете удалять темы в этом форуме.',
	'PRIME_DELETE_TOPIC_MIX_NOTICE'		=> 'Информация: Темы, помеченные как удаленные, не будут изменены.',

	// Topics - Deleted
	'PRIME_DELETED_TOPIC_SUCCESS'		=> 'Выбранная тема была успешно помечена как удаленная.',
	'PRIME_DELETED_TOPICS_SUCCESS'		=> 'Выбранные темы были успешно помечены как удаленные.',
	'PRIME_DELETED_TOPIC_FAILURE'		=> 'Выбранная тема НЕ была помечена как удаленная.',
	'PRIME_DELETED_TOPICS_FAILURE'		=> 'Выбранные темы НЕ были помечены как удаленные.',

	// Topics - Deleted to Trash Bin
	'PRIME_TRASHED_TOPIC_SUCCESS'		=> 'Выбранная тема была успешно перемещена в корзину.',
	'PRIME_TRASHED_TOPICS_SUCCESS'		=> 'Выбраные темы были успешно перемещены в корзину.',
	'PRIME_TRASHED_TOPIC_FAILURE'		=> 'Выбраный темы НЕ былы перемещены в корзину.',
	'PRIME_TRASHED_TOPICS_FAILURE'		=> 'Выбраные темы НЕ были перемещены в корзину.',
	'PRIME_GO_TO_TRASH_BIN'				=> '%sПерейти в корзину%s',

	// Topics - Undeleting
	'PRIME_UNDELETE_TOPICS'				=> 'Восстановить',
	'PRIME_UNDELETE_TOPIC_REASON'		=> 'Укажите причину восстановления',
	'PRIME_UNDELETE_TOPIC_CONFIRM'		=> 'Вы уверены, что хотите восстановить выбранную тему?',
	'PRIME_UNDELETE_TOPICS_CONFIRM'		=> 'Вы уверены, что хотите восстановить выбранные темы?',
	'PRIME_UNDELETE_TOPICS_UNNEEDED'	=> 'Выбранные темы НЕ были восстановлены',


	// Topics - Undeleted
	'PRIME_UNDELETED_TOPIC_SUCCESS'		=> 'Выбранная тема был успешно восстановлена.',
	'PRIME_UNDELETED_TOPICS_SUCCESS'	=> 'Выбранные темы были успешно восстановлены.',
	'PRIME_UNDELETED_TOPIC_FAILURE'		=> 'Выбранная тема НЕ была восстановлена.',
	'PRIME_UNDELETED_TOPICS_FAILURE'	=> 'Выбранные темы НЕ были восстановлены.',

	// Posts - Deleting
	'PRIME_DELETE_POST_REASON'			=> 'Укажите причину удаления',
	'PRIME_DELETE_POST_FOREVER'			=> 'Навсегда удалить сообщение',
	'PRIME_DELETE_POSTS_FOREVER'		=> 'Навсегда удалить сообщения',
	'PRIME_DELETE_POST_FOREVER_DENIED'	=> 'Вы не можете удалять сообщения в этом форуме.',
	'PRIME_DELETE_POST_MIX_NOTICE'		=> 'Информация: Все сообщения, помеченные как удаленные, не будут изменены.',

	// Posts - Deleted
	'PRIME_DELETED_POST_SUCCESS'		=> 'Сообщение было успешно помечено как удаленное.',
	'PRIME_DELETED_POSTS_SUCCESS'		=> 'Сообщения были успешно помечены как удаленные.',
	'PRIME_DELETED_POST_FAILURE'		=> 'Сообщение НЕ было помечено как удаленное.',
	'PRIME_DELETED_POSTS_FAILURE'		=> 'Сообщения НЕ были помечены как удаленные.',

	// Posts - Undeleting
	'PRIME_UNDELETE_POST'				=> 'Восстановить сообщение',
	'PRIME_UNDELETE_POSTS'				=> 'Восстановить сообщения',
	'PRIME_UNDELETE_POST_REASON'		=> 'Пожалуйста укажите причину восстановления',
	'PRIME_UNDELETE_POST_CONFIRM'		=> 'Вы уверены, что хотите восстановить сообщение?',
	'PRIME_UNDELETE_POSTS_CONFIRM'		=> 'Вы уверены, что хотите восстановить сообщения?',
	'PRIME_UNDELETE_POSTS_UNNEEDED'		=> 'Сообщение НЕ может быть восстановлено.',

	// Posts - Undeleted
	'PRIME_UNDELETED_POST_SUCCESS'		=> 'Сообщение было успешно восстановлено.',
	'PRIME_UNDELETED_POSTS_SUCCESS'		=> 'Сообщения были успешно восстановлены.',
	'PRIME_UNDELETED_POST_FAILURE'		=> 'Сообщение НЕ было восстановлено.',
	'PRIME_UNDELETED_POSTS_FAILURE'		=> 'Сообщения НЕ были восстановлены.',

));

?>