<?php
/**
*
* @package phpBB3
* @version $Id: prime_birthdate.php,v 1.2.5 2009/12/24 01:26:00 primehalo Exp $
* @copyright (c) 2007-2009 Ken F. Innes IV
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
* Age (in years) at which COPPA no longer applies.
*/
if (!defined('COPPA_AGE_CUTOFF'))
{
	define('COPPA_AGE_CUTOFF', 13);
}

/*
* Include only once.
*/
global $prime_birthdate;
if (!class_exists('prime_birthdate'))
{
	/**
	* Constants
	*/
	define('BIRTHDATE_DISALLOW', 0);
	define('BIRTHDATE_ALLOW', 1);
	define('BIRTHDATE_REQUIRE', 2);
	define('BIRTHDATE_LOCKED', 3);

	/**
	* Creates the form fields for selecting the Allow Birthdates options
	*/
	function make_allow_birthdays_select($key, $select_id = BIRTHDATE_REQUIRE)
	{
		global $user, $config;

		$options_array = array(
			BIRTHDATE_ALLOW		=> 'YES',
			BIRTHDATE_DISALLOW	=> 'NO',
			BIRTHDATE_REQUIRE	=> 'PRIME_BIRTHDATE_REQUIRE',
			BIRTHDATE_LOCKED	=> 'PRIME_BIRTHDATE_LOCK',
		);
		$select = '';
		$user->add_lang('mods/prime_birthdate');
		foreach ($options_array as $idx => $title)
		{
			$selected = ($config[$key] == $idx) ? ' checked="checked"' : '';
			$select .= '<label><input type="radio"' . ($idx == 1 ? 'id="' . $key . '"' : '') . ' name="config[' . $key . ']" value="' . $idx . '"' . $selected . ' class="radio" /> ' . $user->lang[$title] . '</label>' . "\n";
		}
		return($select);
	}

	/**
	* Class declaration
	*/
	class prime_birthdate
	{
		var $user_cache = array();

		/**
		* Constructor
		*/
		function prime_birthdate()
		{
			$this->initialize_options();
		}

		/**
		* Set defaults for options if they have not been set.
		*/
		function initialize_options()
		{
			global $config;

			$defaults = array(
				'minimum_age' => 4,
				'maximum_age' => 0,
			);
			foreach ($defaults as $option => $default)
			{
				if (!isset($config[$option]))
				{
					set_config($option, $default);
				}
			}
		}

		/**
		* Display the ACP options.
		*/
		function display_acp_options(&$display_vars, $mode)
		{
			global $config, $user;

			if ($mode == 'features')
			{
				$user->add_lang('mods/prime_birthdate');
				$display_vars['vars']['allow_birthdays'] = array('lang' => 'ALLOW_BIRTHDAYS', 'validate' => 'int', 'type' => 'custom', 'function' => 'make_allow_birthdays_select', 'params' => array('{KEY}', '{CONFIG_VALUE}'), 'explain' => true);
			}
			else if ($mode == 'registration')
			{
				$user->add_lang('mods/prime_birthdate');
				$display_copy = $display_vars['vars'];
				$display_vars['vars'] = array();
				foreach ($display_copy as $key => $val)
				{
					$display_vars['vars'][$key] = $val;
					if ($key == 'chg_passforce')	// Insert our option after this one
					{
						$display_vars['vars']['minimum_age'] = array('lang' => 'PRIME_BIRTHDATE_MIN','validate' => 'int',	'type' => 'text:3:3', 'explain' => true, 'append' => ' ' . $user->lang['PRIME_BIRTHDATE_YEARS_OLD']);
						$display_vars['vars']['maximum_age'] = array('lang' => 'PRIME_BIRTHDATE_MAX','validate' => 'int',	'type' => 'text:3:3', 'explain' => true, 'append' => ' ' . $user->lang['PRIME_BIRTHDATE_YEARS_OLD']);
					}
				}
			}
		}

		/**
		* @param integer $user_id - ID of the user to check
		* @return true if user wants their age displayed, otherwise false
		*/
		function user_show_age($user_id = 0)
		{
			return $this->get_user_setting($user_id, 'user_show_age');
		}

		/**
		* @param integer $user_id - ID of the user to check
		* @return true if user wants their birthday announced, otherwise false
		*/
		function user_congrats($user_id = 0)
		{
			return $this->get_user_setting($user_id, 'user_congrats');
		}

		/**
		*/
		function get_user_setting($user_id = 0, $setting = 'user_show_age')
		{
			if (empty($user_id))
			{
				return false;
			}

			// Was user data passed in instead of an ID?
			if (is_array($user_id) && isset($user_id['user_id']))
			{
				if (isset($user_id[$setting]))
				{
					$this->user_cache[$user_id][$setting] = $user_id[$setting];
					return $user_id[$setting];
				}
				$user_id = $user_id['user_id'];
			}

			if (!is_numeric($user_id))
			{
				return false;
			}

			// Is member in our cache?
			if (isset($this->user_cache[$user_id][$setting]))
			{
				return $this->user_cache[$user_id][$setting];
			}

			// Is member the current user?
			global $user;
			if ($user->data['user_id'] == $user_id && isset($user->data[$setting]))
			{
				return $user->data[$setting];
			}

			// Is member already cached?
			global $user_cache;
			if (isset($user_cache[$user_id]) && isset($user_cache[$user_id][$setting]))
			{
				return $user_cache[$user_id][$setting];
			}

			// Grab the member's info
			global $db;
			$sql = 'SELECT user_show_age, user_congrats FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
			$result = $db->sql_query($sql);
			if ($row = $db->sql_fetchrow($result))
			{
				$this->user_cache[$user_id]['user_show_age'] = $row['user_show_age'];
				$this->user_cache[$user_id]['user_congrats'] = $row['user_congrats'];
			}
			$db->sql_freeresult($result);
			return isset($row[$setting]) ? $row[$setting] : false;
		}

		/**
		* Returns a person's current age.
		* @param:  string - $date_of_birth (in the form "day-month-year")
		* @return: integer - Current age of individual based on the date of birth given.
		*/
		function get_age($date_of_birth) //day-month-year
		{
			$bdate = explode("-", $date_of_birth);
			$day	= isset($bdate[0]) ? (int)$bdate[0] : 0;
			$month	= isset($bdate[1]) ? (int)$bdate[1] : 0;
			$year	= isset($bdate[2]) ? (int)$bdate[2] : 0;
			if ($year)
			{
				$age = date("Y") - $year;
				if (($month > date("m")) || ($month == date("m") && date("d") < $day))
				{
					$age -= 1;
				}
				return $age;
			}
			return false;
		}

		/**
		* Determines whether or not birthdates are a requirement.
		*/
		function birthdate_required()
		{
			global $config;

			return ($config['allow_birthdays'] == BIRTHDATE_REQUIRE || $config['allow_birthdays'] == BIRTHDATE_LOCKED);
		}

		/**
		* Determines whether or not birthdates can be changed.
		* If not passing an argument, it returns the general board setting.
		* If passing in a birthdate, if returns true only if the board
		* setting is true and the birthdate is already valid.
		*/
		function birthdate_locked($date_of_birth = false)
		{
			global $config;

			$locked = ($config['allow_birthdays'] == BIRTHDATE_LOCKED);
			if ($locked && $date_of_birth !== false)
			{
				$locked = ($this->birthdate_error($date_of_birth) === false);
			}
			return ($locked);
		}

		/**
		* Validates a birth date.
		* @param:  string - $date_of_birth (in the form "day-month-year")
		* @return: boolean|string - Either false if validation succeeded or a string
		*          which will be used as the error message.
		*/
		function birthdate_error($date_of_birth) //day-month-year
		{
			global $config;

			$bdate = explode("-", $date_of_birth);
			$day	= isset($bdate[0]) ? (int)$bdate[0] : 0;
			$month	= isset($bdate[1]) ? (int)$bdate[1] : 0;
			$year	= isset($bdate[2]) ? (int)$bdate[2] : 0;
			if (!$day || !$month || !$year || $day < 1 || $day > 31 || $month < 1 || $month > 12 || ($year < 1901 && $year > 0) || $year > gmdate('Y', time()))
			{
				return 'INVALID_USER_BIRTHDAY';
			}
			if (checkdate($month, $day, $year) === false)
			{
				return 'INVALID_USER_BIRTHDAY';
			}
			if ($this->get_age($date_of_birth) < $config['minimum_age'])
			{
				return 'PRIME_BIRTHDATE_YOUNG';
			}
			if (!empty($config['maximum_age']) && $this->get_age($date_of_birth) > $config['maximum_age'])
			{
				return 'PRIME_BIRTHDATE_OLD';
			}
			return(false);
		}

		/**
		* Creates the date options for the form dropdown select boxes.
		* @param:  string - $date_of_birth (in the form "day-month-year")
		* @return: array of strings - Each array element contains the options
		*          for a select dropdown box
		*/
		function get_birthdate_options($date_of_birth)
		{
			global $user;
			$bdate = explode("-", $date_of_birth); //day-month-year
			$day	= isset($bdate[0]) ? (int)$bdate[0] : 0;
			$month	= isset($bdate[1]) ? (int)$bdate[1] : 0;
			$year	= isset($bdate[2]) ? (int)$bdate[2] : 0;

			$s_birthday_day_options = '<option value="0"' . ((!$day) ? ' selected="selected"' : '') . '>' . $user->lang['DAY'] . '</option>';
			for ($i = 1; $i < 32; $i++)
			{
				$selected = ($i == $day) ? ' selected="selected"' : '';
				$s_birthday_day_options .= "<option value=\"$i\"$selected>$i</option>";
			}

			$s_birthday_month_options = '<option value="0"' . ((!$month) ? ' selected="selected"' : '') . '>' . $user->lang['MONTH'] . '</option>';
			$lang_dates = array(1 => $user->lang['datetime']['Jan'], $user->lang['datetime']['Feb'], $user->lang['datetime']['Mar'], $user->lang['datetime']['Apr'], $user->lang['datetime']['May_short'], $user->lang['datetime']['Jun'], $user->lang['datetime']['Jul'], $user->lang['datetime']['Aug'], $user->lang['datetime']['Sep'], $user->lang['datetime']['Oct'], $user->lang['datetime']['Nov'], $user->lang['datetime']['Dec']);
			for ($i = 1; $i < 13; $i++)
			{
				$selected = ($i == $month) ? ' selected="selected"' : '';
				$s_birthday_month_options .= "<option value=\"$i\"$selected>{$lang_dates[$i]}</option>";
			}

			$now = getdate();
			$s_birthday_year_options = '<option value="0"' . ((!$year) ? ' selected="selected"' : '') . '>' . $user->lang['YEAR'] . '</option>';
			for ($i = $now['year']; $i > $now['year'] - 100; $i--)
			{
				$selected = ($i == $year) ? ' selected="selected"' : '';
				$s_birthday_year_options .= "<option value=\"$i\"$selected>$i</option>";
			}
			unset($now);
			return(array($s_birthday_day_options, $s_birthday_month_options, $s_birthday_year_options));
		}

		/**
		* index.php
		*/
		function index_inject_sql(&$sql)
		{
			$sql = str_replace('FROM ' . USERS_TABLE, ', u.user_show_age FROM ' . USERS_TABLE, $sql);
			$sql = str_replace('AND u.user_birthday LIKE', 'AND u.user_congrats = 1 AND u.user_birthday LIKE', $sql);
		}

		/**
		* index.php
		* This will prevent a user's username or age from being displayed on the
		* birthday list if they have chosen not to make it publicly viewable.
		*/
		function index_alter_birthday_list(&$row)
		{
			$row['user_birthday'] = !empty($row['user_show_age']) ? $row['user_birthday'] : '';
		}

		/**
		* memberlist.php
		*/
		function memberlist_show_age(&$data, &$age)
		{
			if (!isset($data['user_show_age']))
			{
				//global $db;
				//$result = $db->sql_query('SELECT user_show_age FROM ' . USERS_TABLE . ' WHERE user_id = ' . $data['user_id']);
				//$row = $db->sql_fetchrow($result);
				//$db->sql_freeresult($result);
				//$data['user_show_age'] = $row['user_show_age'];
				$data['user_show_age'] = $this->user_show_age($data['user_id']);
			}
			$age = empty($data['user_show_age']) ? '' : $age;
		}

		/**
		*/
		function alter_user_cache(&$user_cache, &$row)
		{
			$poster_id = $row['poster_id'];
			$user_cache[$poster_id]['user_show_age'] = isset($row['user_show_age']) ? $row['user_show_age'] : 0;
			$user_cache[$poster_id]['user_congrats'] = isset($row['user_congrats']) ? $row['user_congrats'] : 0;
			$user_cache[$poster_id]['age'] = empty($user_cache[$poster_id]['user_show_age']) ? '' : $user_cache[$poster_id]['age'];
		}

		/**
		* Here we check to see if the user needs to enter a valid birth date, and
		* if they do then we redirect them to the page where they can do so.
		*/
		function enforce_birthdate(&$user)
		{
			global $phpbb_root_path, $phpEx;

			if (!defined('IN_ADMIN') && !defined('ADMIN_START') && !defined('IN_LOGIN') && !empty($user->data['is_registered']))
			{
				// Make sure we're not already where we need to be.
				if ($user->page['page_name'] != "ucp.$phpEx" || strpos($user->page['query_string'], 'mode=profile_info') === false)
				{
					// Don't redirect if the user is in the middle of posting, as we wouldn't want them to lose everything they've typed.
					if ($user->page['page_name'] != "posting.$phpEx" || (strpos($user->page['query_string'], 'mode=post') === false && strpos($user->page['query_string'], 'mode=reply') === false && strpos($user->page['query_string'], 'mode=edit') === false))
					{
						include($phpbb_root_path . 'includes/prime_birthdate.' . $phpEx);
						if ($this->birthdate_required() && $this->birthdate_error($user->data['user_birthday']))
						{
							redirect(append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=profile&amp;mode=profile_info&amp;required=birthday'));
						}
					}
				}
			}
		}

		/**
		*/
		function acp_users_get_vars(&$data, &$user_row)
		{
			global $user;

			$data['show_age'] = request_var('show_age', $user_row['user_show_age']);
			$data['congrats'] = request_var('congrats', $user_row['user_congrats']);
			$user->add_lang('mods/prime_birthdate');
		}

		/**
		*/
		function acp_users_inject_sql(&$sql_array, &$data)
		{
			$sql_array['user_show_age'] = $data['show_age'];
			$sql_array['user_congrats'] = $data['congrats'];
		}

		/**
		* Format the birthdate form fields to match the version shown during registration,
		* and assign template variables for user birthdate-related preferences.
		*/
		function acp_users_format_fields(&$data, &$day_field, &$month_field, &$year_field)
		{
			global $template;

			$birthdate = sprintf('%2d-%2d-%4d', $data['bday_day'], $data['bday_month'], $data['bday_year']);
			$bday_options = $this->get_birthdate_options($birthdate);
			$day_field   = $bday_options[0];
			$month_field = $bday_options[1];
			$year_field  = $bday_options[2];
			$template->assign_vars(array(
				'S_SHOW_AGE'		=> $data['show_age'],
				'S_SHOW_CONGRATS'	=> $data['congrats'],
				'S_CURRENT_AGE'		=> $this->get_age($birthdate),
				'PRIME_BIRTHDATE'	=> true,
			));
		}

		/**
		*/
		function ucp_profile_get_vars(&$data)
		{
			global $user, $config;

			if ($config['allow_birthdays'])
			{
				if ($this->birthdate_locked($user->data['user_birthday']))
				{
					$data['bday_day'] = $data['bday_month'] = $data['bday_year'] = 0;
					if ($user->data['user_birthday'])
					{
						list($data['bday_day'], $data['bday_month'], $data['bday_year']) = explode('-', $user->data['user_birthday']);
					}
				}
				$data['show_age'] = request_var('show_age', $user->data['user_show_age']);
				$data['congrats'] = request_var('congrats', $user->data['user_congrats']);
				$user->add_lang('mods/prime_birthdate');
			}
		}

		/**
		*/
		function ucp_profile_error_checking(&$data, &$error)
		{
			global $config;

			if ($config['allow_birthdays'])
			{
				if ($this->birthdate_required() && ($result = $this->birthdate_error("{$data['bday_day']}-{$data['bday_month']}-{$data['bday_year']}")) !== false)
				{
					$error[] = ($result == 'PRIME_BIRTHDATE_EMPTY') ? 'INVALID_USER_BIRTHDAY' : $result;
					$error = array_unique($error);
				}
			}
		}

		/**
		*/
		function ucp_profile_insert_sql(&$sql_array, &$data)
		{
			global $user;

			if ($this->birthdate_locked($user->data['user_birthday']))
			{
				unset($sql_array['user_birthday']);
			}
			$sql_array['user_show_age'] = $data['show_age'];
			$sql_array['user_congrats'] = $data['congrats'];
		}

		/**
		* This will format the birthdate form fields so they match with the version shown during registration.
		*/
		function ucp_profile_format_fields(&$data, &$day_field, &$month_field, &$year_field, &$error)
		{
			global $user, $template, $config;

			if (request_var('required', '') == 'birthday' && $this->birthdate_error($user->data['user_birthday']))
			{
				$error[] = $user->lang['PRIME_BIRTHDATE_EMPTY'];
			}
			$bday_options = $this->get_birthdate_options(sprintf('%2d-%2d-%4d', $data['bday_day'], $data['bday_month'], $data['bday_year']));
			$day_field		= $bday_options[0];
			$month_field	= $bday_options[1];
			$year_field		= $bday_options[2];
			$template->assign_vars(array(
				'S_SHOW_AGE'				=> $data['show_age'],
				'S_SHOW_CONGRATS'			=> $data['congrats'],
				'S_DISPLAY_BIRTHDAY_LIST'	=> ($config['load_birthdays']) ? true : false,
				'S_BIRTHDAYS_REQUIRED'		=> $this->birthdate_required(),
				'S_BIRTHDAYS_LOCKED'		=> $this->birthdate_locked($user->data['user_birthday']),
				'S_BIRTHDAYS_UCP_PROFILE'	=> true,
			));
		}

		/**
		*/
		function ucp_register_init(&$coppa, &$agreed, $change_lang)
		{
			global $config, $template, $user;

			$birthdate = '';
			if ($config['allow_birthdays'])
			{
				// We grab submitted birth date information, or initialize it if none exists.
				$bday['month'] = request_var('bday_month', 0);
				$bday['day']   = request_var('bday_day', 0);
				$bday['year']  = request_var('bday_year', 0);
				$birthdate  = sprintf('%2d-%2d-%4d', $bday['day'], $bday['month'], $bday['year']);

				// The user has agreed to the registration terms and we are trying to head to
				// the registration form page. We check the birth date, and if it's invalid
				// we make them do the terms page again so they can fix it. If it is valid
				// and COPPA is required, then we check their age. We initialize $coppa to
				// zero so that the original COPPA page will be skipped. We need to assign
				// the ERROR field in the template for cases when the user either does not
				// enter their birth date or enters an invalid date.
				$user->add_lang('mods/prime_birthdate');
				if ($coppa === false && $config['coppa_enable'])
				{
					$coppa = 0;
				}
				if ($agreed && $change_lang === '')
				{
					if ($this->birthdate_required() && ($result = $this->birthdate_error($birthdate)) !== false)
					{
						$agreed = false;
						$template->assign_var('ERROR', $user->lang[$result] . '<br />');
					}
					else if ($this->get_age($birthdate) < COPPA_AGE_CUTOFF && $config['coppa_enable'])
					{
						$coppa = 1;
					}
				}

				// We create the birth date form fields for display in the templates.
				$bday_options = $this->get_birthdate_options($birthdate);
				$template->assign_vars(array(
					'S_BIRTHDAY_DAY_OPTIONS'	=> $bday_options[0],
					'S_BIRTHDAY_MONTH_OPTIONS'	=> $bday_options[1],
					'S_BIRTHDAY_YEAR_OPTIONS'	=> $bday_options[2],
					'S_BIRTHDAYS_ENABLED'		=> $agreed ? 'UCP_REGISTER' : 'UCP_AGREEMENT',
					'S_BIRTHDAYS_REQUIRED'		=> $this->birthdate_required(),
					'COPPA_AGE_CUTOFF'			=> COPPA_AGE_CUTOFF,
				));
			}
			return $birthdate;
		}

		/**
		* The user submitted the registration form, so check the birth date again.
		* We also check the age again and turn on the COPPA if need be.
		*/
		function ucp_register_error_checking(&$error, &$coppa, &$birthdate)
		{
			global $config, $user;

			if ($config['allow_birthdays'])
			{
				if ($this->birthdate_required() && ($result = $this->birthdate_error($birthdate)) !== false)
				{
					$error[] = $user->lang[$result];
				}
				else if ($config['coppa_enable'] && $this->get_age($birthdate) < COPPA_AGE_CUTOFF)
				{
					$coppa = 1;
				}
			}
		}

		/**
		*/
		function ucp_register_update_user_row(&$user_row, &$birthdate)
		{
			global $config;

			if ($config['allow_birthdays'])
			{
				$user_row['user_birthday'] = $birthdate;
			}
		}
	}
	// End class

	$prime_birthdate = new prime_birthdate();
}
?>