<?php
/**
*
* prime_post_revisions [English]
*
* @package language
* @version $Id: prime_post_revisions.php,v 1.2.0 2008/07/21 13:45:00 primehalo Exp $
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
	// Viewing posts
	'PRIME_POST_REVISIONS_VIEW'				=> 'View post history.',	// Text for the link to view the revision history

	// Viewing revisions
	'PRIME_POST_REVISIONS_VIEWING'			=> 'Viewing post history',
	'PRIME_POST_REVISIONS_VIEWING_EXPLAIN'	=> 'This page shows all versions of the post, starting with the most current version.',
	'PRIME_POST_REVISIONS_TITLE'			=> 'Viewing post history: %s',	// The %s is the post title
	'PRIME_POST_REVISIONS_FIRST'			=> 'Original post: %s',			// The %s is the post title
	'PRIME_POST_REVISIONS_FINAL'			=> 'Current post: %s',			// The %s is the post title
	'PRIME_POST_REVISIONS_COUNT'			=> 'Revision %d: %s',			// The %s is the post title
	'PRIME_POST_REVISIONS_INFO'				=> 'Edited by %1$s on %2$s.',
	'PRIME_POST_REVISIONS_NO_SUBJECT'		=> '[no subject]',	

	// Delete a revision
	'PRIME_POST_REVISIONS_DELETE'			=> 'Delete Revision',
	'PRIME_POST_REVISIONS_DELETE_CONFIRM'	=> 'Are you sure you want to delete this revision?',
	'PRIME_POST_REVISIONS_DELETE_DENIED'	=> 'You lack the necessary permissions to delete this revision.',
	'PRIME_POST_REVISIONS_DELETE_FAILED'	=> 'An error occured while attempting to delete the revision.',
	'PRIME_POST_REVISIONS_DELETE_SUCCESS'	=> 'The revision has been successfully removed.',
	'PRIME_POST_REVISIONS_DELETE_INVALID'	=> 'No post revision has been selected for removal.',

	// Delete all revisions
	'PRIME_POST_REVISIONS_DELETES'			=> 'Delete all revisions.',
	'PRIME_POST_REVISIONS_DELETES_CONFIRM'	=> 'Are you sure you want to delete these revisions?',
	'PRIME_POST_REVISIONS_DELETES_DENIED'	=> 'You lack the necessary permissions to delete these revisions.',
	'PRIME_POST_REVISIONS_DELETES_FAILED'	=> 'An error occured while attempting to delete these revisions.',
	'PRIME_POST_REVISIONS_DELETES_SUCCESS'	=> 'The revisions have been successfully removed.',
	'PRIME_POST_REVISIONS_DELETES_INVALID'	=> 'No post revisions have been selected for removal.',
));

?>