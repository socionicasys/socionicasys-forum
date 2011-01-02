<?php

if (!defined('IN_PHPBB'))
{
    exit;
}

function user_per_page()
{
	global $user, $config;
	
	if($user->data['is_registered'] && !defined('ADMIN_START'))
	{
	
		$user->get_profile_fields( $user->data['user_id'] );
		
		if(isset($user->profile_fields['pf_posts_per_page']))
		{
			
			$config['posts_per_page'] = ($user->profile_fields['pf_posts_per_page'] > 0 ? (int) $user->profile_fields['pf_posts_per_page'] :  $config['posts_per_page']);
		}
		
		if(isset($user->profile_fields['pf_topics_per_page']))
		{
			$config['topics_per_page'] = ($user->profile_fields['pf_topics_per_page'] > 0 ? (int) $user->profile_fields['pf_topics_per_page'] : $config['topics_per_page']);
		}
	
	}
}

$phpbb_hook->register('phpbb_user_session_handler', 'user_per_page');

?>