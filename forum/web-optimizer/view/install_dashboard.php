<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Outputs main (dashboard) page
 *
 **/
?><noscript><?php
	echo _WEBO_NEW_NOSCRIPT;
?></noscript><div class="wssK wssK0"><div class="wssK1"><h1 class="wssB"><?php
	echo _WEBO_DASHBOARD_CRITICAL;
?></h1><h2 class="wssB1"><?php
	echo _WEBO_DASHBOARD_CRITICAL_FAILED;
?></h2><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_DO;
?>:</p><ol class="wssO7 wssK02" style="display:none"><li class="wssO8"><p class="wssI"><?php 
	echo _WEBO_DASHBOARD_CRITICAL_STEP1_1;
?></p></li><li class="wssO8"><p class="wssI"><?php 
	echo _WEBO_DASHBOARD_CRITICAL_STEP1_2;
?> (<code><?php
	echo str_replace("//", "/", str_replace("\\", "/", realpath(dirname(__FILE__) . '/../') . '/'));
?></code>).</p></li><li class="wssO8"><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_STEP1_3;
?></p></li><li class="wssO8"><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_STEP1_4;
?></p></li><li class="wssO8"><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_STEP1_5;
?></p></li></ol><ol class="wssO7 wssK03"><li class="wssO8"><p class="wssI"><?php 
	echo _WEBO_DASHBOARD_CRITICAL_STEP2_1;
?> <code><?php
	echo str_replace("//", "/", str_replace("\\", "/", realpath(dirname(__FILE__) . '/../') . '/libs/css/wss.css'));
?></code>.</p></li><li class="wssO8"><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_STEP2_2;
?></p></li><li class="wssO8"><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_STEP2_3;
?></p></li><li class="wssO8"><p class="wssI"><?php
	echo _WEBO_DASHBOARD_CRITICAL_STEP1_5;
?></p></li></ol><p class="wssI"><?php 
	echo _WEBO_DASHBOARD_CRITICAL_REFER;
?> <a href="http://code.google.com/p/web-optimizator/w/list" rel="nofollow" class="wssJ"><?php
	echo _WEBO_DASHBOARD_CRITICAL_DOCS;
?></a> <?php
	echo _WEBO_DASHBOARD_CRITICAL_OR;
?> <a href="http://code.google.com/p/web-optimizator/issues/list" rel="nofollow" class="wssJ"><?php
	echo _WEBO_DASHBOARD_CRITICAL_ISSUES;
?></a>.</p>
</div></div><ul class="wssM"><li class="wssM1"><a class="wssM2" href="#wss_dashboard" title="<?php
	echo _WEBO_SPLASH2_CONTROLPANEL_TITLE;
?>"><span class="wssM5"></span><span class="wssM4 wssM10"><?php
	echo _WEBO_SPLASH2_CONTROLPANEL;
?></span></a></li><li class="wssM1"><a href="#wss_options" class="wssM3" title="<?php
	echo _WEBO_SPLASH2_OPTIONS_TITLE;
?>"><span class="wssM5"></span><span class="wssM4 wssM11"><?php
	echo _WEBO_SPLASH2_OPTIONS;
?></span></a></li><li class="wssM1"><a href="#wss_system" class="wssM3" title="<?php
	echo _WEBO_DASHBOARD_SYSTEM_TITLE;
?>"><span class="wssM5"></span><span class="wssM4 wssM12"><?php
	echo _WEBO_DASHBOARD_SYSTEM;
?></span></a></li><li class="wssM1"><a href="#wss_account" class="wssM3" title="<?php
	echo _WEBO_DASHBOARD_ACCOUNT_TITLE;
?>"><span class="wssM5"></span><span class="wssM4 wssM14"><?php
	echo _WEBO_DASHBOARD_ACCOUNT;
?></span></a></li><li class="wssM1"><a class="wssM3" href="#wss_awards" title="<?php
	echo _WEBO_DASHBOARD_AWARDS;
?>"><span class="wssM5"></span><span class="wssM4 wssM16"><?php
	echo _WEBO_DASHBOARD_AWARDS_TITLE;
?></span></a></li></ul><div class="wssN"><div class="wssN4"><div class="wssN5 wssN11"><div class="wssN2"><h2 class="wssB"><a href="<?php
	echo _WEBO_DASHBOARD_ORDER;
?>" class="wssN3"><?php
	echo _WEBO_DASHBOARD_HELP;
?></a></h2><p class="wssI"><?php
	echo _WEBO_DASHBOARD_HELP1;
?></p><p class="wssI"><?php
	echo _WEBO_DASHBOARD_HELP2;
?></p><p class="wssI"><a href="<?php
	echo _WEBO_DASHBOARD_ORDER;
?>" class="wssJ5"><?php
	echo _WEBO_DASHBOARD_SEND;
?><span class="wssJ6"></span></a></p></div></div><div class="wssN5<?php
	echo strpos($cookie, 'wss_aw') !== false ? ' wssA0' : '';
?>" id="wss_aw"><div class="wssN2"><h2 class="wssB"><a href="#wss_awards" class="wssN3"><?php
	echo _WEBO_DASHBOARD_AWARDS_CURRENT;
?></a></h2><div id="wss_awards"><p class="wssI3"><?php 
	echo _WEBO_DASHBOARD_LOADING;
?></p></div><a class="wssJ20" href="javascript:_.hide('wss_aw')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN5<?php
	echo strpos($cookie, 'wss_buzz') !== false ? ' wssA0' : '';
?>" id="wss_buzz"><div class="wssN2"><h2 class="wssB"><a href="#wss_about" class="wssN3"><?php
	echo _WEBO_DASHBOARD_BUZZ;
?></a></h2><p class="wssI"><a href="http://extensions.joomla.org/extensions/site-management/site-performance/10152" rel="nofollow" class="wssJ wssJ13">Joomla! Extensions Directory</a></p><p class="wssI"><a href="http://twitter.com/wboptimizer" rel="nofollow" class="wssJ wssJ10">Twitter</a></p><p class="wssI"><a href="http://www.facebook.com/pages/Web-Optimizer/183974322020" rel="nofollow" class="wssJ wssJ11">Facebook</a></p><p class="wssI"><a href="http://blog.webogroup.com/" class="wssJ wssJ12">Blogger</a></p><a class="wssJ20" href="javascript:_.hide('wss_buzz')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN5<?php
	echo strpos($cookie, 'wss_news') !== false ? ' wssA0' : '';
?>" id="wss_news"><div class="wssN2 wssN21"><h2 class="wssB"><a href="http://blog.webogroup.com/" class="wssN3"><?php
	echo _WEBO_DASHBOARD_NEWS;
?></a></h2><div id="wss_feed"><p class="wssI3"><?php 
	echo _WEBO_DASHBOARD_LOADING;
?></p></div><a class="wssJ20" href="javascript:_.hide('wss_news')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN5<?php
	echo strpos($cookie, 'wss_links') !== false ? ' wssA0' : '';
?>" id="wss_links"><div class="wssN2"><h2 class="wssB"><a href="http://www.webogroup.com/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer" class="wssN3"><?php
	echo _WEBO_DASHBOARD_LINKS;
?></a></h2><ul class="wssO wssO3"><li class="wssO4"><a href="http://www.webogroup.com/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer" class="wssJ wssJ2"><?php
	echo _WEBO_DASHBOARD_LINKS_WEBSITE;
?></a></li><li class="wssO4"><a href="http://code.google.com/p/web-optimizator/w/list?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer" class="wssJ wssJ2"><?php
	echo _WEBO_DASHBOARD_LINKS_UG;
?></a></li><li class="wssO4"><a href="http://code.google.com/p/web-optimizator/issues/list?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer" class="wssJ wssJ2"><?php
	echo _WEBO_DASHBOARD_LINKS_ISSUES;
?></a></li><li class="wssO4"><a href="http://www.webogroup.com/home/support/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer" class="wssJ wssJ2"><?php
	echo _WEBO_DASHBOARD_LINKS_SUPPORT;
?></a></li></ul><a class="wssJ20" href="javascript:_.hide('wss_links')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div></div><div class="wssN4"><div class="wssN5<?php
	echo strpos($cookie, 'wss_che') !== false ? ' wssA0' : '';
?>" id="wss_che"><div class="wssN2 wssN21"><h2 class="wssB"><a href="#wss_system#cache" class="wssN3"><?php
	echo _WEBO_DASHBOARD_CACHE_TITLE;
?></a></h2><div id="wss_cache"><p class="wssI3"><?php 
	echo _WEBO_DASHBOARD_LOADING;
?></p></div><a class="wssJ20" href="javascript:_.hide('wss_che')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN5 wssN21<?php
	echo ($premium < 2?' wssN20' : '') .
		(strpos($cookie, 'wss_sp') !== false ? ' wssA0' : '');
?>" id="wss_sp"><div class="wssN2"><h2 class="wssB"><<?php
	echo $premium < 2 ? 'span' : 'span';
?> class="wssN3"><?php
	echo _WEBO_DASHBOARD_RESULTS;
?></<?php
	echo $premium < 2 ? 'span' : 'span';
?>></h2><?php
	if ($premium < 2) {
?><p class="wssI wssI1"><?php
		echo _WEBO_DASHBOARD_AVAILABLE;
?></p><?php
	} else {
?><div id="wss_speed"><p class="wssI3"><?php 
		echo _WEBO_DASHBOARD_LOADING;
?></p></div><?php
	}
?><a class="wssJ20" href="javascript:_.hide('wss_sp')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN5<?php
	echo ($premium<2 ? ' wssN20' : '') .
		(strpos($cookie, 'wss_tools') !== false ? ' wssA0' : '');
?>" id="wss_tools"><div class="wssN2 wssN21"><h2 class="wssB"><span class="wssN3"><?php
	echo _WEBO_DASHBOARD_TOOLS;
?></span></h2><ul class="wssO wssO3"><li class="wssO4"><<?php
	echo $premium<2 ? 'span' : 'a href="#wss_gzip"';
?> class="wssJ"><?php
	echo _WEBO_TOOLS_GZIP;
?></<?php
	echo $premium<2 ? 'span' : 'a';
?>></li><li class="wssO4"><<?php
	echo $premium<2 ? 'span' : 'a href="#wss_image"';
?> class="wssJ"><?php
	echo _WEBO_TOOLS_IMAGE;
?></<?php
	echo $premium<2 ? 'span' : 'a';
?>></li><li class="wssO4"><<?php
	echo $premium<2 ? 'span' : 'a href="#wss_cdn"';
?> class="wssJ"><?php
	echo _WEBO_TOOLS_CDN;
?></<?php
	echo $premium<2 ? 'span' : 'a';
?>></li></ul><?php
	if ($premium < 2) {
?><p class="wssI wssI1"><?php
		echo _WEBO_DASHBOARD_AVAILABLE;
?></p><?php
	}
?><a class="wssJ20" href="javascript:_.hide('wss_tools')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN5<?php
	echo strpos($cookie, 'wss_updates') !== false ? ' wssA0' : '';
?>" id="wss_updates"><div class="wssN2 wssN21"><h2 class="wssB"><a href="#wss_system#updates" class="wssN3"><?php
	echo _WEBO_DASHBOARD_UPDATES;
?></a></h2><?php
	if ($version_new > $version) {
?><div id="wss_upd" title="<?php
		echo _WEBO_LOGIN_VERSION;
?> <?php
		echo $version_new;
?>"><p class="wssI3"><?php 
		echo _WEBO_DASHBOARD_LOADING;
?></p></div><?php
	} else {
?><p class="wssI"><?php
		echo _WEBO_SYSTEM_NOUPDATES;
?></p><?php
	}
?><a class="wssJ20" href="javascript:_.hide('wss_updates')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div></div><div class="wssN1<?php
	echo strpos($cookie, 'wss_status') !== false ? ' wssA0' : '';
?>" id="wss_status"><div class="wssN2"><h2 class="wssB"><a href="#wss_system" class="wssN3"><?php
	echo _WEBO_DASHBOARD_STATUS;
?></a></h2><p class="wssI">WEBO Site SpeedUp <?php
		echo _WEBO_DASHBOARD_STATUS_IS;
?> <strong><?php
	if ($active) {
		echo _WEBO_DASHBOARD_STATUS_ACTIVE;
?></strong> (<?php
		echo _WEBO_DASHBOARD_STATUS_LIVE;
?>).</p><p class="wssI"><?php
		echo _WEBO_DASHBOARD_STATUS_WORKING;
?><a href="http://<?php
		echo $website;
?>/" class="wssJ"><?php
		echo _WEBO_DASHBOARD_STATUS_WORKING2;
?></a><?php
		echo _WEBO_DASHBOARD_STATUS_WORKING3;
?></p><p class="wssI"><a href="#wss_status" class="wssJ wssJ5"><?php
		echo _WEBO_DASHBOARD_STATUS_DISABLE;
	} else {
		echo _WEBO_DASHBOARD_STATUS_NOTACTIVE;
?></strong> (<?php
		echo _WEBO_DASHBOARD_STATUS_DEBUG;
?>)</p><p class="wssI"><?php
		echo _WEBO_DASHBOARD_STATUS_TESTING;
?></p><ul class="wssO wssO3"><li class="wssO4"><?php
		echo _WEBO_DASHBOARD_STATUS_TESTING2;
?><a href="http://<?php
		echo $website;
?>/?web_optimizer_debug=1" class="wssJ"><?php
		echo _WEBO_DASHBOARD_STATUS_TESTING4;
?></a>,</li><li class="wssO4"><?php
		echo _WEBO_DASHBOARD_STATUS_COOKIE;
?><a href="javascript:_.doc.cookie='web_optimizer_debug=1;expires='+(new Date(new Date().getTime()+86400000)).toGMTString()+';path=/;domain='+_.doc.domain+';';_.doc.location.href='http://'+_.doc.domain+'/'"><?php
		echo _WEBO_DASHBOARD_STATUS_COOKIE2;
?></a>.</li></ul><p class="wssI"><?php
		echo _WEBO_DASHBOARD_STATUS_TESTING3;
?></p><p class="wssI"><a href="#wss_status" class="wssJ wssJ7"><?php
		echo _WEBO_DASHBOARD_STATUS_ENABLE;
	}
?><span class="wssJ6"></span></a></p><a class="wssJ20" href="javascript:_.hide('wss_status')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN1<?php
	echo strpos($cookie, 'wss_opt') !== false ? ' wssA0' : '';
?>" id="wss_opt"><div class="wssN2 wssN21"><h2 class="wssB"><a href="#wss_options" class="wssN3"><?php
	echo _WEBO_SPLASH2_OPTIONS;
?></a></h2><div id="wss_options"><p class="wssI3"><?php 
	echo _WEBO_DASHBOARD_LOADING;
?></p></div><a class="wssJ20" href="javascript:_.hide('wss_opt')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN1<?php
	echo strpos($cookie, 'wss_sys') !== false ? ' wssA0' : '';
?>" id="wss_sys"><div class="wssN2 wssN21"><h2 class="wssB"><a href="#wss_system" class="wssN3"><?php
	echo _WEBO_SYSTEM_TITLE;
?></a></h2><div id="wss_system"><p class="wssI3"><?php 
	echo _WEBO_DASHBOARD_LOADING;
?></p></div><a class="wssJ20" href="javascript:_.hide('wss_sys')" title="<?php
	echo _WEBO_HELP_MINIMIZE;
?>"></a></div></div><div class="wssN1 wssN12"><div class="wssN2 wssN21"><h2 class="wssB"><span class="wssN3"><?php
	echo _WEBO_DASHBOARD_ALL;
?></span></h2><p class="wssI"><a href="javascript:_('.wss_a')[0].className='wss_a wss_a1';void(0)"><?php
	echo _WEBO_HELP_HELP;
?></a></p><?php
	foreach (array(
		'wss_status' => '_WEBO_DASHBOARD_STATUS',
		'wss_opt' => '_WEBO_SPLASH2_OPTIONS',
		'wss_sys' => '_WEBO_SYSTEM_TITLE',
		'wss_che' => '_WEBO_DASHBOARD_CACHE_TITLE',
		'wss_sp' => '_WEBO_DASHBOARD_RESULTS',
		'wss_tools' => '_WEBO_DASHBOARD_TOOLS',
		'wss_links' => '_WEBO_DASHBOARD_LINKS',
		'wss_awards' => '_WEBO_DASHBOARD_AWARDS_TITLE',
		'wss_updates' => '_WEBO_DASHBOARD_UPDATES',
		'wss_buzz' => '_WEBO_DASHBOARD_BUZZ',
		'wss_news' => '_WEBO_DASHBOARD_NEWS'
		) as $key => $val) {
			if (strpos($cookie, $key) !== false) {
?><p class="wssI <?php
				echo $key;
?>"><a href="javascript:_.show('<?php
				echo $key;
?>')" class="wssJ"><?php
				echo constant($val);
?></a></p><?php
			}
	}
?></div></div></div><div class="wss_h"><h4 class="wss_l"><span id="wss_prog">0</span>%<span class="wss_m"></span></h4><p id="wss_mess"></p><span id="wss_mess1" class="wssA0"><?php
	echo _WEBO_UPGRADE_FILE;
?></span></div><script type="text/javascript">wss_fee=<?php
	echo round($fee);
?>;wss_license='<?php
	echo $license;
?>';
wss_pass='<?php
	echo $password;
?>';
wss_c='<?php
	echo $cache_folder;
?>';
wss_messages=["<?php
	echo _WEBO_DASHBOARD_STATUS0;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS1;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS2;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS2;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS4;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS5;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS6;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS6;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS8;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS8;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS10;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS11;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS12;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS13;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS14;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS15;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS16;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS17;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS18;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS19;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS20;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS21;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS22;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS23;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS24;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS10;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS11;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS12;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS13;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS14;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS15;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS16;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS17;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS18;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS19;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS20;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS21;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS22;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS23;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS24;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS10;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS11;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS12;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS13;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS14;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS15;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS16;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS17;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS18;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS19;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS20;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS21;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS22;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS23;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS24;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS10;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS11;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS12;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS13;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS14;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS15;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS16;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS17;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS18;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS19;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS20;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS21;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS22;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS23;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS24;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS10;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS11;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS12;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS13;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS14;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS15;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS16;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS17;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS18;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS19;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS20;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS21;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS22;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS23;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS24;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS85;
?> (<?php
	echo _WEBO_DASHBOARD_STATUS_ALL;
?>)",
"<?php
	echo _WEBO_DASHBOARD_STATUS85;
?> (IE6)",
"<?php
	echo _WEBO_DASHBOARD_STATUS85;
?> (IE7)",
"<?php
	echo _WEBO_DASHBOARD_STATUS85;
?> (IE8)",
"<?php
	echo _WEBO_DASHBOARD_STATUS85;
?> (IE7@Vista)",
"<?php
	echo _WEBO_DASHBOARD_STATUS90;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS90;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS90;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS90;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS90;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS95;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS95;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS95;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS95;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS95;
?>",
"<?php
	echo _WEBO_DASHBOARD_STATUS100;
?>"];
wss_install="<?php
	echo _WEBO_LOGIN_UPGRADE;
?> <?php
	echo _WEBO_LOGIN_UPGRADE_TO;
?> ";wss_kb="<?php
	echo _WEBO_LOGIN_EFFICIENCY_KB;
?>";
wss_confirm="<?php
	echo str_replace('"', '\"', _WEBO_OPTIONS_CONFIRM);
?>";
wss_outof="<?php
	echo str_replace('"', '\"', _WEBO_GZIP_OUTOF);
?>";
wss_error0="<?php
	echo _WEBO_GZIP_NOSUCCESS;
?>";
wss_error1="<?php
	echo _WEBO_SPLASH3_CANTWRITE4;
?>";
wss_error2="<?php
	echo _WEBO_GZIP_ERROR;
?>";
wss_error6=wss_error67="<?php
	echo _WEBO_CDN_NOACCESS;
?>";
setTimeout(function(){if(typeof _==='undefined'){setTimeout(arguments.callee,10)}else{
var a=_.doc,b=a.createElement('script');b.type='text/javascript';
b.src='http://i.webo.in/wss/index.<?php
	echo $language;
	echo defined("LANG_CHARSET") && LANG_CHARSET != 'UTF-8' ? '.cp1251' : '';
?>.js';a.body.appendChild(b)}},50)</script>