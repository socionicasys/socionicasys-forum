<?php
/**
*
* @package phpBB3
* @version $Id: prime_post_revisions.php,v 1.2.7 2011/03/01 14:17:00 primehalo Exp $
* @copyright (c) 2007-2011 Ken Innes IV
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
* Ensure that this file has not already been included.
*/
if (!class_exists('prime_post_revisions'))
{
	/**
	* Permissions
	*/
	define('APPROVE_VIEWING_POSTER', false);		// Can the post author view the revisions?
	define('APPROVE_REMOVAL_POSTER', false);		// Can the post author remove revisions?
	define('APPROVE_REMOVAL_EDITOR', false);		// Can the post editor remove their revision?
	define('APPROVE_REMOVAL_MODERATOR', true);		// Can moderators (with the ability to delete posts) remove revisions?
	
	/**
	* Options
	*/
	define('ENABLE_POST_REVISIONS', true);			// Enable this MOD?
	define('SUBJECT_CHANGE_REVISION', true);		// Store a revision when only the subject has been changed?
	define('REVISION_PAGE_HEADER', true);			// Display the explanation about the viewing revisions page?
	define('REVISION_POST_HEADERS', true);			// Display the revision number in the posts' subject area?
	define('REVISION_SIGNATURES', true);			// Display signatures while viewing revisions?
	
	/**
	* Magic Numbers
	*/
	define('PRIME_POST_REVISION_WIPE', -1);			// Delete all revisions
	define('PRIME_POST_REVISION_UNDO', -2);			// Undo last revision


	/**
	* Store revision information into the database
	*/
	function store_post_revision_info(&$data, &$post_data)
	{
		global $db, $user;

		if (!ENABLE_POST_REVISIONS)
		{
			return;
		}
		// Grab the info that we need to store for the revision.
		$sql = 'SELECT post_text, post_subject, bbcode_uid'
		     . ' FROM ' . POSTS_TABLE
		     . ' WHERE post_id=' . $data['post_id'];
		$original_post_data = $db->sql_fetchrow($result = $db->sql_query($sql));
		$db->sql_freeresult($result);

		$subject_change = (SUBJECT_CHANGE_REVISION && strcmp(trim($post_data['post_subject']), trim($original_post_data['post_subject'])) != 0);
		$message_change = strcmp(trim($data['message']), trim($original_post_data['post_text'])) != 0;

		// Check to see if the message or subject line has changed.
		if ($message_change || $subject_change)
		{
			// Place the revision info into the database.
			$sql_ary = array(
				'post_id'          => $data['post_id'],
				'post_subject'     => $original_post_data['post_subject'],
				'post_text'        => $original_post_data['post_text'],
				'bbcode_uid'       => $original_post_data['bbcode_uid'],
				'post_edit_time'   => time(),
				'post_edit_user'   => $user->data['user_id'],
				'post_edit_reason' => $data['post_edit_reason'],
			);
			$db->sql_query('INSERT INTO ' . POST_REVISIONS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
			$db->sql_freeresult($result);
		}
	}

	class prime_post_revisions
	{
		var $revision_info = array();			// Quick revision info for each post (total edits, last edit time & user)
		var $user_cache = array();				// Cache of some basic info about users who edited posts
		var $approve_viewing = false;			// Are we allowed to access post history?
		var $viewing_history = false;			// Are we displaying post history?

		// Used when viewing history or deleting
		var $approve_removal = false;			// Can we delete the revision?
		var $forum_id = 0;
		var $topic_id = 0;
		var $post_id = 0;
		var $current_post = null;				// Current post info, without any revision info
		var $result = null;						// SQL result

		/**
		* Constructor
		*/
		function prime_post_revisions(&$post_list, $forum_id = 0, $topic_id = 0, $post_id = 0)
		{
			global $db, $auth, $user, $phpbb_root_path, $phpEx;

			if (!ENABLE_POST_REVISIONS)
			{
				return;
			}

			$this->approve_removal = $auth->acl_get('a_');
			$this->forum_id	= (int)$forum_id;
			$this->topic_id	= (int)$topic_id;
			$this->post_id	= (int)$post_id;

		 	// Allow user to view the post history if they have permission to view post details. If not
			// and APPROVE_VIEWING_POSTER is TRUE, then we won't know until we have the poster_id.
			if (($this->approve_viewing = $auth->acl_get('m_info', $forum_id)) || APPROVE_VIEWING_POSTER)
			{
				// Get the total number of revisions and the latest revision info for this post, plus user info for anyone who edited it.
				$this->revision_info = array();
				$this->user_cache = array();
				$sql = $db->sql_build_query('SELECT', array(
					'SELECT'	=> 'r.post_id, r.post_edit_time, r.post_edit_user, u.*',
					'FROM'		=> array(POST_REVISIONS_TABLE => 'r', USERS_TABLE => 'u'),
					'WHERE'		=> $db->sql_in_set('r.post_id', $post_list) . ' AND r.post_edit_user = u.user_id'
				));
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$id = $row['post_id'];
					$this->revision_info[$id]['count'] = empty($this->revision_info[$id]) ? 1 : $this->revision_info[$id]['count'] + 1;
					if (empty($this->revision_info[$id]['time']) || $this->revision_info[$id]['time'] < $row['post_edit_time'])
					{
						$this->revision_info[$id]['time'] = $row['post_edit_time'];
						$this->revision_info[$id]['user'] = $row['post_edit_user'];
					}
					unset($row['post_id'], $row['post_edit_time'], $row['post_edit_user']);
					$this->user_cache[$row['user_id']] = $row;
				}
				$db->sql_freeresult($result);
			}

			// Are we on the post revisions page?
			if (!empty($this->revision_info[$post_id]) && ($this->viewing_history = request_var('display_history', false)))
			{
				$this->viewing_history = $this->approve_viewing;

				// If user isn't allowed to view history, but APPROVE_VIEWING_POSTER is TRUE,
				// then we won't actually know if user is allowed until we have the poster_id
				if (!$this->approve_viewing && APPROVE_VIEWING_POSTER && $user->data['is_registered'])
				{
					$sql = 'SELECT poster_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . $post_id;
					$result = $db->sql_query($sql);
					if ($row = $db->sql_fetchrow($result))
					{
						$this->viewing_history = ($row['poster_id'] == $user->data['user_id']);
					}
				}
				if ($this->viewing_history)
				{
					$post_list = array($this->post_id);	// We're only interested in the one post for which we're viewing the history.
				}
			}

			// Are we deleting a revision?
			if ($remove_history = request_var('remove_history', 0))
			{
				$user->add_lang('mods/prime_post_revisions');
				$s_hidden_fields = build_hidden_fields(array(
					'p' => $post_id,
					'f' => $forum_id
				));
				if (confirm_box(true))
				{
					$this->remove_history($post_id, $remove_history);
				}
				else
				{
					confirm_box(false, 'PRIME_POST_REVISIONS_DELETE' . ($remove_history == PRIME_POST_REVISION_WIPE ? 'S' : ''), $s_hidden_fields);
				}
				redirect(append_sid("{$phpbb_root_path}viewtopic.$phpEx", "p=$post_id&f=$forum_id&display_history=true", false));
			}
		}


		/**
		* Does user have permission to view the post history?
		*/
		function can_view_history($user_id)
		{
			global $user;

			$approve_viewing = $this->approve_viewing || (APPROVE_VIEWING_POSTER && $user->data['user_id'] == $user_id);
			return $approve_viewing;
		}

		/**
		* Delete a post revision from the database.
		*/
		function remove_history($post_id, $post_edit_time)
		{
			global $db, $auth, $user, $phpbb_root_path, $phpEx;

			$string_prefix = ($post_edit_time == PRIME_POST_REVISION_WIPE ? 'PRIME_POST_REVISIONS_DELETES_' : 'PRIME_POST_REVISIONS_DELETE_');
			if (empty($post_id) || empty($post_edit_time))
			{
				trigger_error($user->lang[$string_prefix . 'INVALID']);
			}

			// Check for authorization
			$approve = $this->approve_removal;
			if (!$approve && (APPROVE_REMOVAL_POSTER || APPROVE_REMOVAL_MODERATOR))
			{
				$sql = 'SELECT poster_id, forum_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . $post_id;
				$result = $db->sql_query($sql);
				if ($row = $db->sql_fetchrow($result))
				{
					$approve = (APPROVE_REMOVAL_POSTER && $user->data['user_id'] == $row['poster_id']) || (APPROVE_REMOVAL_MODERATOR && $auth->acl_get('f_delete', $row['forum_id']) && $auth->acl_get('m_delete', $row['forum_id']));
				}
				$db->sql_freeresult($result);
			}
			if (!$approve && APPROVE_REMOVAL_EDITOR && $post_edit_time > 0)
			{
				$sql = ' SELECT post_id, post_edit_time, post_edit_user'
						. ' FROM ' . POST_REVISIONS_TABLE
						. ' WHERE post_id=' . $post_id . ' AND post_edit_time <= ' . $post_edit_time
						. ' ORDER BY post_edit_time DESC';
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					if ($row['post_edit_time'] == $post_edit_time)
					{
						if ($next_row = $db->sql_fetchrow($result))
						{
							$approve = ($user->data['user_id'] == $next_row['post_edit_user']);
						}
						break;
					}
				}
				$db->sql_freeresult($result);
			}

			// Proceed with removal, if allowed.
			$display_history = '&amp;display_history=true';
			if (!$approve)
			{
				$message = $user->lang[$string_prefix . 'DENIED'];
			}
			else
			{
				$revisions = array();
				$sql = 'SELECT * FROM ' . POST_REVISIONS_TABLE . ' WHERE post_id=' . $post_id . ' ORDER BY post_edit_time DESC';
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$revisions[] = $row;
				}
			}
			if (!empty($revisions))
			{
				// Replace the contents of the current post with the previous revision
				if ($post_edit_time == PRIME_POST_REVISION_UNDO)
				{
					// Grab the most recent edit history from the revision table so we can put it back into the post.
					$update = array();
					$sql = 'SELECT p.topic_id, p.post_edit_count, p.post_edit_time AS edit_time, t.topic_title, t.topic_replies_real, t.topic_first_post_id, t.topic_last_post_id'
					     . '	FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t '
					     . '	WHERE p.post_id=' . $post_id
					     . '		AND t.topic_id = p.topic_id';
					$result = $db->sql_query($sql);
					$post = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
					$post_edit_time 			= $revisions[0]['post_edit_time'];
					$update['post_subject']		= $revisions[0]['post_subject'];
					$update['post_text']		= $revisions[0]['post_text'];
					$update['post_edit_count']	= 0;
					$update['post_edit_time']	= 0;
					$update['post_edit_user']	= 0;
					$update['post_edit_reason']	= '';
					if (!empty($revisions[1]))
					{
						$update['post_edit_count']	= $post['post_edit_count'] > 0 ? $post['post_edit_count'] - 1 : 0;
						$update['post_edit_time']	= $revisions[1]['post_edit_time'];
						$update['post_edit_user']	= $revisions[1]['post_edit_user'];
						$update['post_edit_reason']	= $revisions[1]['post_edit_reason'];
					}
					$sql = 'UPDATE ' . POSTS_TABLE
							. '	SET ' . $db->sql_build_array('UPDATE', $update)
							. '	WHERE post_id = ' . $post_id;
					$db->sql_query($sql);
					$subject = $update['post_subject'];
					if (!empty($subject) && $subject != $post['topic_title'])
					{
						$set = (($post['topic_replies_real'] == 0) || ($post['topic_first_post_id'] == $post_id)) ? ('topic_title = \'' . $db->sql_escape($subject) . '\'') : (($post['topic_last_post_id'] == $post_id) ? 'topic_last_post_subject = \'' . $db->sql_escape($subject) . '\'' : '');
						if (!empty($set))
						{
							$sql = 'UPDATE ' . TOPICS_TABLE
									. '	SET ' . $set
									. '	WHERE topic_id = ' . $post['topic_id'];
							$db->sql_query($sql);
						}
					}
					// Done updating the current post with the latest revision, now continue with removing the revision.
				}

				// A revision is being deleted, thus need to update the previous revision's info
				if ($post_edit_time > 0)
				{
					foreach($revisions as $key => $val)
					{
						if ($val['post_edit_time'] == $post_edit_time)
						{
							$delete_key = $key;
						}
						if ($val['post_edit_time'] < $post_edit_time)
						{
							$update_key = $key;
							break;
						}
					}
				}
				$sql_update = '';
				if (isset($delete_key))
				{
					if (isset($update_key) && empty($update))
					{
						$update['post_edit_time']	= $revisions[$delete_key]['post_edit_time'];
						$update['post_edit_user']	= $revisions[$delete_key]['post_edit_user'];
						$update['post_edit_reason'] = $revisions[$delete_key]['post_edit_reason'];
						$sql_update = 'UPDATE ' . POST_REVISIONS_TABLE
									. '	SET ' . $db->sql_build_array('UPDATE', $update)
									. '	WHERE post_id = ' . $post_id
									. '		AND post_edit_time = ' . $revisions[$update_key]['post_edit_time'];
					}
					unset($revisions[$delete_key]);
				}

				// Delete the revision
				$sql = 'DELETE FROM ' . POST_REVISIONS_TABLE . ' WHERE post_id = ' . $post_id . ($post_edit_time > 0 ? " AND post_edit_time = $post_edit_time" : '');
				if ($db->sql_query($sql))
				{
					if ($sql_update)
					{
						$db->sql_query($sql_update);
					}
					$message = $user->lang[$string_prefix . 'SUCCESS'];

					// Check to see if there is are any more revisions to display.
					if (empty($revisions) || $post_edit_time == PRIME_POST_REVISION_WIPE)
					{
						$display_history = '';
					}
				}
				else
				{
					$message = $user->lang[$string_prefix . 'FAILED'];
				}
			}
			$prev_page = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f={$this->forum_id}&amp;t={$this->topic_id}&amp;p={$post_id}&amp;$display_history");
			$message .= '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $prev_page . '">', '</a>');
			trigger_error($message);
		}


		/**
		* Get the history information (if we're displaying the history).
		*/
		function get_revision_info(&$post_list, $result, &$viewtopic_url, &$viewtopic_title)
		{
			global $db, $auth, $user, $phpbb_root_path, $phpEx;

			if (!ENABLE_POST_REVISIONS)
			{
				return;
			}
			if ($this->viewing_history)
			{
				$this->viewing_history = false;		// We'll turn it back on if we grab the info we need

				// We successfully grabbed the most recent post (info not stored in the revision table)
				if ($this->current_post = $db->sql_fetchrow($result))
				{
					$db->sql_rowseek(0, $result);

					// We shouldn't need this check, but better to be safe
					if (!$this->can_view_history($this->current_post['poster_id']))
					{
						return;
					}
					$this->approve_removal = ($this->approve_removal || (APPROVE_REMOVAL_POSTER && $user->data['user_id'] == $this->current_post['poster_id']) || (APPROVE_REMOVAL_MODERATOR && $auth->acl_get('f_delete', $this->forum_id) && $auth->acl_get('m_delete', $this->forum_id)));

					// Let's grab the edit history of the post.
					$sql = ' SELECT post_id, post_subject, post_text, bbcode_uid, post_edit_time, post_edit_user, post_edit_reason'
					     . ' FROM ' . POST_REVISIONS_TABLE
					     . ' WHERE post_id=' . $this->post_id
					     . ' ORDER BY post_edit_time DESC';
					if (($result2 = $db->sql_query($sql)) && ($row = $db->sql_fetchrow($result2)))
					{
						$db->sql_rowseek(0, $result2);
						$user->add_lang('mods/prime_post_revisions');
						$this->viewing_history = true;
						$post_list = array(); // reset, as it will now be filled with revisions instead of posts
						$viewtopic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f={$this->forum_id}&amp;t={$this->topic_id}&amp;p={$this->post_id}") . '#p' . $this->post_id;
						$viewtopic_title = $this->current_post['post_subject'];
					}
					$this->result = $result2;
				}
			}
		}


		/**
		* Merge revision specific info with the general post info
		*/
		function merge_revision_info(&$post_list, &$result, &$row)
		{
			global $db;
			static $offset_id = 0;
			
			if (!ENABLE_POST_REVISIONS)
			{
				return;
			}
			if ($this->viewing_history)
			{
				if ($offset_id === 0 && $this->result)
				{
					$db->sql_freeresult($result);
					$result = $this->result;
					$this->result = null;
				}
				$row = array_merge($this->current_post, $row);
				$post_list[] = $row['post_id'] = $row['post_id'] + $offset_id;
				$offset_id += 1;
			}
		}

		/**
		* The board does not keep count of every edit, but since this MOD does lets
		* display the proper number of edits.
		*/
		function set_edit_count(&$row)
		{
			global $user, $user_cache;

			if (!ENABLE_POST_REVISIONS)
			{
				return;
			}

			if ($this->can_view_history($row['user_id']) && !$this->viewing_history && !empty($this->revision_info[$row['post_id']]))
			{
				$row['post_edit_count'] = max($row['post_edit_count'], $this->revision_info[$row['post_id']]['count']);
				$row['post_edit_user'] = $this->revision_info[$row['post_id']]['user'];
				$row['post_edit_time'] = $this->revision_info[$row['post_id']]['time'];

				$user_id = $row['post_edit_user'];
				if (isset($this->user_cache[$user_id]) && !isset($user_cache[$user_id]))
				{
					$user_cache[$user_id] = $this->user_cache[$user_id];
				}
			}
			else if ($this->viewing_history)
			{
				$row['post_edit_count'] = 0;
				$row['post_edit_reason'] = '';
			}
      
		}

		/**
		* Inject user data into the $post_edit_list and $user_cache tables if it does not already exist.
		*/
		function inject_user_data(&$row)
		{
			global $user_cache, $post_edit_list;
			$user_id = $row['post_edit_user'];

			if ($this->can_view_history($row['user_id']) && isset($this->user_cache[$user_id]))
			{
				if (!isset($post_edit_list[$user_id]))
				{
					$cache = &$this->user_cache[$user_id];
					$post_edit_list[$user_id] = array('user_id' => $user_id, 'username' => $cache['username'], 'user_colour' => $cache['user_colour']);
				}
				if (!isset($user_cache[$user_id]))
				{
					$user_cache[$user_id] = $this->user_cache[$user_id];
				}
			}
		}


		/**
		* Update $postrow data.
		*/
		function update_postrow(&$post_list, $index, &$rowset, &$postrow)
		{
			global $user, $phpbb_root_path, $phpEx;

			if (!ENABLE_POST_REVISIONS)
			{
				return;
			}
			//trigger_error(str_replace("\n", '<br />', print_r($rowset, true))); // for debugging
			$row = &$rowset[$post_list[$index]];
			if ($this->viewing_history)
			{
				$postrow['U_EDIT']      	= '';
				$postrow['U_QUOTE']     	= '';
				$postrow['U_INFO']      	= '';
				$postrow['EDIT_REASON']		= '';
				$postrow['EDITED_MESSAGE']	= '';
				$postrow['U_DELETE']		= '';
				$postrow['U_WARN']			= '';
				$postrow['U_REPORT']		= '';
				$postrow['U_MINI_POST']		= append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $this->current_post['post_id']) . '&amp;f=' . $this->forum_id . '#p' . $this->current_post['post_id'];
				if (!REVISION_SIGNATURES)
				{
					$postrow['SIGNATURE']	= '';
				}
				if (REVISION_POST_HEADERS)
				{
					$revision_total = count($post_list);
					$revision_index = $revision_total - $index;
					$subject = empty($row['post_subject']) ? $user->lang['PRIME_POST_REVISIONS_NO_SUBJECT'] : $row['post_subject'];
					if ($revision_index == $revision_total)
					{
						$postrow['POST_SUBJECT'] = sprintf($user->lang['PRIME_POST_REVISIONS_FINAL'], $subject);
					}
					else if (!isset($post_list[$index + 1]))
					{
						$postrow['POST_SUBJECT'] = sprintf($user->lang['PRIME_POST_REVISIONS_FIRST'], $subject);
					}
					else
					{
						$postrow['POST_SUBJECT'] = sprintf($user->lang['PRIME_POST_REVISIONS_COUNT'], $revision_index - 1, $subject);
					}
				}

				// Only display the resaon for editing on revisions, not the original.
				$approve_removal = $this->approve_removal;
				if (isset($post_list[$index + 1]))
				{
					$next_row = &$rowset[$post_list[$index + 1]];
					$post_edit_user = $next_row['post_edit_user'];
					$post_edit_date = $next_row['post_edit_time'];
					$postrow['EDIT_REASON'] 	= $next_row['post_edit_reason'];
					$postrow['EDITED_MESSAGE']	= sprintf($user->lang['PRIME_POST_REVISIONS_INFO'], get_username_string('full', $post_edit_user, $this->user_cache[$post_edit_user]['username'], $this->user_cache[$post_edit_user]['user_colour']), $user->format_date($post_edit_date));

					if (APPROVE_REMOVAL_EDITOR && !$this->approve_removal)
					{
						$approve_removal = ($user->data['user_id'] == $post_edit_user);
					}
				}
				$remove_history = ($index == 0 ? PRIME_POST_REVISION_UNDO : (!empty($row['post_edit_time']) ? $row['post_edit_time'] : ''));
				$postrow['U_DELETE'] = (!$approve_removal || !$remove_history) ? '' : append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $this->current_post['post_id'] . '&amp;remove_history=' . $remove_history);
			}
			else if (!empty($this->revision_info[$row['post_id']]['count']))
			{
				if ($this->can_view_history($row['user_id']))
				{
					$user->add_lang('mods/prime_post_revisions');
					$l_edited_link = '<a href="' . append_sid("viewtopic.$phpEx", "p={$row['post_id']}&amp;display_history=true") . '" class="view_edits">' . $user->lang['PRIME_POST_REVISIONS_VIEW'] . '</a>';
					$postrow['EDITED_MESSAGE'] = trim($postrow['EDITED_MESSAGE'] . ' ' . $l_edited_link);
				}
			}

		}

		/**
		* Assign template variables for viewing post revisions.
		*/
		function assign_template_variables($viewtopic_url, $viewtopic_title)
		{
			global $template, $user;

			if (!ENABLE_POST_REVISIONS)
			{
				return;
			}
			if ($this->viewing_history)
			{
				$viewtopic_title = (empty($viewtopic_title) ? $user->lang['PRIME_POST_REVISIONS_NO_SUBJECT'] : $viewtopic_title);
				$template->assign_vars(array(
					'VIEWING_REVISIONS'		=> true, // A new template variable indicating we are viewing the revisions
					'S_NUM_POSTS'			=> 1,	// Remove the filter/sort display options
					'S_DISPLAY_SEARCHBOX'	=> '',	// Remove the search box
					'S_DISPLAY_REPLY_INFO'	=> '',	// Remove the reply button
					'U_PRINT_TOPIC'			=> '',	// Remove print view
					'U_EMAIL_TOPIC'			=> '',	// Remove email topic
					'S_TOPIC_MOD'			=> '',
					'PAGE_TITLE'			=> sprintf($user->lang['PRIME_POST_REVISIONS_TITLE'], $viewtopic_title),
					'TOPIC_TITLE'			=> sprintf($user->lang['PRIME_POST_REVISIONS_TITLE'], $viewtopic_title),
					'U_VIEW_TOPIC'			=> $viewtopic_url,
					'FORUM_DESC'			=> $user->lang['PRIME_POST_REVISIONS_VIEWING_EXPLAIN'],
				));
				if (defined('REVISION_PAGE_HEADER') && REVISION_PAGE_HEADER)
				{
					global $phpbb_root_path, $phpEx;
					$remove_all = !$this->approve_removal ? '' : ' <a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $this->current_post['post_id'] . '&amp;remove_history=' . PRIME_POST_REVISION_WIPE) . '">' . $user->lang['PRIME_POST_REVISIONS_DELETES'] . '</a>';
					$template->assign_vars(array(
						'S_FORUM_RULES'		=> true,
						'L_FORUM_RULES'		=> $user->lang['PRIME_POST_REVISIONS_VIEWING'],
						'FORUM_RULES'		=> $user->lang['PRIME_POST_REVISIONS_VIEWING_EXPLAIN'] . $remove_all,
					));
				}
			}
		}
	}
}
?>