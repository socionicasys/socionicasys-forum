<?php
/**
 *
 * @author primehalo (Ken F. Innes IV) primehalo@gmail.com
 * @version $Id$
 * @copyright (c) 2010 Ken F. Innes IV
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();


if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// The name of the mod to be displayed during installation.
$mod_name = 'Prime Trash Bin';

/*
* The name of the config variable which will hold the currently installed version
* UMIL will handle checking, setting, and updating the version itself.
*/
$version_config_name = 'prime_trash_bin_version';



/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	'1.1.1' => array(

		'permission_add' => array(
			array('f_delete_forever', 0),
			array('f_undelete', 0),
			array('m_delete_forever', 1),
			array('m_undelete', 1),
		),

		'table_column_add' => array(
			array(TOPICS_TABLE, 'topic_deleted_from', array('UINT', 0)),
			array(TOPICS_TABLE, 'topic_deleted_user', array('UINT', 0)),
			array(TOPICS_TABLE, 'topic_deleted_time', array('TIMESTAMP', 0)),
			array(TOPICS_TABLE, 'topic_deleted_reason', array('VCHAR', '')),

			array(POSTS_TABLE, 'post_deleted_from', array('UINT', 0)),
			array(POSTS_TABLE, 'post_deleted_user', array('UINT', 0)),
			array(POSTS_TABLE, 'post_deleted_time', array('TIMESTAMP', 0)),
			array(POSTS_TABLE, 'post_deleted_reason', array('VCHAR', '')),
		),

		'cache_purge' => array(),
	),
);

// Include the UMIL Auto file, it handles the rest
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);