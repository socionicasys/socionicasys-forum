<?php
/**
*
* @package phpBB3
* @version $Id: prime_trash_bin_a.php,v 1.1.0 2010/12/28 03:00:00 PST primehalo Exp $
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
* Include only once.
*/
if (!defined('INCLUDES_PRIME_TRASH_BIN_A_PHP'))
{
	define('INCLUDES_PRIME_TRASH_BIN_A_PHP', true);

	/**
	* Options
	*/
	define('SHOW_STIFLED_TOPIC_TITLE', true);	// Show a deleted topic's title?
	define('SHOW_PLACEHOLDER', true);			// Show a placeholder for deleted posts/topics?
	define('SHOW_ALL_IN_TRASH_BIN', true); 		// Allow all users to view the contents of deleted posts within the Trash Bin (assuming users have permission to view the Trash Bin)?
	define('MINIMIZE_DELETED_POST', true);		// Show the bare minimum? (Otherwise, show everything but the actual message.)

	/**
	* Constants
	*/
	// The following are for $config['topic_delete_mode']
	define('PRIME_DELETE_MODE_FOREVER', 0);
	define('PRIME_DELETE_MODE_STIFLE', 1);
	define('PRIME_DELETE_MODE_TRASH', 2);
	define('PRIME_DELETE_MODE_SHADOW', 3);
	define('PRIME_DELETE_MODE_TRASH_ONLY', 4);

	/**
	* Determines authorization for fake-delete actions.
	*/
	function auth_fake_delete($mode, $forum_id = 0)
	{
		global $auth, $config, $user;

		$type  = strpos($mode, '|') !== false ? '|' : '&';
		$modes = explode($type, $mode);
		$permission = false;

		if ((int)$user->data['user_type'] === USER_FOUNDER)
		{
			return true;
		}

		foreach($modes as $mode_val)
		{
			switch ($mode_val)
			{
				// Permanent Deletion
				case 'delete':
					$permission = $auth->acl_get('m_delete_forever', $forum_id) || $auth->acl_get('f_delete_forever', $forum_id);
				break;

				// View contents of the deleted topic/post
				case 'view':
					$permission = $auth->acl_get('a_', $forum_id);
					$permission = $permission || (($auth->acl_get('f_delete', $forum_id) && $auth->acl_get('m_delete', $forum_id))) ? true : false;
					$permission = $permission || $auth->acl_get('m_undelete', $forum_id);
					$permission = $permission || (SHOW_ALL_IN_TRASH_BIN && is_trash_forum($forum_id));
					//$permission = $permission || $auth->acl_get('f_read', $forum_id); // Allow any user to see the deleted posts so long as they can view the forum
				break;

				// Display placeholder for the deleted topic/post
				case 'list':
					// If the user can view the contents of the deleted post, then they need to be able to see the placeholder
					$permission = auth_fake_delete('view', $forum_id) || SHOW_PLACEHOLDER;
				break;

				// Restore the post/topic
				case 'undelete':
					$permission = $auth->acl_get('m_undelete', $forum_id) || $auth->acl_get('f_undelete', $forum_id);
				break;
			}
			$authorization = !isset($authorization) ? $permission : (($type == '|') ? ($authorization || $permission) : ($authorization && $permission));
		}
		return(isset($authorization) ? $authorization : false);
	}

	/**
	*/
	function stifle_topics_enabled()
	{
		global $config;
		return(empty($config['topic_delete_mode']) ? false : true);
	}

	/**
	*/
	function stifle_posts_enabled()
	{
		global $config;
		return(empty($config['topic_delete_mode']) ? false : true);
	}


	/**
	*/
	function trash_enabled()
	{
		global $config;
		return(empty($config['trash_forum']) ? false : true);
	}

	/**
	*/
	function get_trash_forum()
	{
		global $config;
		return(empty($config['trash_forum']) ? false : (integer)$config['trash_forum']);
	}

	/**
	*/
	function is_trash_forum($forum_id)
	{
		return(get_trash_forum() === (integer)$forum_id);
	}


	/**
	*/
	function trash_required()
	{
		global $config;
		return(!empty($config['topic_delete_mode']) && (integer)$config['topic_delete_mode'] >= PRIME_DELETE_MODE_TRASH && get_trash_forum());
	}

	/**
	*/
	function trash_shadow_required()
	{
		global $config;
		return(isset($config['topic_delete_mode']) && (integer)$config['topic_delete_mode'] == PRIME_DELETE_MODE_SHADOW);
	}


	/**
	*/
	function trash_shadow_disabled()
	{
		global $config;
		return(isset($config['topic_delete_mode']) && (integer)$config['topic_delete_mode'] == PRIME_DELETE_MODE_TRASH_ONLY);
	}


	/* Functions for topics
	------------------------------------------------------------------------ */

	/**
	*/
	function fake_delete_alter_quickmod($qm_options, $forum_id)
	{
		global $user;

		$user->add_lang('mods/prime_trash_bin_a');
		$qm_delete = '<option value="delete_topic">' . $user->lang['DELETE_TOPIC'] . '</option>';
		if (auth_fake_delete('undelete', $forum_id))
		{
			// Provide undelete as a quickmod option.
			$qm_undelete = '<option value="undelete_topic">' . $user->lang['PRIME_QM_TOPIC_UNDELETE'] . '</option>';
			$find = strpos($qm_options, $qm_delete) !== false ? $qm_delete : '</option>';
			$qm_options = str_replace($find, $find . $qm_undelete, $qm_options);
		}
		if (!auth_fake_delete('delete', $forum_id))
		{
			// Remove the delete quickmod option if user can't permanently delete the topic.
			$qm_options = str_replace($qm_delete, '', $qm_options);
		}
		else
		{
			// Replace the normal delete with a permanent delete.
			$qm_options = str_replace($user->lang['DELETE_TOPIC'], $user->lang['PRIME_QM_TOPIC_DELETE_FOREVER'], $qm_options);
		}
		return($qm_options);
	}


	/**
	*/
	function is_topic_stifled($topic_data)
	{
		$is_stifled = null;
		if (is_array($topic_data) && isset($topic_data['topic_deleted_time']))
		{
			$is_stifled = empty($topic_data['topic_deleted_time']) ? false : true;
		}
		else if (is_int($topic_data))
		{
			global $db;
			$sql = 'SELECT topic_id'
					. ' FROM ' . TOPICS_TABLE
					. ' WHERE topic_deleted_time < 1'
					. ' AND topic_id = ' . $topic_data;
			$result = $db->sql_query($sql);
			$is_stifled = $db->sql_affectedrows();
			$is_stifled = empty($is_stifled);
			$db->sql_freeresult($result);
		}
		return ($is_stifled);
	}


	/**
	*/
	function get_stifled_topic_title($title, $forum_id = 0, $censor =  true)
	{
		global $user;
		$user->add_lang('mods/prime_trash_bin_a');
		$title = (auth_fake_delete('view', $forum_id) || SHOW_STIFLED_TOPIC_TITLE) ? ($censor ? censor_text($title) : $title) : '';
		$title = $user->lang['PRIME_TOPIC_DELETED_TITLE'] . (empty($title) ? '' : $user->lang['PRIME_TOPIC_DELETED_TITLE_SEP']) . $title;
		return($title);
	}


	/**
	*/
	function get_stifled_topic_msg(&$topic_data)
	{
		global $auth, $user, $template, $db, $phpEx, $phpbb_root_path;

		if (is_int($topic_data))
		{
			$sql = 'SELECT f.forum_name, t.topic_id, t.forum_id, t.topic_deleted_time, t.topic_deleted_user, t.topic_deleted_from, t.topic_deleted_reason'
					. ' FROM ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . ' f'
					. ' WHERE t.topic_deleted_time < 1'
					. ' AND t.topic_id = ' . $topic_data
					. ' AND f.forum_id = t.forum_id';
			$result = $db->sql_query($sql);
			$topic_data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}
		if (!is_array($topic_data) || !isset($topic_data['topic_deleted_time']))
		{
			return('');
		}

		$user->add_lang('mods/prime_trash_bin_a');

		// Get information about the user who deleted the topic and the forum from which it was deleted.
		$sql = 'SELECT DISTINCT u.username, u.user_colour, f.forum_name'
				. ' FROM ' . USERS_TABLE . ' u, ' . FORUMS_TABLE . ' f'
				. ' WHERE user_id = ' . (int)$topic_data['topic_deleted_user']
				. (empty($topic_data['topic_deleted_from']) ? '' : (' AND forum_id=' . $topic_data['topic_deleted_from']))
		;
		$result = $db->sql_query($sql);
		$del_info = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		// Start building the message.
		$msg['from']    = (empty($topic_data['topic_deleted_from']) || $topic_data['topic_deleted_from'] == $topic_data['forum_id'] || !$auth->acl_get('f_list', $topic_data['topic_deleted_from'])) ? '' : '<a href="' . append_sid("{$phpbb_root_path}viewforum.$phpEx", "f={$topic_data['topic_deleted_from']}", true) . '">' . $del_info['forum_name'] . '</a>';
		$msg['by']      = empty($topic_data['topic_deleted_user']) ? '' : get_username_string('full', $topic_data['topic_deleted_user'], $del_info['username'], $del_info['user_colour']);
		$msg['on']      = empty($topic_data['topic_deleted_time']) ? '' : $user->format_date($topic_data['topic_deleted_time']);
		$msg['deleted'] = $user->lang['PRIME_TOPIC_DELETED_MSG'];

		$can_undo_delete = auth_fake_delete('undelete', $topic_data['forum_id']); // && $auth->acl_get('m_move', $topic_data['forum_id']) && $topic_data['topic_status'] != ITEM_MOVED ? true : false;
		$can_perm_delete = auth_fake_delete('delete', $topic_data['forum_id']);
		$can_view_delete = auth_fake_delete('view', $topic_data['forum_id']);
		$deleted_post_id = isset($topic_data['post_id']) ? ('deleted_p' . $topic_data['post_id']) : '';

		$redirect = request_var('redirect', build_url(array('_f_', 'action', 'quickmod')));
		$del_args = '&amp;quickmod=1&amp;t=' . $topic_data['topic_id'];
		if ($user->page['page_name'] == "mcp.$phpEx")
		{
			global $id, $mode;
			$del_args = "&amp;i={$id}&amp;mode={$mode}&amp;topic_id_list[]={$topic_data['topic_id']}";
		}
		$del_args .= '&amp;redirect=' . urlencode(str_replace('&amp;', '&', $redirect));

		// Almost done, just assign the rest of our template variables.
		$template_vars = array(
			'IS_TOPIC'	=> true,
			'IS_ROW'	=> ($user->page['page_name'] == "viewforum.$phpEx" || ($user->page['page_name'] == "search.$phpEx" && request_var('sr', '') == 'topic') || ($user->page['page_name'] == "mcp.$phpEx" && strpos($user->page['query_string'], 'mode=forum_view') !== false)),
			'MESSAGE'	=> $msg['deleted'],
			'FROM'		=> $msg['from'],
			'BY' 		=> $msg['by'],
			'ON'		=> $msg['on'],
			'REASON'	=> censor_text($topic_data['topic_deleted_reason']),
			'UNDO_LINK'	=> $can_undo_delete ? ' <a href="' . append_sid("{$phpbb_root_path}mcp.$phpEx", "f={$topic_data['forum_id']}&amp;action=undelete_topic$del_args") . '" class="topic_undelete">' . $user->lang['PRIME_TOPIC_UNDELETE'] . '</a>' : '',
			'PERM_LINK'	=> $can_perm_delete ? ' <a href="' . append_sid("{$phpbb_root_path}mcp.$phpEx", "f={$topic_data['forum_id']}&amp;action=delete_topic$del_args") . '" class="topic_delete_forever">' . $user->lang['PRIME_TOPIC_DELETE_FOREVER'] . '</a>' : '',
			'MINI_POST_IMG'	=> $user->img('icon_post_target', $user->lang['POST']),
			'U_MINI_POST'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $topic_data['forum_id'] . '&amp;t=' . $topic_data['topic_id']),
		);
		if($can_view_delete && !empty($topic_data['post_text']))
		{
			$template_vars = array_merge($template_vars, array(
				'ID'		=> $deleted_post_id,
				'TEXT'		=> $topic_data['post_text'],
				'TITLE'		=> isset($topic_data['post_subject']) ? $topic_data['post_subject'] : '',
				'POSTER'	=>  get_username_string('full', $topic_data['poster_id'], $topic_data['username'], $topic_data['user_colour'], $topic_data['post_username']),
				'VIEW_LINK'	=> '<a href="#view_post" class="view_deleted_post" onclick="return(show_post(this,\'' . $deleted_post_id . '\'));">' . $user->lang['PRIME_VIEW_DELETED_POST'] . '</a>',
			));
		}
		$template->set_filenames(array('delete_msg'	=> 'prime_deleted_msg.html'));
		$template->assign_block_vars('deleted', $template_vars);
		$deleted_msg = $template->assign_display('delete_msg');
		$template->destroy_block_vars('deleted');
		return($deleted_msg);
	}



	/* Functions for posts
	------------------------------------------------------------------------ */

	/**
	* Determines if a post has already been marked as deleted.
	*/
	function can_stifle_post($post_data)
	{
		$can_stifle = false;
		if (is_array($post_data))
		{
			$can_stifle = (empty($post_data['post_deleted_time']) && !empty($post_data['post_approved']));
		}
		if (is_int($post_data))
		{
			$sql = 'SELECT post_id'
					. ' FROM ' . POSTS_TABLE
					. ' WHERE (post_deleted_time = 0 AND post_approved > 0)'
					. ' AND post_id=' . $post_data;
			$result = $db->sql_query($sql);
			$can_stifle = $db->sql_affectedrows() ? true : false;
			$db->sql_freeresult($result);
		}
		return($can_stifle);
	}


	/**
	*/
	function get_stifled_post_title($title, $forum_id = 0, $censor =  true)
	{
		global $user;
		$user->add_lang('mods/prime_trash_bin_a');
		$title = !auth_fake_delete('view', $forum_id) ? '' : ($censor ? censor_text($title) : $title);
		$title = $user->lang['PRIME_POST_DELETED_TITLE'] . (empty($title) ? '' : $user->lang['PRIME_POST_DELETED_TITLE_SEP']) . $title;
		return($title);
	}


	/**
	* Creates the message to display for a deleted post in place of the post's original content.
	*/
	function get_stifled_post_msg(&$post_data, $message = '', $title = '')
	{
		if (!is_array($post_data) || empty($post_data['post_deleted_time']))
		{
			return('');
		}

		global $user, $template, $db, $phpEx, $phpbb_root_path, $user_cache;

		$user->add_lang('mods/prime_trash_bin_a');

		$poster_id = isset($post_data['user_id']) ? $post_data['user_id'] : (isset($post_data['poster_id']) ? $post_data['poster_id'] : '');
		$poster_name = isset($post_data['username']) ? $post_data['username'] : (isset($post_data['post_username']) ? $post_data['post_username'] : '');
		$poster_color = isset($post_data['user_colour']) ? $post_data['user_colour'] :'';

		// Get the information about the user who deleted the post.
		if ($post_data['post_deleted_user'] == $poster_id)
		{
			$del_info['username']    = $poster_name;
			$del_info['user_colour'] = $poster_color;
		}
		else if ($post_data['post_deleted_user'] == $user->data['user_id'])
		{
			$del_info['username']    = $user->data['username'];
			$del_info['user_colour'] = $user->data['user_colour'];
		}
		else if (!empty($user_cache['user_id']) && $post_data['post_deleted_user'] == $user_cache['user_id'])
		{
			$del_info['username']    = $user_cache['username'];
			$del_info['user_colour'] = $user_cache['user_colour'];
		}
		// Get the title of the topic that the post was deleted from.
		if (!empty($post_data['post_deleted_from']) && !empty($post_data['topic_title']) && $post_data['post_deleted_from'] == $post_data['topic_id'])
		{
			$del_info['topic_title'] = $post_data['topic_title'];
		}
		if (!isset($del_info['username']) || !isset($del_info['topic_title']))
		{
			$sql = 'SELECT DISTINCT u.username, u.user_colour, t.topic_title'
					. ' FROM ' . USERS_TABLE . ' u,  ' . TOPICS_TABLE . ' t'
					. ' WHERE u.user_id = ' . (int)$post_data['post_deleted_user']
					. (empty($post_data['post_deleted_from']) ? '' : ' AND topic_id = ' . (int)$post_data['post_deleted_from'])
			;
			$result = $db->sql_query($sql);
			$del_info = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}

		// Start building the message.
		$msg['from'] = (empty($post_data['post_deleted_from']) || $post_data['post_deleted_from'] == $post_data['topic_id'] || !$auth->acl_get('f_list', $post_data['post_deleted_from'])) ? '' : '<a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", "t={$post_data['post_deleted_from']}", true) . '">' . $del_info['topic_title'] . '</a>';
		$msg['by']   = empty($post_data['post_deleted_user']) ? '' : get_username_string('full', $post_data['post_deleted_user'], $del_info['username'], $del_info['user_colour']);
		$msg['on']   = empty($post_data['post_deleted_time']) ? '' : $user->format_date($post_data['post_deleted_time']);
		$msg['deleted'] = $user->lang['PRIME_POST_DELETED_MSG'];

		$redirect = request_var('redirect', build_url(array('_f_', 'action', 'quickmod')));
		$redirect = '&amp;redirect=' . urlencode(str_replace('&amp;', '&', $redirect));
		$forum_id = !empty($post_data['forum_id']) ? $post_data['forum_id'] : request_var('f', 0); // post_data['forum_id'] could be 0 for a global topic, but a valid forum_id is required to get past the NO_FORUM error in posting.php

		$can_undo_delete  = auth_fake_delete('undelete', $post_data['forum_id']) && $user->page['page_name'] != "posting.$phpEx";
		$can_perm_delete  = auth_fake_delete('delete', $post_data['forum_id']) && $user->page['page_name'] != "posting.$phpEx";
		//$can_view_delete  = auth_fake_delete('delete|undelete', $post_data['forum_id']);
		$can_view_delete  = auth_fake_delete('view', $post_data['forum_id']);
		$deleted_post_id  = 'deleted_p' . $post_data['post_id'];
		$perm_delete_page = "posting.$phpEx";
		$perm_delete_args = "mode=delete&amp;f=$forum_id&amp;t={$post_data['topic_id']}&amp;p={$post_data['post_id']}";
		$undo_delete_page = "mcp.$phpEx";
		$undo_delete_args = "quickmod=1&amp;action=undelete_post&amp;f={$post_data['forum_id']}&amp;t={$post_data['topic_id']}&amp;p={$post_data['post_id']}";

		if ($user->page['page_name'] == "mcp.$phpEx")
		{
			global $id, $mode;
			$id = isset($id) ? $id : 'main';
			$mode = isset($mode) ? $mode : 'topic_view';
			$perm_delete_page = "mcp.$phpEx";
			$perm_delete_args = "i={$id}&amp;mode={$mode}&amp;action=delete_post&amp;f=$forum_id&amp;t={$post_data['topic_id']}&amp;post_id_list[]={$post_data['post_id']}";
			$undo_delete_args = str_replace('delete', 'undelete', $perm_delete_args);
		}

		// Almost done, just assign the rest of our template variables.
		$template_vars = array(
			'IS_POST'		=> true,
			'IS_ROW'		=> ($user->page['page_name'] == "search.$phpEx" && request_var('sr', '') == 'topic'),
			'MESSAGE'		=> $msg['deleted'],
			'FROM'			=> $msg['from'],
			'BY' 			=> $msg['by'],
			'ON'			=> $msg['on'],
			'REASON'		=> censor_text($post_data['post_deleted_reason']),
			'UNDO_LINK'		=> $can_undo_delete ? '<a href="' . append_sid("{$phpbb_root_path}$undo_delete_page", $undo_delete_args . $redirect) . '" class="post_undelete">' . $user->lang['PRIME_POST_UNDELETE'] . '</a>' : '',
			'PERM_LINK'		=> $can_perm_delete ? '<a href="' . append_sid("{$phpbb_root_path}$perm_delete_page", $perm_delete_args . $redirect) . '" class="post_delete_forever">' . $user->lang['PRIME_POST_DELETE_FOREVER'] . '</a>' : '',
			'MINI_POST_IMG'	=> $user->img('icon_post_target', $user->lang['POST']),
			'U_MINI_POST'	=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $post_data['forum_id'] . '&amp;t=' . $post_data['topic_id'] . '&amp;p=' . $post_data['post_id']) . '#p' . $post_data['post_id'],
		);
		if($can_view_delete && !empty($message))
		{
			$template_vars = array_merge($template_vars, array(
				'ID'		=> $deleted_post_id,
				'TEXT'		=> $message,
				'TITLE'		=> $title,
				'POSTER'	=>  get_username_string('full', $poster_id, $poster_name, $poster_color),
				'VIEW_LINK'	=> '<a href="#view_post" class="view_deleted_post" onclick="return(show_post(this,\'' . $deleted_post_id . '\'));">' . $user->lang['PRIME_VIEW_DELETED_POST'] . '</a>',
			));
		}
		$template->set_filenames(array('delete_msg'	=> 'prime_deleted_msg.html'));
		$template->assign_block_vars('deleted', $template_vars);
		$deleted_msg = $template->assign_display('delete_msg');
		$template->destroy_block_vars('deleted');

		return($deleted_msg);
	}


	/* Functions for topics & posts
	------------------------------------------------------------------------ */

	/**
	*/
	function set_stifled_template_vars(&$template_vars, &$block)
	{
		global $template;

		if (is_string($block) && strlen($block) > 0)
		{
			$template->alter_block_array($block, $template_vars, true, 'change');
		}
		else if (is_array($block))
		{
			$block = array_merge($block, $template_vars);
		}
		else if ($block)
		{
			$template->assign_vars($template_vars);
		}
	}


	/**
	*/
	function set_stifled_topic_template_vars(&$data, $title, &$block)
	{
		global $template, $user, $phpEx;

		if (!stifle_topics_enabled())
		{
			return(array());
		}
		$user->add_lang('mods/prime_trash_bin_a');
		$template->assign_var('S_TOPIC_DELETED', true);

		$page_name     = str_replace(".$phpEx", '', $user->page['page_name']);
		$page_mode     = request_var('mode', '');
		$deleted_msg   = get_stifled_topic_msg($data);
		$deleted_title = get_stifled_topic_title($title, $data['forum_id']);
		$template_vars = array();
		switch ($page_name)
		{
			case 'mcp':
				$user->add_lang('mods/prime_trash_bin_b');
				$template->assign_var('S_CAN_UNDELETE', auth_fake_delete('undelete', isset($data['forum_id']) ? $data['forum_id'] : 0));
				switch ($page_mode)
				{
					case 'forum_view':
						$template_vars = array(
							'S_DELETED'			=> true,
							'TOPIC_TITLE'		=> $deleted_title,
						);
					break;

					case 'topic_view':
						$template_vars = array(
							'S_DELETED'			=> true,
							'TOPIC_TITLE'		=> $deleted_title,
							'DELETED_MSG'		=> $deleted_msg,
						);
					break;
				}
			break;

			case 'search':
				$post_mode = request_var('sr', false) == 'posts' ? true : false;
				$template_vars = array(
					'S_DELETED'		=> true,
					'TOPIC_TITLE'	=> $deleted_title,
					'DELETED_MSG'	=> $deleted_msg,
					'POST_SUBJECT'	=> $deleted_title,
					'MESSAGE'		=> $deleted_msg,

					'S_IGNORE_POST' => MINIMIZE_DELETED_POST,
					'L_IGNORE_POST' => $deleted_msg,
				);
				if ($post_mode)
				{
					// So the prime_show_deleted_post.html will get included
					$template->assign_var('S_POST_DELETED', true);
				}
			break;

			case 'viewforum':
				$template_vars = array(
					'S_DELETED' 	=> true,
					'TOPIC_TITLE' 	=> $deleted_title,
					'DELETED_MSG'	=> $deleted_msg,
				);
			break;

			case 'viewtopic':
				$template_vars = array(
					// New
					'DELETED_MSG'	=> $deleted_msg ,

					// Overwrite
					'TOPIC_TITLE' 			=> $deleted_title,
				//	'TOPIC_POSTER'			=> '',
				//	'TOPIC_AUTHOR_FULL'		=> '',
				//	'TOPIC_AUTHOR_COLOUR'	=> '',
				//	'TOPIC_AUTHOR'			=> '',
				//	'PAGE_NUMBER' 			=> 1,
					'POST_IMG' 				=> '',
					'QUOTE_IMG' 			=> '',
					'REPLY_IMG'				=> '',
					'EDIT_IMG' 				=> '',
					'DELETE_IMG' 			=> '',
					'INFO_IMG' 				=> '',
					'PROFILE_IMG'			=> '',
					'SEARCH_IMG' 			=> '',
					'PM_IMG' 				=> '',
					'EMAIL_IMG' 			=> '',
					'WWW_IMG' 				=> '',
					'ICQ_IMG' 				=> '',
					'AIM_IMG' 				=> '',
					'MSN_IMG' 				=> '',
					'YIM_IMG' 				=> '',
					'JABBER_IMG'			=> '',
					'REPORT_IMG'			=> '',
					'REPORTED_IMG'			=> '',
					'UNAPPROVED_IMG'		=> '',
					'WARN_IMG'				=> '',
					'S_IS_LOCKED'			=> true,
				//	'S_TOPIC_MOD' 			=> '',
				//	'S_MOD_ACTION' 			=> '',
				//	'S_DISPLAY_SEARCHBOX'	=> false,
				//	'S_SEARCHBOX_ACTION'	=> '',
				//	'S_DISPLAY_POST_INFO'	=> false,
					'S_DISPLAY_REPLY_INFO'	=> false,
				//	'U_TOPIC'				=> '',
				//	'U_VIEW_TOPIC' 			=> '',
				//	'U_VIEW_FORUM' 			=> '',
				//	'U_VIEW_OLDER_TOPIC'	=> '',
				//	'U_VIEW_NEWER_TOPIC'	=> '',
					'U_PRINT_TOPIC'			=> '',
				//	'U_EMAIL_TOPIC'			=> '',
				//	'U_WATCH_TOPIC' 		=> '',
				//	'L_WATCH_TOPIC' 		=> '',
				//	'S_WATCHING_TOPIC'		=> '',
				//	'U_BOOKMARK_TOPIC'		=> '',
				//	'L_BOOKMARK_TOPIC'		=> '',
				//	'U_POST_NEW_TOPIC' 		=> '',
					'U_POST_REPLY_TOPIC' 	=> '',
					'U_BUMP_TOPIC'			=> '',
					'S_QUICK_REPLY'			=> false,
				);
				// If not allowed to view the deleted topic, don't display any poll
				$forum_id = isset($topic_data['forum_id']) ? $topic_data['forum_id'] : 0;
				if (!auth_fake_delete('view', $forum_id))
				{
					$template_vars['S_HAS_POLL'] = false;
				}
			break;
		}
		set_stifled_template_vars($template_vars, $block);
		return($template_vars);
	}

	function set_stifled_post_template_vars(&$data, $message, $title, &$block)
	{
		global $template, $user, $phpEx;

		if (!stifle_posts_enabled())
		{
			return(array());
		}
		$user->add_lang('mods/prime_trash_bin_a');
		$template->assign_var('S_POST_DELETED', true);

		$page_name     = str_replace(".$phpEx", '', $user->page['page_name']);
		$page_mode     = request_var('mode', '');
		$deleted_msg   = get_stifled_post_msg($data, $message, $title);
		$deleted_title = get_stifled_post_title($title, $data['forum_id']);
		$template_vars = array();
		switch ($page_name)
		{
			case 'mcp':
				$user->add_lang('mods/prime_trash_bin_b');
				$template->assign_var('S_CAN_UNDELETE', auth_fake_delete('undelete', isset($data['forum_id']) ? $data['forum_id'] : 0));
				switch ($page_mode)
				{
					case 'post_details':
						$template_vars = array(
							'S_DELETED'				=> true,
							'POST_SUBJECT'			=> $deleted_title,
							'DELETED_REASON'		=> (!empty($data['post_deleted_time']) && !empty($data['post_deleted_reason'])) ? $data['post_deleted_reason'] : false,
							'S_CAN_UNDELETE_POST'	=> (!empty($data['post_deleted_time']) && auth_fake_delete('undelete', isset($data['forum_id']))) ? $data['forum_id'] : 0,
						);
						if (!empty($data['post_deleted_time']))
						{
							$template_vars['S_CAN_DELETE_POST'] = auth_fake_delete('delete', isset($data['forum_id']) ? $data['forum_id'] : 0);
						}
					break;

					case 'topic_view':
						$template_vars = array(
							'S_DELETED'			=> true,
							'POST_SUBJECT'		=> $deleted_title,
							'MESSAGE'			=> $deleted_msg,
						);
					break;
				}
			break;

			case 'search':
				$template_vars = array(
					'S_DELETED'		=> true,
					'S_IGNORE_POST'	=> MINIMIZE_DELETED_POST,
					'L_IGNORE_POST'	=> $deleted_msg,
					'POST_SUBJECT'	=> $deleted_title,
					'MESSAGE'		=> $deleted_msg,
				);
			break;

			case 'viewtopic':
				$template_vars = array(
					'S_DELETED'     => true,
					'S_IGNORE_POST' => MINIMIZE_DELETED_POST,
					'L_IGNORE_POST' => $deleted_msg,
					'POST_SUBJECT'  => $deleted_title,
					'MESSAGE'       => $deleted_msg,
					'ONLINE_IMG'    => '',
					'S_ONLINE'      => false,
					'U_MINI_POST'   => '',
					'U_QUOTE'       => '',
					'U_EDIT'        => '',
					'U_REPORT'      => '',
					'U_WARN'        => '',
				);
			break;

			case 'posting':
				$template_vars = array(
					'S_DELETED'     => true,
					'S_IGNORE_POST' => MINIMIZE_DELETED_POST,
					'L_IGNORE_POST' => $deleted_msg,
					'POST_SUBJECT'  => $deleted_title,
					'MESSAGE'       => $deleted_msg,
					'ONLINE_IMG'    => '',
					'S_ONLINE'      => false,
					'U_MINI_POST'   => '',
					'U_QUOTE'       => '',
					'U_EDIT'        => '',
					'U_REPORT'      => '',
					'U_WARN'        => '',
				);
			break;
		}
		set_stifled_template_vars($template_vars, $block);
		return($template_vars);
	}
}
?>