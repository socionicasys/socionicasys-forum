<?php
/**
*
* @package phpBB3
* @version $Id: prime_trash_bin_b.php,v 1.1.0 2010/01/03 03:00:00 PST primehalo Exp $
* @copyright (c) 2007-2010 Ken Innes IV
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Check to see if this file has already been included.
*/
if (!defined('INCLUDES_PRIME_TRASH_BIN_B_PHP'))
{
	define('INCLUDES_PRIME_TRASH_BIN_B_PHP', true);
	include ($phpbb_root_path . 'includes/prime_trash_bin_a.' . $phpEx);

	//=======================================================================//
	// Administration Functions                                              //
	//=======================================================================//

	/**
	*/
	function make_fake_delete_select($select_id = 0)
	{
		global $config;
		if (!isset($config['topic_delete_mode']))
		{
			set_config('topic_delete_mode', 0);
		}
		$options = build_select(array(
			0 => 'PRIME_FAKE_DELETE_DISABLE',
			1 => 'PRIME_FAKE_DELETE_ENABLE',
			2 => 'PRIME_FAKE_DELETE_AUTO_TRASH',
			3 => 'PRIME_FAKE_DELETE_SHADOW_ON',
			4 => 'PRIME_FAKE_DELETE_SHADOW_OFF',
		), $select_id);
		return($options);
	}


	/**
	*/
	function make_trash_select($select_id = false)
	{
		global $config, $user;
		if (!isset($config['trash_forum']))
		{
			set_config('trash_forum', 0);
		}
		$options  = '<option value="0">' . $user->lang['PRIME_TRASH_FORUM_DISABLE'] . '</option>';
		$options .= '<option disabled="disabled" class="disabled-option" value="0">' . $user->lang['PRIME_TRASH_FORUM_DIVIDER'] . '</option>';
		return($options . make_forum_select($select_id, false, false, true, true, true, false));
	}


	/**
	*/
	function validate_fake_delete_options($cfg_array, &$error)
	{
		$delete_mode = isset($cfg_array['topic_delete_mode']) ? $cfg_array['topic_delete_mode'] : 0;
		$msg[2] = 'PRIME_FAKE_DELETE_AUTO_TRASH';
		$msg[3] = 'PRIME_FAKE_DELETE_SHADOW_ON';
		$msg[4] = 'PRIME_FAKE_DELETE_SHADOW_OFF';
		if (empty($cfg_array['trash_forum']) && isset($msg[$delete_mode]))
		{
			global $user;
			$user->add_lang('mods/prime_trash_bin_b');
			$error[] = sprintf($user->lang['PRIME_NO_TRASH_FORUM_ERROR'], $user->lang[$msg[$delete_mode]]);
		}
	}

	/**
	* Alter the forum ID from which topics and posts were stifled (such as when the forum is being deleted).
	*/
	function update_stifled_from($old_forum_id, $new_forum_id)
	{
		global $db;

		$sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_deleted_from = ' . (int)$new_forum_id . ' WHERE topic_deleted_from = ' . (int)$old_forum_id;
		$db->sql_query($sql);

		$sql = 'UPDATE ' . POSTS_TABLE . ' SET post_deleted_from = ' . (int)$new_forum_id . ' WHERE post_deleted_from = ' . (int)$old_forum_id;
		$db->sql_query($sql);
	}


	//=======================================================================//
	// Moderation Functions                                                  //
	//=======================================================================//

	/**
	* $data -  key: user_id, value: post count to subtract
	*/
	function stifle_update_post_counts($data)
	{
		if (!empty($data))
		{
			global $db;

			// Get the post counts for the users who need updating, and adjust them accordingly.
			// If we tried to do it directly with an UPDATE, they could wind up with a negative post count.
			$post_count = array();
			$sql = 'SELECT user_id, user_posts'
					. ' FROM ' . USERS_TABLE
					. ' WHERE ' . $db->sql_in_set('user_id', array_keys($data));
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				if ($data[$row['user_id']])
				{
					$post_count[$row['user_id']] = ($row['user_posts'] - $data[$row['user_id']]) > 0 ? ($row['user_posts'] - $data[$row['user_id']]) : 0;
				}
			}
			$db->sql_freeresult($result);

			// Now that we have the correct post counts for each user, we can update the DB.
			foreach($post_count as $poster_id => $count)
			{
				$sql = 'UPDATE ' . USERS_TABLE
						. ' SET user_posts = ' . (int)$count
						. ' WHERE user_id = ' . (int)$poster_id;
				$db->sql_query($sql);
			}
		}
	}


	/**
	* $data -  key: user_id, value: post count to subtract
	*/
	function unstifle_update_post_counts($data)
	{
		if (!empty($data))
		{
			global $db;
			foreach ($data as $poster_id => $count)
			{
				$sql = 'UPDATE ' . USERS_TABLE
						. ' SET user_posts = user_posts + ' . (int)$count
						. ' WHERE user_id = ' . (int)$poster_id;
				$db->sql_query($sql);
			}
		}
	}


	/* Functions for topics
	------------------------------------------------------------------------ */

	/**
	* @return	mixed	If all are mock-deleted: true
	* 					Otherwise: the number of mock-deleted topics
	*/
	function are_topics_stifled($topic_ids)
	{
		global $db;

		$topic_ids = !is_array($topic_ids) ? array($topic_ids) : $topic_ids;
		$sql = 'SELECT topic_id'
				. ' FROM ' . TOPICS_TABLE
				. ' WHERE topic_deleted_time > 0'
				. ' AND ' . $db->sql_in_set('topic_id', $topic_ids);
		$result = $db->sql_query($sql);
		$stifled_count = $db->sql_affectedrows();
		$db->sql_freeresult($result);
		return(($stifled_count === count($topic_ids)) ? true : $stifled_count);
	}


	/**
	* The parameter $delete_post indicates that a user is deleting the only post
	*  in a topic, and they are a moderator who has already been validated.
	*/
	function mcp_stifle_topic($topic_ids, $delete_post = false)
	{
		global $db, $user, $auth, $template, $phpEx, $phpbb_root_path;

		if (!stifle_topics_enabled())
		{
			return(false);
		}
		if (empty($topic_ids))
		{
			trigger_error($user->lang['NO_TOPICS_SELECTED']);
		}

		$forum_id = request_var('f', 0);
		$user->add_lang('mcp');
		$topic_ids = is_array($topic_ids) ? $topic_ids : array($topic_ids);

		// If this is a shadow topic, then present the page for a normal, permanent deletion.
		if (count($topic_ids) == 1 && !empty($topic_ids[0]))
		{
			$sql = 'SELECT topic_status FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . (int)$topic_ids[0];
			$result = $db->sql_query($sql);
			$shadow_topic = (($row = $db->sql_fetchrow($result)) && $row['topic_status'] == ITEM_MOVED) ? true : false;
			$db->sql_freeresult($result);
			if ($shadow_topic)
			{
				return false;
			}
		}

		//If topics are supposed to be permanently deleted, we have to check for permission.
		$delete_forever = (request_var('delete_forever', false) && auth_fake_delete('delete', $forum_id));
		if (/*is_trash_forum($forum_id) ||*/ $delete_forever || ($already_stifled = are_topics_stifled($topic_ids)) === true)
		{
			if (!auth_fake_delete('delete', $forum_id)/* && (!$delete_post && !check_ids($topic_ids, TOPICS_TABLE, 'topic_id', array('m_delete_forever')))*/)
			{
				trigger_error($user->lang['NO_AUTH_OPERATION']);
			}
			return(false); // Return to allow normal, permanent deletion
		}
		if (!$delete_post && !check_ids($topic_ids, TOPICS_TABLE, 'topic_id', array('m_delete')))
		{
			trigger_error($user->lang['NO_AUTH_OPERATION']);
		}

		$user->add_lang('mods/prime_trash_bin_b');
		$redirect = request_var('redirect', build_url(array('_f_', 'action', 'quickmod')));
		$message = '';

		if (confirm_box(true))
		{
			$move_to_trash = (trash_enabled() && request_var('move_to_trash', false)) || trash_required();
			$view_trash = $auth->acl_get('f_list', get_trash_forum()) && $move_to_trash;
			$message = stifle_topics($topic_ids, true);
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'topic_id_list'	=> $topic_ids,
				'f'				=> $forum_id,
				'action'		=> 'delete_topic',
				'redirect'		=> $redirect,
			));
			if ($already_stifled)
			{
				$template->assign_var('ADDITIONAL_MSG', $user->lang['PRIME_DELETE_TOPIC_MIX_NOTICE']);
			}
			if (get_trash_forum() && !is_trash_forum($forum_id))
			{
				$template->assign_var('S_CAN_USE_TRASH_BIN', !trash_required() ? true : false);
				$template->assign_var('S_SHADOW_DEFAULT_DISABLED', !trash_required() ? true : false); // Disable the shadow option when the trash bin option is not checked.
				$template->assign_var('S_CAN_LEAVE_SHADOW', !trash_shadow_required() && !trash_shadow_disabled());
			}
			if (auth_fake_delete('delete', $forum_id))
			{
				$template->assign_vars(array('S_CAN_DELETE_FOREVER' => true, 'L_PRIME_DELETE_FOREVER' => ($user->lang[sizeof($topic_ids) == 1 ? 'PRIME_DELETE_TOPIC_FOREVER' : 'PRIME_DELETE_TOPICS_FOREVER'])));
			}
			$template->assign_var('L_PRIME_DELETE_REASON', $user->lang['PRIME_DELETE_TOPIC_REASON']);
			confirm_box(false, (sizeof($topic_ids) == 1) ? 'DELETE_TOPIC' : 'DELETE_TOPICS', $s_hidden_fields, 'prime_delete_confirm.html');
		}
		if (!$message)
		{
			$redirect = request_var('redirect', "index.$phpEx");
			$redirect = reapply_sid($redirect);
			redirect($redirect);
		}
		else
		{
			$prev_page = preg_match('/mode=delete/i', $redirect) ? '' : reapply_sid($redirect);
			if (!$prev_page && $topic_ids[0])
			{
				$prev_page = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $forum_id . '&amp;t=' . $topic_ids[0]);
			}
			$redirect = append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id);
			meta_refresh(3, $redirect);
			$message = $user->lang[$message];
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_FORUM'], '<a href="' . $redirect . '">', '</a>');
			$message .= ($prev_page && ($view_trash || !$move_to_trash)) ? '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $prev_page . '">', '</a>') : '';
			$message .= ($view_trash) ? '<br /><br />' . sprintf($user->lang['PRIME_GO_TO_TRASH_BIN'], '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . get_trash_forum()) . '">', '</a>') : '';
			trigger_error($message);
		}
	}


	/**
	*/
	function stifle_topics($topic_ids, $log = false)
	{
		global $db, $user, $phpbb_root_path, $phpEx;

		if (empty($topic_ids))
		{
			return('NO_TOPICS_SELECTED');
		}
		$sync = array();
		$topic_ids = is_array($topic_ids) ? $topic_ids : array($topic_ids);
		$trash_it = (request_var('move_to_trash', false) || trash_required()) ? (boolean)get_trash_forum() : false;
		$del_info['topic_deleted_user']   = $user->data['user_id'];
		$del_info['topic_deleted_time']   = time();
		$del_info['topic_deleted_reason'] = utf8_normalize_nfc(request_var('delete_reason', '', true));
		$post_count = array();

		// Check to see if this topic has yet to be marked for deletion (and get the forum id).
		$sql = 'SELECT topic_id, forum_id, topic_title'
				. ' FROM ' . TOPICS_TABLE
				. ' WHERE topic_deleted_time < 1'
				. ' AND ' . $db->sql_in_set('topic_id', $topic_ids);
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$sync[] = $row['forum_id'];
			$del_info['topic_deleted_from']	= $row['forum_id'];
			$sql = 'UPDATE ' . TOPICS_TABLE
					. ' SET ' . $db->sql_build_array('UPDATE', $del_info)
					. ' WHERE topic_id = ' . (int)$row['topic_id'];
			$db->sql_query($sql);
			if ($log)
			{
				add_log('mod', $row['forum_id'], $row['topic_id'], ($trash_it ? 'LOG_TOPIC_TRASHED' : 'LOG_TOPIC_STIFLED'), $row['topic_title'], $del_info['topic_deleted_reason']);
			}

			// Get info for updating user post counts
			$sql = 'SELECT poster_id'
					. ' FROM ' . POSTS_TABLE
					. ' WHERE (topic_id = ' . (int)$row['topic_id']
					. ' AND post_postcount > 0'
					. ' AND post_deleted_time = 0)';
			$result2 = $db->sql_query($sql);
			while ($row2 = $db->sql_fetchrow($result2))
			{
				$post_count[$row2['poster_id']] = empty($post_count[$row2['poster_id']]) ? 1 : $post_count[$row2['poster_id']] + 1;
			}
			$db->sql_freeresult($result2);

		}
		$db->sql_freeresult($result);

		// Update user post counts.
		stifle_update_post_counts($post_count);

		if ($trash_it)
		{
			return(move_to_trash($topic_ids));
		}

		if (!function_exists('sync'))
		{
			include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
		}
		sync('forum', 'forum_id', $sync);
		return(sizeof($topic_ids) == 1 ? 'PRIME_DELETED_TOPIC_SUCCESS' : 'PRIME_DELETED_TOPICS_SUCCESS');
	}


	/**
	* Moves topics to the Trash Bin forum. There is no need to check for permissions,
	* as we want to move these to the trash regardless of the user's permissions.
	*/
	function move_to_trash($topic_ids, $log = false)
	{
		global $db, $phpbb_root_path, $phpEx;

		$to_forum_id = (integer)get_trash_forum();
		if (empty($topic_ids) || empty($to_forum_id))
		{
			return(empty($topic_ids) ? 'NO_TOPICS_SELECTED' : 'FORUM_NOT_EXIST');
		}
		if(empty($to_forum_id)) //if (!sizeof($forum_data = get_forum_data($to_forum_id, false)))
		{
			return('FORUM_NOT_EXIST');
		}

		//$forum_id = check_ids($topic_ids, TOPICS_TABLE, 'topic_id', array('m_move'), true);
		$leave_shadow = (isset($_POST['move_leave_shadow']) || trash_shadow_required()) && !trash_shadow_disabled();
		$topics_moved = sizeof($topic_ids);
		$topics_authed_moved = 0;

		// Grab the topic data for the given topic IDs
		$sql_array = array(
			'SELECT'	=> 't.*, f.forum_name',
			'FROM'		=> array(TOPICS_TABLE	=> 't'),
			'LEFT_JOIN'	=> array(array(
				'FROM'	=> array(FORUMS_TABLE => 'f'),
				'ON'	=> 'f.forum_id = t.forum_id')),
			'WHERE'		=> $db->sql_in_set('t.topic_id', $topic_ids)
		);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$row['forum_id'] = (!$row['forum_id']) ? request_var('f', 0) : $row['forum_id'];
			$topic_data[$row['topic_id']] = $row;
		}
		$db->sql_freeresult($result);

		// All this sync code is taken straight from mcp_move_topic(), including
		// this $forum_sync_data which doesn't ever seemed to be used... ever.
		//$forum_sync_data = array();
		//$forum_sync_data[$forum_id] = current($topic_data);
		//$forum_sync_data[$to_forum_id] = $forum_data;
		foreach ($topic_data as $topic_id => $topic_info)
		{
			if ($topic_info['topic_approved'] == '1')
			{
				$topics_authed_moved++;
			}
		}
		$db->sql_transaction('begin');
		$sql = 'SELECT SUM(t.topic_replies + t.topic_approved) as topic_posts
			FROM ' . TOPICS_TABLE . ' t
			WHERE ' . $db->sql_in_set('t.topic_id', $topic_ids);
		$result = $db->sql_query($sql);
		$row_data = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$sync_sql = array();
		if ($row_data['topic_posts'])
		{
			// Move this line down inside the loop so $row['forum_id'] can replace $forum_id
			//$sync_sql[$forum_id][]		= 'forum_posts = forum_posts - ' . (int) $row_data['topic_posts'];
			$sync_sql[$to_forum_id][]	= 'forum_posts = forum_posts + ' . (int) $row_data['topic_posts'];
		}
		if ($topics_authed_moved)
		{
			$sync_sql[$to_forum_id][]	= 'forum_topics = forum_topics + ' . (int) $topics_authed_moved;
		}
		$sync_sql[$to_forum_id][]	= 'forum_topics_real = forum_topics_real + ' . (int) $topics_moved;

		// Move topics, but do not resync yet
		if (!function_exists('move_topics'))
		{
			include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
		}
		move_topics($topic_ids, $to_forum_id, false);

		$forum_ids = array($to_forum_id);
		foreach ($topic_data as $topic_id => $row)
		{
			// Get the list of forums to resync, add a log entry
			$forum_ids[] = $row['forum_id'];
			if ($log)
			{
				add_log('mod', $to_forum_id, $topic_id, 'LOG_MOVE', $row['forum_name']);
			}
			// If we have moved a global announcement, we need to correct the topic type
			if ($row['topic_type'] == POST_GLOBAL)
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_type = ' . POST_ANNOUNCE . '
					WHERE topic_id = ' . (int)$row['topic_id'];
				$db->sql_query($sql);
			}

			// Leave a redirection if required and only if the topic is visible to users
			if ($leave_shadow && $row['topic_approved'] && $row['topic_type'] != POST_GLOBAL)
			{
				$shadow = $row;
				unset($shadow['forum_name']);
				unset($shadow['topic_id']);
				$shadow['topic_approved']	= 1;
				$shadow['topic_status']		= ITEM_MOVED;
				$shadow['topic_type']		= POST_NORMAL;
				$shadow['topic_moved_id']	= (int) $row['topic_id'];
				$db->sql_query('INSERT INTO ' . TOPICS_TABLE . $db->sql_build_array('INSERT', $shadow));
				$topics_authed_moved--;
				$topics_moved--;
			}
			// I moved this code here inside the loop so $row['forum_id'] can replace $forum_id
			if ($row_data['topic_posts'] && !isset($sync_sql[$row['forum_id']]['forum_posts']))
			{
				$sync_sql[$row['forum_id']]['forum_posts']	= 'forum_posts = forum_posts - ' . (int) $row_data['topic_posts'];
			}
			if (!isset($sync_sql[$row['forum_id']]['forum_topics_real']))
			{
				$sync_sql[$row['forum_id']]['forum_topics_real']	= 'forum_topics_real = forum_topics_real - ' . (int) $topics_moved;
			}
			if ($topics_authed_moved && !isset($sync_sql[$row['forum_id']]['forum_topics']))
			{
				$sync_sql[$row['forum_id']]['forum_topics']	= 'forum_topics = forum_topics - ' . (int) $topics_authed_moved;
			}
		}
		unset($topic_data);

		// I moved this code up inside the loop so $row['forum_id'] can replace $forum_id
		//$sync_sql[$forum_id][]	= 'forum_topics_real = forum_topics_real - ' . (int) $topics_moved;
		//if ($topics_authed_moved)
		//{
		//	$sync_sql[$forum_id][]	= 'forum_topics = forum_topics - ' . (int) $topics_authed_moved;
		//}
		foreach ($sync_sql as $forum_id_key => $array)
		{
			$sql = 'UPDATE ' . FORUMS_TABLE . '
				SET ' . implode(', ', $array) . '
				WHERE forum_id = ' . (int)$forum_id_key;
			$db->sql_query($sql);
		}
		$db->sql_transaction('commit');

		sync('forum', 'forum_id', $forum_ids);
		return((sizeof($topic_ids) == 1 ? 'PRIME_TRASHED_TOPIC_SUCCESS' : 'PRIME_TRASHED_TOPICS_SUCCESS'));
	}


	/**
	*/
	function mcp_unstifle_topic($topic_ids)
	{
		global $user, $template, $phpEx, $phpbb_root_path;

		$user->add_lang('mcp');
		$user->add_lang('mods/prime_trash_bin_b');

		if (empty($topic_ids))
		{
			trigger_error($user->lang['NO_TOPICS_SELECTED']);
		}
		$topic_ids = is_array($topic_ids) ? $topic_ids : array($topic_ids);

		if (!check_ids($topic_ids, TOPICS_TABLE, 'topic_id', array('a_', 'm_undelete')))
		{
			trigger_error($user->lang['NO_AUTH_OPERATION']);
		}
		if (!are_topics_stifled($topic_ids))
		{
			trigger_error($user->lang['PRIME_UNDELETE_TOPICS_UNNEEDED']);
		}

		$redirect = request_var('redirect', build_url(array('_f_', 'action', 'quickmod')));
		$forum_id = request_var('f', 0);
		$message = '';

		if (confirm_box(true))
		{
			$message = unstifle_topics($topic_ids, true);
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'topic_id_list'	=> $topic_ids,
				'f'				=> $forum_id,
				'action'		=> 'undelete_topic',
				'redirect'		=> $redirect,
			));
			$template->assign_var('L_PRIME_DELETE_REASON', $user->lang['PRIME_UNDELETE_TOPIC_REASON']);
			confirm_box(false, (sizeof($topic_ids) == 1) ? 'PRIME_UNDELETE_TOPIC' : 'PRIME_UNDELETE_TOPICS', $s_hidden_fields, 'prime_delete_confirm.html');
		}
		$redirect = reapply_sid($redirect);
		if (!$message)
		{
			redirect($redirect);
		}
		else
		{
			meta_refresh(3, $redirect);
			$message = $user->lang[$message];
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>');
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_FORUM'], '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", "f=$forum_id") . '">', '</a>');
			trigger_error($message);
		}
	}


	/**
	*/
	function unstifle_topics($topic_ids, $log = false)
	{
		global $db, $phpbb_root_path, $phpEx;

		if (empty($topic_ids))
		{
			return('NO_TOPIC_SELECTED');
		}
		if (!is_array($topic_ids))
		{
			$topic_ids = array($topic_ids);
		}

		$sync = array();
		$move = array();
		$updated_info['topic_deleted_from']	= 0;
		$updated_info['topic_deleted_user']	= 0;
		$updated_info['topic_deleted_time']	= 0;
		$updated_info['topic_deleted_reason']	= utf8_normalize_nfc(request_var('delete_reason', '', true));
		$post_count = array();

		$sql = 'SELECT topic_id, forum_id, topic_deleted_from, topic_title, topic_type'
				. ' FROM ' . TOPICS_TABLE
				. ' WHERE topic_deleted_time > 0'
				. ' AND ' . $db->sql_in_set('topic_id', $topic_ids);
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			if (auth_fake_delete('undelete', $row['forum_id']))
			{
				// If the topic used to be a global announcement, we must again make it so.
				if ($row['topic_deleted_from'] == 0 && $row['topic_type'] == POST_ANNOUNCE)
				{
					$updated_info['topic_type'] = POST_GLOBAL;
				}
				$sql = 'UPDATE ' . TOPICS_TABLE
						. ' SET ' . $db->sql_build_array('UPDATE', $updated_info)
						. ' WHERE topic_id = ' . (int)$row['topic_id'];
				$db->sql_query($sql);
				if (is_trash_forum($row['forum_id']))
				{
					$move[$row['topic_deleted_from']][] = $row['topic_id'];
				}
				$sync[] = $row['forum_id'];
				$sync[] = $row['topic_deleted_from'];
				$success = (empty($success) ? '' : ',') . $row['topic_id'];
				if ($log)
				{
					add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_TOPIC_UNSTIFLED', $row['topic_title'], $updated_info['topic_deleted_reason']);
				}

				// Get info for updating user post counts
				$sql = 'SELECT poster_id'
						. ' FROM ' . POSTS_TABLE
						. ' WHERE (topic_id = ' . (int)$row['topic_id']
						. ' AND post_postcount > 0'
						. ' AND post_deleted_time = 0)';
				$result2 = $db->sql_query($sql);
				while ($row2 = $db->sql_fetchrow($result2))
				{
					$post_count[$row2['poster_id']] = empty($post_count[$row2['poster_id']]) ? 1 : $post_count[$row2['poster_id']] + 1;
				}
				$db->sql_freeresult($result2);

			}
		}
		$db->sql_freeresult($result);

		// Update user post counts
		unstifle_update_post_counts($post_count);

		// Get out of here if nothing happened.
		if (empty($success))
		{
			return('NO_AUTH_OPERATION');
		}

		if (!function_exists('sync') || !function_exists('move_topics'))
		{
			include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
		}

		// Move topics back to their pre-deletion forums
		foreach ($move as $forum_id => $topic_ids)
		{
			move_topics($topic_ids, $forum_id);
		}
		sync('forum', 'forum_id', $sync);
		return((sizeof($topic_ids) == 1) ? 'PRIME_UNDELETED_TOPIC_SUCCESS' : 'PRIME_UNDELETED_TOPICS_SUCCESS');
	}


	/* Functions for posts
	------------------------------------------------------------------------ */

	/**
	* Determines how many posts are mock-deleted.
	* Returns true if all are mock-deleted, otherwise
	* returns the number of posts that are be mock-deleted.
	*/
	function are_posts_stifled($post_ids)
	{
		global $db;

		$post_ids = !is_array($post_ids) ? array($post_ids) : $post_ids;
		$sql = 'SELECT post_id'
				. ' FROM ' . POSTS_TABLE
				. ' WHERE post_deleted_time > 0'
				. ' AND ' . $db->sql_in_set('post_id', $post_ids);
		$result = $db->sql_query($sql);
		$stifled_count = $db->sql_affectedrows();
		$db->sql_freeresult($result);
		return(($stifled_count === count($post_ids)) ? true : $stifled_count);
	}


	/**
	* Determines how many posts can be mock-deleted.
	* Returns true if all can be mock deleted, otherwise
	* returns the number of posts that can be mock deleted.
	*/
	function can_posts_be_stifled($post_ids)
	{
		global $db;

		if (empty($post_ids))
		{
			return 0;
		}
		$post_ids = !is_array($post_ids) ? array($post_ids) : $post_ids;
		$sql = 'SELECT post_id'
				. ' FROM ' . POSTS_TABLE
				. ' WHERE (post_deleted_time = 0 AND post_approved > 0)'
				. ' AND ' . $db->sql_in_set('post_id', $post_ids);
		$result = $db->sql_query($sql);
		$safe_deletable_count = $db->sql_affectedrows();
		$db->sql_freeresult($result);
		return(($safe_deletable_count === count($post_ids)) ? true : $safe_deletable_count);

	}


	function stifle_users_posts($user_id, $username, $adm_link_back)
	{
		global $user;

		$delete_forever = (request_var('delete_forever', false) && auth_fake_delete('delete')) ? true : false;
		if (stifle_posts_enabled() && !$delete_forever)
		{
			$message = stifle_posts($user_id, 'poster_id');
			add_log('admin', 'LOG_USER_DEL_POSTS', $username);
			trigger_error($user->lang[$message] . $adm_link_back);
		}
	}


	/**
	* Marks posts as deleted.
	*/
	function stifle_posts($ids, $where_type = 'post_id', $log = false)
	{
		global $db, $user;

		if (empty($ids))
		{
			return('NO_POST_SELECTED');
		}
		if (is_bool($where_type))
		{
			$log = $where_type;
			$where_type = 'post_id';
		}

		$ids = is_array($ids) ? $ids : array($ids);
		$return = '';

		// Only grab posts that have yet to be marked for deletion.
		$sql = 'SELECT post_deleted_time, post_deleted_user, post_deleted_reason, post_id, topic_id, forum_id, post_subject, poster_id, post_postcount'
				. ' FROM ' . POSTS_TABLE
				. ' WHERE (post_deleted_time = 0 AND post_approved > 0)'
				. ' AND ' . $db->sql_in_set($where_type, $ids);

		$result = $db->sql_query($sql);

		$del_info['post_deleted_time']   = time();
		$del_info['post_deleted_user']   = $user->data['user_id'];
		$del_info['post_deleted_reason'] = utf8_normalize_nfc(request_var('delete_reason', '', true));
		$post_count = array();
		$delete_topics = array();
		$sync_topics = array();
		$sync_forums = array();

		while ($row = $db->sql_fetchrow($result))
		{
			$sync_topics[] = $row['topic_id'];
			$sync_forums[] = $row['forum_id'];

			// We've already determined that the whole topic will be deleted.
			//if (isset($delete_topics[$row['topic_id']]))
			//{
			//	continue;
			//}

			// Check if we should just mock-delete the entire topic.
			if (stifle_topics_enabled() && !is_trash_forum($row['forum_id']) && !isset($delete_topics[$row['topic_id']]))
			{
				$sql = 'SELECT topic_deleted_time, topic_first_post_id, topic_last_post_id'
						. ' FROM ' . TOPICS_TABLE
						. ' WHERE topic_id = ' . (int)$row['topic_id']
				;
				$topic_result = $db->sql_query($sql);
				$topic_data = $db->sql_fetchrow($topic_result);
				$db->sql_freeresult($topic_result);

				// The post is the only one in the topic, so mock-delete this topic
				// unless the topic is already marked as deleted.
				if ($topic_data['topic_first_post_id'] == $topic_data['topic_last_post_id'] && empty($topic_data['topic_deleted_time']))
				{
					// Double-check just to be sure
					$sql = 'SELECT COUNT(post_id) AS posts_in_topic FROM ' . POSTS_TABLE . ' WHERE topic_id = ' . (int)$row['topic_id'] . ' AND post_approved = 1 AND post_deleted_time = 0';
					$result = $db->sql_query($sql);
					$posts_in_topic = (int) $db->sql_fetchfield('posts_in_topic');
					$db->sql_freeresult($result);
					if ($posts_in_topic <= 1)
					{
						$delete_topics[$row['topic_id']] = $row['topic_id'];
						continue;
					}
				}
			}

			// Keep track of user post counts so we can update them
			if ($row['post_postcount'])
			{
				$post_count[$row['poster_id']] = empty($post_count[$row['poster_id']]) ? 1 : $post_count[$row['poster_id']] + 1;
			}

			// Mark the post as deleted
			$sql = 'UPDATE ' . POSTS_TABLE
					. ' SET ' . $db->sql_build_array('UPDATE', $del_info)
					. ' WHERE post_id = ' . (int)$row['post_id'];
			$db->sql_query($sql);

			// Add a log entry if required
			if ($log)
			{
				add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_POST_STIFLED', $row['post_subject'], $del_info['post_deleted_reason']);
			}

			$return = !$return ? 'PRIME_DELETED_POST_SUCCESS' : 'PRIME_DELETED_POSTS_SUCCESS';
		}
		$db->sql_freeresult($result);

		// Update user post counts.
		stifle_update_post_counts($post_count);

		// Time to delete the topics, if need be.
		if (!empty($delete_topics))
		{
			$topic_msg = stifle_topics($delete_topics, $log);
			$return = $return . ($return ? '|' : '') . $topic_msg;
		}
		$return = empty($return) ? 'PRIME_DELETED_POST_FAILURE' : $return;

		// Update the last post information for the topics and forums
		if (count($sync_topics))
		{
			global $phpbb_root_path, $phpEx;
			if (!function_exists('sync') || !function_exists('move_posts'))
			{
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}
			sync('topic', 'topic_id', $sync_topics);
			sync('forum', 'forum_id', $sync_forums);
		}

		return($return);
	}


	/**
	*/
	function  mcp_stifle_post($post_ids)
	{
		global $auth, $user, $db, $template, $phpEx, $phpbb_root_path;

		if (!stifle_posts_enabled())
		{
			return(false);
		}
		if (empty($post_ids))
		{
			trigger_error($user->lang['NO_POST_SELECTED']);
		}
		$user->add_lang('mcp');
		$post_ids = is_array($post_ids) ? $post_ids : array($post_ids);
		$post_ids_temp = $post_ids; // the check_ids() function will alter the passed in array of ids
		$can_full_delete = check_ids($post_ids_temp, POSTS_TABLE, 'post_id', array('a_', 'm_delete_forever'));

		// Check if posts are already marked as deleted or if the delete forever checkbox was selected.
		if (!($can_be_stifled = can_posts_be_stifled($post_ids)) || request_var('delete_forever', false))
		{
			if (!$can_full_delete)
			{
				trigger_error($user->lang['NO_AUTH_OPERATION']);
			}
			return(false); // Return to allow normal, permanent deletion
		}
		if (!check_ids($post_ids, POSTS_TABLE, 'post_id', array('m_delete')))
		{
			trigger_error($user->lang['NO_AUTH_OPERATION']);
		}

		$user->add_lang('mods/prime_trash_bin_b');
		$redirect = request_var('redirect', build_url(array('_f_', 'action', 'quickmod')));
		$forum_id = request_var('f', 0);
		$topic_id = request_var('t', 0);

		if (confirm_box(true))
		{
			$result_msg = stifle_posts($post_ids, 'post_id', true);

			if (strpos($result_msg, 'TOPIC') !== false)
			{
				$return_link[] = sprintf($user->lang['RETURN_TOPIC'], '<a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id") . '">', '</a>');
				$result_msg .= '|' . 'EMPTY_TOPICS_REMOVED_WARNING';
			}
			if (strpos($result_msg, 'SUCCESS') !== false)
			{
				meta_refresh(3, $redirect);
			}
			$return_link[] = sprintf($user->lang['RETURN_FORUM'], '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id) . '">', '</a>');

			$message = '';
			foreach(explode('|', $result_msg) as $msg)
			{
				$message .= ($message ? '<br /><br />' : '') . (isset($user->lang[$msg]) ? $user->lang[$msg] : $msg);
			}
			trigger_error($message . '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>') . '<br /><br />' . implode('<br /><br />', $return_link));
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'post_id_list'	=> $post_ids,
				'f'				=> $forum_id,
				'action'		=> 'delete_post',
				'redirect'		=> $redirect)
			);
			if ($can_be_stifled !== true)
			{
				$template->assign_var('ADDITIONAL_MSG', $user->lang['PRIME_DELETE_POST_MIX_NOTICE']);
			}
			if ($can_full_delete)
			{
				$template->assign_vars(array('S_CAN_DELETE_FOREVER' => true, 'L_PRIME_DELETE_FOREVER' => ($user->lang[sizeof($post_ids) == 1 ? 'PRIME_DELETE_POST_FOREVER' : 'PRIME_DELETE_POSTS_FOREVER'])));
			}
			$template->assign_var('L_PRIME_DELETE_REASON', $user->lang['PRIME_DELETE_POST_REASON']);
			confirm_box(false, (sizeof($post_ids) == 1) ? 'DELETE_POST' : 'DELETE_POSTS', $s_hidden_fields, 'prime_delete_confirm.html');
		}
	}


	/**
	*/
	function unstifle_posts($post_ids, $log = false)
	{
		global $db, $phpbb_root_path, $phpEx;

		if (empty($post_ids))
		{
			return('NO_POST_SELECTED');
		}
		$post_ids = is_array($post_ids) ? $post_ids : array($post_ids);

		$sync_topics = array();
		$sync_forums = array();
		$move = array();
		$updated_info['post_deleted_from'] = 0;
		$updated_info['post_deleted_user'] = 0;
		$updated_info['post_deleted_time'] = 0;
		$updated_info['post_deleted_reason'] = utf8_normalize_nfc(request_var('delete_reason', '', true));
		$post_count = array();

		$sql = 'SELECT post_id, topic_id, forum_id, post_deleted_from, post_subject, poster_id, post_postcount'
				. ' FROM ' . POSTS_TABLE
				. ' WHERE post_deleted_time > 0'
				. ' AND ' . $db->sql_in_set('post_id', $post_ids);
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			if (auth_fake_delete('undelete', $row['forum_id']))
			{
				if ($row['post_postcount'])
				{
					$post_count[$row['poster_id']] = empty($post_count[$row['poster_id']]) ? 1 : $post_count[$row['poster_id']] + 1;
				}

				$sql = 'UPDATE ' . POSTS_TABLE
						. ' SET ' . $db->sql_build_array('UPDATE', $updated_info)
						. ' WHERE post_id = ' . (int)$row['post_id'];
				$db->sql_query($sql);
				//if (is_trash_forum($row['forum_id']))
				//{
				//	$move[$row['topic_deleted_from']][] = $row['topic_id'];
				//}
				$sync_topics[] = $row['topic_id'];
				$sync_forums[] = $row['forum_id'];
				$sync_forums[] = $row['post_deleted_from'];
				$success = true;
				if ($log)
				{
					add_log('mod', $row['forum_id'], $row['topic_id'], 'LOG_POST_UNSTIFLED', $row['post_subject'], $updated_info['post_deleted_reason']);
				}
			}
		}
		$db->sql_freeresult($result);

		// Update user post counts.
		unstifle_update_post_counts($post_count);

		// Get out of here if nothing happened.
		if (empty($success))
		{
			return(sizeof($post_ids) == 1 ? 'PRIME_UNDELETED_POST_FAILURE' : 'PRIME_UNDELETED_POSTS_FAILURE');
		}

		// Update the last post information for the topics and forums
		if (count($sync_topics))
		{
			if (!function_exists('sync') || !function_exists('move_posts'))
			{
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}

			// Move posts back to their pre-deletion topics
			//foreach ($move as $forum_id => $topic_ids)
			//{
			//	move_topics($topic_ids, $forum_id);
			//}
			sync('topic', 'topic_id', $sync_topics);
			sync('forum', 'forum_id', $sync_forums);
		}
		return((sizeof($post_ids) == 1) ? 'PRIME_UNDELETED_POST_SUCCESS' : 'PRIME_UNDELETED_POST_SUCCESS');
	}


	/**
	*/
	function mcp_unstifle_post($post_ids, $forum_id = 0)
	{
		global $user, $template, $phpEx, $phpbb_root_path;

		$user->add_lang('mcp');
		$user->add_lang('mods/prime_trash_bin_b');

		if (empty($post_ids))
		{
			trigger_error($user->lang['NO_POST_SELECTED']);
		}
		$post_ids = is_array($post_ids) ? $post_ids : array($post_ids);
		$forum_id = request_var('f', 0);

		if (function_exists('check_ids') && !check_ids($post_ids, POSTS_TABLE, 'post_id', array('a_', 'm_undelete')))
		{
			trigger_error($user->lang['NO_AUTH_OPERATION']);
		}
		if(!auth_fake_delete('undelete', $forum_id))
		{
			trigger_error($user->lang['NO_AUTH_OPERATION']);
		}
		if (!are_posts_stifled($post_ids))
		{
			trigger_error($user->lang['PRIME_UNDELETE_POSTS_UNNEEDED']);
		}
		$redirect = request_var('redirect', build_url(array('_f_', 'action', 'quickmod')));
		$topic_id = request_var('t', 0);
		$message = '';

		if (confirm_box(true))
		{
			$message = unstifle_posts($post_ids, true);
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'post_id_list'	=> $post_ids,
				'f'				=> $forum_id,
				't'				=> $topic_id,
				'action'		=> 'undelete_post',
				'redirect'		=> $redirect,
			));
			$template->assign_var('L_PRIME_DELETE_REASON', $user->lang['PRIME_UNDELETE_POST_REASON']);
			confirm_box(false, (sizeof($post_ids) == 1) ? 'PRIME_UNDELETE_POST' : 'PRIME_UNDELETE_POSTS', $s_hidden_fields, 'prime_delete_confirm.html');
		}
		$redirect = reapply_sid($redirect);
		if (!$message)
		{
			redirect($redirect);
		}
		else
		{
			meta_refresh(3, $redirect);
			$message = $user->lang[$message];
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $redirect . '">', '</a>');
			$message .= empty($topic_id) ? '' : '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", "t=$topic_id") . '">', '</a>');
			$message .= empty($forum_id) ? '' : '<br /><br />' . sprintf($user->lang['RETURN_FORUM'], '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", "f=$forum_id") . '">', '</a>');
			trigger_error($message);
		}
	}


	/**
	*/
	function stifle_post($forum_id, $topic_id, $post_id, &$data, $log = false)
	{
		global $user;

		//$post_mode = ($data['topic_first_post_id'] == $data['topic_last_post_id']) ? 'delete_topic' : (($data['topic_first_post_id'] == $post_id) ? 'delete_first_post' : (($data['topic_last_post_id'] == $post_id) ? 'delete_last_post' : 'delete'));
		$next_post_id = $post_id;
		$result_msg = stifle_posts($post_id, 'post_id', $log);

		//switch ($post_mode)
		//{
		//	case 'delete_topic':
		//	break;
		//
		//	case 'delete_first_post':
		//	break;
		//
		//	case 'delete_last_post':
		//	break;
		//
		//	case 'delete':
		//	break;
		//}
		return($next_post_id);
	}


	/**
	*/
	function handle_post_stifle($forum_id, $topic_id, $post_id, &$post_data)
	{
		global $user, $db, $auth, $phpbb_root_path, $phpEx, $template;

		$delete_forever = (request_var('delete_forever', false) && auth_fake_delete('delete', $forum_id));
		if (!stifle_posts_enabled() || $delete_forever)
		{
			return(false);
		}
		$user->add_lang('mods/prime_trash_bin_b');

		// The post can only be permanently deleted, so check for permission
		if (!can_stifle_post($post_data))
		{
			// If user can permanent delete then move on like we were never here.
			if (auth_fake_delete('delete', $forum_id))
			{
				return(false);
			}
			trigger_error($user->lang['PRIME_DELETE_POST_FOREVER_DENIED']);
		}

		// This is the only post in the topic AND the user is a moderator
		// so lets do a topic deletion instead.
		if($auth->acl_get('m_delete', $forum_id) && !empty($post_data['topic_first_post_id']) && !empty($post_data['topic_last_post_id']) && $post_data['topic_first_post_id'] == $post_data['topic_last_post_id'])
		{
			$sql = 'SELECT COUNT(post_id) AS posts_in_topic FROM ' . POSTS_TABLE . ' WHERE topic_id = ' . (int)$topic_id . ' AND post_approved = 1 AND post_deleted_time = 0';
			$result = $db->sql_query($sql);
			$posts_in_topic = (int) $db->sql_fetchfield('posts_in_topic');
			$db->sql_freeresult($result);
			if ($posts_in_topic <= 1)
			{
				mcp_stifle_topic($topic_id, true);
				return(true);
			}
		}

		// If moderator removing post or user itself removing post, present a confirmation screen
		// These are normal delete permissions, since we're not permanent deleting.
		if ($auth->acl_get('m_delete', $forum_id) || ($post_data['poster_id'] == $user->data['user_id'] && $user->data['is_registered'] && $auth->acl_get('f_delete', $forum_id) && $post_id == $post_data['topic_last_post_id']))
		{
			if (confirm_box(true))
			{
				$data = array(
					'user_id'				=> $user->data['user_id'],
					'topic_first_post_id'	=> $post_data['topic_first_post_id'],
					'topic_last_post_id'	=> $post_data['topic_last_post_id'],
					'topic_approved'		=> $post_data['topic_approved'],
					'topic_type'			=> $post_data['topic_type'],
					'post_approved'			=> $post_data['post_approved'],
					'post_reported'			=> $post_data['post_reported'],
					'post_time'				=> $post_data['post_time'],
					'poster_id'				=> $post_data['poster_id'],
					'post_postcount'		=> $post_data['post_postcount']
				);

				$next_post_id = stifle_post($forum_id, $topic_id, $post_id, $data, true);

				if ($post_data['topic_first_post_id'] == $post_data['topic_last_post_id'])
				{
					//add_log('mod', $forum_id, $topic_id, (stifle_topics_enabled() ? 'LOG_TOPIC_STIFLED' : 'LOG_DELETE_TOPIC'), $post_data['topic_title']);

					$meta_info = append_sid("{$phpbb_root_path}viewforum.$phpEx", "f=$forum_id");
					$message = $user->lang['PRIME_DELETED_POST_SUCCESS'];
				}
				else
				{
					//add_log('mod', $forum_id, $topic_id, 'LOG_POST_STIFLED', $post_data['post_subject']);

					$meta_info = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;t=$topic_id&amp;p=$next_post_id") . "#p$next_post_id";
					$message = $user->lang['PRIME_DELETED_POST_SUCCESS'] . '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . $meta_info . '">', '</a>');
				}

				meta_refresh(3, $meta_info);
				$message .= '<br /><br />' . sprintf($user->lang['RETURN_FORUM'], '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id) . '">', '</a>');
				trigger_error($message);
			}
			else
			{
				$s_hidden_fields = build_hidden_fields(array(
					'p'		=> $post_id,
					'f'		=> $forum_id,
					'mode'	=> 'delete',
				));
				if (auth_fake_delete('delete', $forum_id))
				{
					global $template;
					$template->assign_vars(array('S_CAN_DELETE_FOREVER' => true, 'L_PRIME_DELETE_FOREVER' => $user->lang['PRIME_DELETE_POST_FOREVER']));
				}
				$template->assign_var('L_PRIME_DELETE_REASON', $user->lang['PRIME_DELETE_POST_REASON']);
				confirm_box(false, 'DELETE_MESSAGE', $s_hidden_fields, 'prime_delete_confirm.html');
			}
		}

		// If we are here the user is not able to delete - present the correct error message
		if ($post_data['poster_id'] != $user->data['user_id'] && $auth->acl_get('f_delete', $forum_id))
		{
			trigger_error('DELETE_OWN_POSTS');
		}

		if ($post_data['poster_id'] == $user->data['user_id'] && $auth->acl_get('f_delete', $forum_id) && $post_id != $post_data['topic_last_post_id'])
		{
			trigger_error('CANNOT_DELETE_REPLIED');
		}
		trigger_error('USER_CANNOT_DELETE');
	}
}
?>