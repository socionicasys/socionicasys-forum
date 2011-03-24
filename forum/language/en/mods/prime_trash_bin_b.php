<?php
/**
*
* prime_trash_bin_b [English]
*
* @package language
* @version $Id: prime_trash_bin_b.php,v 1.1.0 2008/08/23 17:01:00 primehalo Exp $
* @copyright (c) 2007 Ken F. Innes IV
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
	'LOG_TOPIC_DELETED'		=> '<strong>Permanently deleted topic</strong><br />» %s',
	'LOG_DELETE_TOPIC'		=> '<strong>Permanently deleted topic</strong><br />» %s',
	'LOG_DELETE_POST'		=> '<strong>Permanently deleted post</strong><br />» %s',

	// New
	'LOG_TOPIC_STIFLED'		=> '<strong>Deleted topic</strong><br />» %1$s',
	'LOG_TOPIC_TRASHED'		=> '<strong>Deleted topic to the Trash Bin</strong><br />» %1$s',
	'LOG_TOPIC_UNSTIFLED'	=> '<strong>Undeleted topic</strong><br />» %1$s',

	'LOG_POST_STIFLED'		=> '<strong>Deleted post</strong><br />» %1$s',
	'LOG_POST_TRASHED'		=> '<strong>Deleted post to the Trash Bin</strong><br />» %1$s',
	'LOG_POST_UNSTIFLED'	=> '<strong>Undeleted post</strong><br />» %1$s',
));

$lang = array_merge($lang, array(
	'LOG_TOPIC_STIFLED_2'	=> $lang['LOG_TOPIC_STIFLED'] . '<br />» » <strong>Reason:</strong> %2$s',
	'LOG_TOPIC_TRASHED_2'	=> $lang['LOG_TOPIC_TRASHED'] . '<br />» » <strong>Reason:</strong> %2$s',
	'LOG_TOPIC_UNSTIFLED_2'	=> $lang['LOG_TOPIC_UNSTIFLED'] . '<br />» » <strong>Reason:</strong> %2$s',

	'LOG_POST_STIFLED_2'	=> $lang['LOG_POST_STIFLED'] . '<br />» » <strong>Reason:</strong> %2$s',
	'LOG_POST_TRASHED_2'	=> $lang['LOG_POST_TRASHED'] . '<br />» » <strong>Reason:</strong> %2$s',
	'LOG_POST_UNSTIFLED_2'	=> $lang['LOG_POST_UNSTIFLED'] . '<br />» » <strong>Reason:</strong> %2$s',
));


// Administration
$lang = array_merge($lang, array(
	'PRIME_FAKE_DELETE'					=> 'Topic Deletions',
	'PRIME_FAKE_DELETE_EXPLAIN'			=> 'Determines how topics will be deleted.',
	'PRIME_FAKE_DELETE_DISABLE'			=> 'Permanently delete topic',
	'PRIME_FAKE_DELETE_ENABLE'			=> 'Keep topic and mark as deleted',
	'PRIME_FAKE_DELETE_AUTO_TRASH'		=> 'Move topic to the Trash Bin',
	'PRIME_FAKE_DELETE_SHADOW_ON'		=> 'Move to Trash Bin with a shadow',
	'PRIME_FAKE_DELETE_SHADOW_OFF'		=> 'Move to Trash Bin without a shadow',

	'PRIME_TRASH_FORUM'					=> 'Trash Bin Forum',
	'PRIME_TRASH_FORUM_EXPLAIN'			=> 'If selected, deleting a topic will move it to this forum. Deleting a topic in the Trash Bin will remove it permanently.',
	'PRIME_TRASH_FORUM_DISABLE'			=> 'Don\'t use a trash bin',
	'PRIME_TRASH_FORUM_DIVIDER'			=> '---------------------------',
	'PRIME_NO_TRASH_FORUM_ERROR'		=> 'You must set a Trash Bin forum when selecting the "%s" option',

	'PRIME_FOREVER_WHEN_DELETE_USER'	=> 'Permanently delete posts',
));

// Moderation
$lang = array_merge($lang, array(

	// Topics - Deleting
	'PRIME_DELETE_TOPIC_REASON'			=> 'Please enter a reason for the deletion',
	'PRIME_DELETE_TOPIC_FOREVER'		=> 'Permanently delete this topic',
	'PRIME_DELETE_TOPICS_FOREVER'		=> 'Permanently delete these topics',
	'PRIME_DELETE_TO_TRASH_BIN'			=> 'Move topic to the Trash Bin forum',
	'PRIME_DELETE_TOPIC_FOREVER_DENIED'	=> 'You cannot permanently delete topics in this forum.',
	'PRIME_DELETE_TOPIC_MIX_NOTICE'		=> 'Notice: Any topics that are already marked as deleted will not be affected.',

	// Topics - Deleted
	'PRIME_DELETED_TOPIC_SUCCESS'		=> 'The selected topic was successfully marked as deleted.',
	'PRIME_DELETED_TOPICS_SUCCESS'		=> 'The selected topics were successfully marked as deleted.',
	'PRIME_DELETED_TOPIC_FAILURE'		=> 'The selected topic was NOT marked as deleted.',
	'PRIME_DELETED_TOPICS_FAILURE'		=> 'The selected topics were NOT marked as deleted.',

	// Topics - Deleted to Trash Bin
	'PRIME_TRASHED_TOPIC_SUCCESS'		=> 'The selected topic was successfully moved to the Trash Bin forum.',
	'PRIME_TRASHED_TOPICS_SUCCESS'		=> 'The selected topics were successfully moved to the Trash Bin forum.',
	'PRIME_TRASHED_TOPIC_FAILURE'		=> 'The selected topic was NOT moved to the Trash Bin forum.',
	'PRIME_TRASHED_TOPICS_FAILURE'		=> 'The selected topics were NOT moved to the Trash Bin forum.',
	'PRIME_GO_TO_TRASH_BIN'				=> '%sGo to the Trash Bin forum%s',

	// Topics - Undeleting
	'PRIME_UNDELETE_TOPICS'				=> 'Undelete',
	'PRIME_UNDELETE_TOPIC_REASON'		=> 'Please enter a reason for reversing the deletion',
	'PRIME_UNDELETE_TOPIC_CONFIRM'		=> 'Are you sure you want to undelete this topic?',
	'PRIME_UNDELETE_TOPICS_CONFIRM'		=> 'Are you sure you want to undelete these topics?',
	'PRIME_UNDELETE_TOPICS_UNNEEDED'	=> 'The selected topics cannot be undeleted.',


	// Topics - Undeleted
	'PRIME_UNDELETED_TOPIC_SUCCESS'		=> 'The selected topic was successfully restored.',
	'PRIME_UNDELETED_TOPICS_SUCCESS'	=> 'The selected topics were successfully restored.',
	'PRIME_UNDELETED_TOPIC_FAILURE'		=> 'The selected topic was NOT restored.',
	'PRIME_UNDELETED_TOPICS_FAILURE'	=> 'The selected topics were NOT restored.',

	// Posts - Deleting
	'PRIME_DELETE_POST_REASON'			=> 'Please enter a reason for the deletion',
	'PRIME_DELETE_POST_FOREVER'			=> 'Permanently delete this post',
	'PRIME_DELETE_POSTS_FOREVER'		=> 'Permanently delete these posts',
	'PRIME_DELETE_POST_FOREVER_DENIED'	=> 'You cannot permanently delete posts in this forum.',
	'PRIME_DELETE_POST_MIX_NOTICE'		=> 'Notice: Any posts that are already marked as deleted will not be affected.',

	// Posts - Deleted
	'PRIME_DELETED_POST_SUCCESS'		=> 'The selected post was successfully marked as deleted.',
	'PRIME_DELETED_POSTS_SUCCESS'		=> 'The selected posts were successfully marked as deleted.',
	'PRIME_DELETED_POST_FAILURE'		=> 'The selected post was NOT marked as deleted.',
	'PRIME_DELETED_POSTS_FAILURE'		=> 'The selected posts were NOT marked as deleted.',

	// Posts - Undeleting
	'PRIME_UNDELETE_POST'				=> 'Undelete post',
	'PRIME_UNDELETE_POSTS'				=> 'Undelete posts',
	'PRIME_UNDELETE_POST_REASON'		=> 'Please enter a reason for reversing the deletion',
	'PRIME_UNDELETE_POST_CONFIRM'		=> 'Are you sure you want to undelete this post?',
	'PRIME_UNDELETE_POSTS_CONFIRM'		=> 'Are you sure you want to undelete these posts?',
	'PRIME_UNDELETE_POSTS_UNNEEDED'		=> 'The selected posts cannot be undeleted.',

	// Posts - Undeleted
	'PRIME_UNDELETED_POST_SUCCESS'		=> 'The selected post was successfully restored.',
	'PRIME_UNDELETED_POSTS_SUCCESS'		=> 'The selected posts were successfully restored.',
	'PRIME_UNDELETED_POST_FAILURE'		=> 'The selected post was NOT restored.',
	'PRIME_UNDELETED_POSTS_FAILURE'		=> 'The selected posts were NOT restored.',

));

?>