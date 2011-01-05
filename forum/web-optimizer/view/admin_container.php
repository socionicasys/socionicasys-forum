<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Outputs general container for pages of administation interface
 *
 **/
 
/* set correct content charset */
if (!$skip_render) {
	header('Content-Type: text/html;charset=' . _WEBO_CHARSET);
}

$ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';

if (!$ajax) {
	if (!$skip_render) {
?><!DOCTYPE html><html><head><title><?php
		echo _WEBO_GENERAL_TITLE;
?> | <?php
		echo $title
?></title><meta http-equiv="content-type" content="text/html;charset=<?php
		echo _WEBO_CHARSET;
?>"/><link href="libs/css/wss.css?<?php
		if (empty($page) || (!empty($page) && $page != 'install_enter_password')) {
			echo $version;
		}
?>" rel="stylesheet" type="text/css"/><link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACEklEQVQ4T3WST0iTcRjHf3nwtNOC/twW6kGvMXArYd1EN9pi4C5BJHRYSO80oqRAGsgGleDy1Nb8c8j0IpMVYjDqEhP6g77uUnpZHhL0IKRbyz3t8wuHbe4LDzw838/nfbcxpWqSzea8y8vZz/Pz7wrT0+kyw86NrpavJpNZt6RSH8xY7LWMj8+eOHQwsHVyMrm4Mzr6UqLRSVla+ihbW9tSLP7Ww86NDiaZTO3+95CpqUVzeHhCIpFJDTcKHQwsjpYXFt73GMZTGRwck3z+Z0P5KDCwOLgqFpv91N//WGZm0nVwqfRHDg/LdXdYHFxlGE8KgcAD2dj4IWtrm6JOuSrf8a0G4/G0HpJIvNEdDCwOrvL775XdbkMODgoavNw1IM5Lt/XbbRcCetg7HUHdEVgcXNXdPVB2uW7J/v6/B8zNZfSbjNBzsZ726GHnRkdgcXCVz3e3YLdfl1xuU5fFYknOnPVqIRp9pYedGx2BxcFVwWDkS3u7X8LhePVHevgoIefOX6t81KIedm5HgcXBVeHwix6bzS1tbT4xze8ayOe3qz8eYedGYFpargoOrv4v9PXdN63WK9LR4ZfV1W/SKHQwsDjVf+LIyITF6by509zcKRZLl4RCz2RlZV329n7pYedGB+Nw3NjFUcfDobf3jtnUZBelLp44dDB18vEMDY15PZ7Q19ZWb6HyxjLDzo2ulv8LN6Bqnkiu8fYAAAAASUVORK5CYII=" type="image/x-icon"/></head><body><?php
	}
?><a id="wss_spot"></a><div class="wss_a"><div class="wss_q"><span class="wss_q0"><?php
	echo _WEBO_GENERAL_LOADING;
?></span> <span id="wss_q1"></span><span id="wss_q2"></span></div><a id="wss_dashboard" title="WEBO Site SpeedUp" href="<?php
	if (in_array($page, array('install_enter_password', 'install_set_password'))) {
?>http://www.webogroup.com/home/site-speedup/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer<?php
	} else {
?>#wss_dashboard<?php
	}
?>"></a><span class="wss_c"><?php
	if (empty($page) || (!empty($page) && $page != 'install_enter_password')) {
		echo $version;
	}
?></span><ul class="wss_d" title="<?php
	echo _WEBO_GENERAL_LANGUAGE;
?>"><?php
	switch ($language) {
		case 'en':
?><li class="wss_e wss_e1"><a class="wss_g" href="javascript:_.t('ru')"><?php
			echo _WEBO_GENERAL_ru;
?></a></li><?php
			break;
		default:
?><li class="wss_e wss_e1"><a class="wss_g" href="javascript:_.t('en')"><?php
			echo _WEBO_GENERAL_en;
?></a></li><?php
			break;
	}
?><li class="wss_e wss_f"><a class="wss_g" href="javascript:if(_.lang){_('.wss_d1')[0].className='wss_d';_.lang=0}else{_('.wss_d')[0].className='wss_d wss_d1';_.lang=1}void(0)"><?php
	echo constant('_WEBO_GENERAL_' . $language);
	?></a></li><?php
	$i = 0;
	foreach (array('de', 'ru', 'es', 'ua', 'fr', 'it', 'ur') as $lang) {
		if ($lang != $language && ($language != 'en' || $lang != 'ru')) {
?><li class="wss_e<?php
			echo $i == 5 ? ' wss_e2' : '';
?>"><a class="wss_g" href="javascript:_.t('<?php
			echo $lang;
?>')"><?php
			echo constant('_WEBO_GENERAL_' . $lang);
?></a></li><?php
			$i++;
		}
	}
?></ul><?php
	if (empty($page) || (!empty($page) && $page != 'install_enter_password')) {
?><a id="wss_promo" href="#wss_promo" title="<?php
		echo _WEBO_GENERAL_BUY;
?> WEBO Site SpeedUp <?php
		echo _WEBO_GENERAL_PREMIUM;
?> <?php
		echo _WEBO_GENERAL_EDITION
?>"<?php
		echo $premium ? ' style="display:none"' : '';
?>><?php
		echo _WEBO_GENERAL_BUY;
?><span class="wss_o"><?php
		echo _WEBO_GENERAL_PREMIUM;
?></span><span class="wss_p"><?php
		echo _WEBO_GENERAL_EDITION
?></span><span class="wss_n"></span></a><a id="wss_bal" class="wssN5<?php
		if (round($premium) != 10) {
?> wssA0<?php
		}
?>" href="#wss_balance"><span class="wssN2"><span class="wssJ"><?php
		echo _WEBO_SAAS_BALANCE;
?>:</span><span id="wss_balance"><?php
		echo '???';
?></span></span></a><?php
	}
?><div id="wss_content"><?php
}

switch ($page) {
	case 'install_enter_password':
		?><div class="wssN0 wssN1"><form action="index.php" method="post" class="wssC wssN2"><h1 class="wssA"><?php  echo _WEBO_LOGIN_TITLE; ?></h1><dl class="wssD"><dt class="wssD1"><label for="wss_password" class="wssE"><?php   echo _WEBO_LOGIN_PASSWORD; ?>:</label></dt><dd class="wssD2"><input type="password" id="wss_password" name="wss_password" title="<?php  echo _WEBO_LOGIN_ENTERPASSWORD; ?>" class="wssF"/></dd></dl><p class="wssD"><input type="submit" value="<?php  echo _WEBO_LOGIN_LOGIN; ?>" class="wssG wssG1"/><input type="hidden" name="wss_page" value="install_dashboard"/><input type="hidden" name="wss_Submit" value="1"/></p></form></div> <?php
		break;
	case 'install_set_password':
		?><h1 class="wssA wssA2"><?php  echo _WEBO_NEW_TITLE; ?></h1><div class="wssK<?php  if (empty($error) || !count($error)) { ?> wssA0<?php  } ?>"><div class="wssK1"><h2 class="wssB"><?php  echo _WEBO_ERROR_TITLE; ?></h2><ul class="wssL"><li class="wssL1<?php  if (empty($error[0])) { ?> wssA0<?php  } ?>" id="wss_error0"><?php  echo _WEBO_SPLASH3_CONFIGERROR; ?></li><li class="wssL1<?php  if (empty($error[1])) { ?> wssA0<?php  } ?>" id="wss_error1"><?php  echo _WEBO_LOGIN_ENTERPASSWORD; ?></li><li class="wssL1<?php  if (empty($error[2])) { ?> wssA0<?php  } ?>" id="wss_error2"><?php  echo _WEBO_LOGIN_ENTERPASSWORDCONFIRM; ?></li><li class="wssL1<?php  if (empty($error[3])) { ?> wssA0<?php  } ?>" id="wss_error3"><?php  echo _WEBO_LOGIN_ENTEREMAIL; ?></li><li class="wssL1<?php  if (empty($error[4])) { ?> wssA0<?php  } ?>" id="wss_error4"><?php  echo _WEBO_LOGIN_LICENSEAGREEMENT . _WEBO_LOGIN_LICENSEAGREEMENT2; ?></li>><li class="wssL1<?php  if (empty($error[10])) { ?> wssA0<?php  } ?>" id="wss_error10"><?php  echo _WEBO_SPLASH3_HTACCESS_CHMOD3 . ' ' . _WEBO_SPLASH3_HTACCESS_CHMOD4; ?></li></ul></div></div><h2 class="wssB"><?php  echo _WEBO_NEW_ENTER; ?></h2><form action="index.php" method="post" class="wssC wssC1"><dl class="wssD"><dt class="wssD1"><label for="wss_username" class="wssE"><?php   echo _WEBO_LOGIN_USERNAME; ?>:</label></dt><dd class="wssD2"><input id="wss_username" name="wss_username" title="<?php  echo _WEBO_LOGIN_ENTERLOGIN; ?>" class="wssF" value="<?php  if (empty($submit) || !empty($username)) {  echo htmlspecialchars($username); }?>"/></dd><dt class="wssD1"><label for="wss_password" class="wssE"><?php   echo _WEBO_LOGIN_PASSWORD; ?>:</label></dt><dd class="wssD2<?php  if (!empty($error[1]) || !empty($error[2])) {  ?> wssD8<?php  } ?>"><span class="wssD3">*</span><input type="password" id="wss_password" name="wss_password" title="<?php  echo _WEBO_LOGIN_ENTERPASSWORD; ?>" class="wssF" value="<?php  if (empty($submit) || !empty($password)) {  echo htmlspecialchars($password);  } ?>"/></dd><dt class="wssD1"><label for="wss_confirm" class="wssE"><?php   echo _WEBO_LOGIN_PASSWORD_CONFIRM; ?>:</label></dt><dd class="wssD2<?php  if (!empty($error[2])) { ?> wssD8<?php  } ?>"><span class="wssD3">*</span><input type="password" id="wss_confirm" name="wss_confirm" title="<?php  echo _WEBO_PASSWORD_ENTERPASSWORDCONFIRM; ?>" class="wssF" value="<?php  if (empty($submit) || !empty($confirm)) {  echo htmlspecialchars($confirm);  } ?>"/></dd><dt class="wssD1"><label for="wss_license" class="wssE"><?php   echo _WEBO_LOGIN_LICENSE; ?>:</label></dt><dd class="wssD2"><input id="wss_license" name="wss_license" title="<?php  echo _WEBO_LOGIN_ENTERLICENSE; ?>" class="wssF" value="<?php  if (empty($submit) || !empty($license)) {  echo htmlspecialchars($license);  } ?>" size="29" maxlength="29"/></dd><dt class="wssD1"><label for="wss_email" class="wssE"><?php   echo _WEBO_LOGIN_EMAIL; ?>:</label></dt><dd class="wssD2<?php  if (!empty($error[3])) { ?> wssD8<?php  } ?>"><span class="wssD3">*</span><input id="wss_email" name="wss_email" title="<?php  echo _WEBO_LOGIN_ENTEREMAIL;   echo _WEBO_LOGIN_EMAILNOTICE; ?>" class="wssF" value="<?php  if (empty($submit) || !empty($email)) {  echo htmlspecialchars($email);  } ?>"/><span class="wssD4"><?php  echo _WEBO_LOGIN_EMAILNOTICE; ?></span></dd><dt class="wssD5"><label for="wss_confirmagreement" class="wssE"><?php   echo _WEBO_LOGIN_LICENSEAGREEMENT . ' <a href="javascript:document.getElementById(\'wss_eula2\').style.display=\'block\';void(0)">' . _WEBO_LOGIN_LICENSEAGREEMENT2.'</a> ' . _WEBO_LOGIN_LICENSEAGREEMENT3; ?>:</label></dt><dd class="wssD6 wssD7<?php  if (!empty($error[4])) { ?> wssD8<?php  } ?>"><span class="wssD3">*</span><input type="checkbox" id="wss_confirmagreement" name="wss_confirmagreement" title="<?php  echo _WEBO_LOGIN_CONFIRMLICENSEAGREEMENT; ?>"<?php  if (empty($submit) || !empty($confirmagreement)) { ?> checked="checked"<?php  } ?>/></dd><dd class="wssD2 wssA0" id="wss_eula2"><pre class="wssF wssF1"><?php  if ($language == 'ru') {  echo htmlspecialchars(@file_get_contents('LICENSE.utf8.ru.txt'));  } else {  echo htmlspecialchars(@file_get_contents('LICENSE.txt'));  } ?></pre></dd></dl><p class="wssD"><input type="submit" value="<?php  echo _WEBO_SPLASH1_NEXT; ?>" class="wssG"/><input type="hidden" name="wss_page" value="install_set_password"/><input type="hidden" name="wss_Submit" value="1"/></p></form><div class="wssH wssH2"><div class="wssRB"><span class="wssRB1"><span class="wssRB2">&bull;</span></span><span class="wssRB3"><span class="wssRB4">&bull;</span></span></div><div class="wssH1"><h2 class="wssB"><?php  echo _WEBO_NEW_TITLE; ?></h2><?php  echo _WEBO_NEW_PROTECT; ?></div><div class="wssRB"><span class="wssRB5"><span class="wssRB6">&bull;</span></span><span class="wssRB7"><span class="wssRB8">&bull;</span></span></div></div> <?php
		break;
	default :
		require($page.".php");
		break;
}

if (!$ajax) {
?></div><p class="wss_y"><?php
	if (empty($page) || ($page != 'install_enter_password' && $page != 'install_set_password')) {
?><a class="wss_z wss_z1" href="javascript:_('.wss_a')[0].className='wss_a wss_a1';void(0)"><?php
		echo _WEBO_HELP_HELP;
?></a><?php
	}
?><a href="http://www.webogroup.com/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer" class="wss_x" title="WEBO Software"></a>&copy; 2009-<?php
	echo date("Y");
?> <a href="<?php
	if (in_array($page, array('install_enter_password', 'install_set_password'))) {
?>http://www.webogroup.com/home/site-speedup/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=web.optimizer<?php
	} else {
?>#wss_about<?php
	}
?>" class="wss_z" title="About WEBO Site SpeedUp">WEBO Site SpeedUp</a><?php
	if (empty($page) || (!empty($page) && $page != 'install_enter_password' && $page != 'install_set_password')) {
?> | <a class="wss_w" href="#wss_promo" title="WEBO Site SpeedUp"><span class="wss_w1<?php
		if (!$premium) {
?> wss_w0<?php
		}
?>"><?php
		echo _WEBO_SPLASH2_COMPARISON_DEMO;
?> <?php
		echo  _WEBO_SPLASH2_COMPARISON_VERSION;
?> </span><span class="wss_w2<?php
		if ($premium == 1) {
?> wss_w0<?php
		}
?>"> <?php
		echo _WEBO_SPLASH2_COMPARISON_LITE;
?> <?php
		echo  _WEBO_SPLASH2_COMPARISON_VERSION;
?> </span><span class="wss_w3<?php
		if ($premium == 2) {
?> wss_w0<?php
		}
?>"> <?php
		echo _WEBO_SPLASH2_COMPARISON_FULL;
?> <?php
		echo  _WEBO_SPLASH2_COMPARISON_VERSION;
?></span><span class="wss_w4<?php
		if ($premium == 3) {
?> wss_w0<?php
		}
?>"> <?php
		echo _WEBO_SPLASH2_COMPARISON_CORPORATE;
?> <?php
		echo  _WEBO_SPLASH2_COMPARISON_VERSION;
?></span></a><a class="wss_w" href="#wss_balance" title="WEBO Site SpeedUp"><span class="wss_w5<?php
		if ($premium == 10) {
?> wss_w0<?php
		}
?>"> <?php
		echo _WEBO_SPLASH2_COMPARISON_SAAS;
?> <?php
		echo  _WEBO_SPLASH2_COMPARISON_VERSION;
?></span></a><?php
	}
?></p><?php
	if (empty($page) || !in_array($page, array('install_enter_password','install_set_password'))) {
		$screens_lang = (in_array($language, array('ru', 'ua')) ? 'ru' : 'en');
?><div id="wss_help"><div class="wss_r"><a href="javascript:_('.wss_a')[0].className='wss_a';_.v('wss_welcome=1');void(0)" class="wssJ20" title="<?php
		echo _WEBO_HELP_MINIMIZE;
?>"></a><h1 class="wssB"><?php
		echo _WEBO_HELP_WELCOME;
?>!</h1><h2 class="wssB1"><?php
		echo _WEBO_HELP_FIRSTTIME;
?></h2><ol class="wssO7"><li class="wssO8 wssO24"><a class="wssO22" href="#wss_wizard"><span class="wssO29<?php
		echo $screens_lang;
?> wssO25" title="<?php
		echo _WEBO_WIZARD_TITLE;
?>"></span></a><?php
		echo _WEBO_HELP_TUNING;
?></li><li class="wssO8 wssO21"><a class="wssO22" href="#wss_account"><span class="wssO29<?php
		echo $screens_lang;
?> wssO23" title="<?php
		echo _WEBO_DASHBOARD_ACCOUNT;
?>"></span></a><?php
		echo _WEBO_HELP_LICENSEKEY;
?> <a href="#wss_account" class="wssJ"><?php
		echo _WEBO_DASHBOARD_ACCOUNT;
?></a> <?php
		echo _WEBO_HELP_LICENSEKEY2;
?></li><li class="wssO8 wssO26"><a class="wssO22" href="#wss_dashboard"><span class="wssO29<?php
		echo $screens_lang;
?> wssO27" title="<?php
		echo _WEBO_SPLASH2_CONTROLPANEL;
?>"></span></a><?php
		echo _WEBO_HELP_CONTROLPANEL;
?></li></ol><div class="wss_r1"><div class="wssN wssN4"><h3 class="wssB2"><?php
		echo _WEBO_ABOUT_SUPPORT;
?></h3><ul class="wssO7"><li class="wssO8"><a href="http://www.webogroup.com/" class="wssJ wssJ0"><?php
		echo _WEBO_HELP_LINK1;
?></a></li><li class="wssO8"><a href="http://code.google.com/p/web-optimizator/w/list" class="wssJ wssJ0"><?php
		echo _WEBO_HELP_LINK2;
?></a></li><li class="wssO8"><a href="http://code.google.com/p/web-optimizator/issues/list" class="wssJ wssJ0"><?php
		echo _WEBO_HELP_LINK3;
?></a></li><li class="wssO8"><a href="http://www.webogroup.com/home/support/" class="wssJ wssJ0"><?php
		echo _WEBO_HELP_LINK4;
?></a></li><li class="wssO8"><a href="http://www.webogroup.com/store/" class="wssJ wssJ0"><?php
		echo _WEBO_HELP_LINK5;
?></a></li></ul></div><div class="wssN wssN4"><h3 class="wssB2"><?php
		echo _WEBO_HELP_MEDIA;
?></h3><a href="http://www.webogroup.com/home/site-speedup-joomla/video/" class="wssO29 wssO30"><span class="wssO29<?php
		echo $screens_lang;
?> wssO31"></span></a></div><div class="wssN"><h3 class="wssB2"><?php
		echo _WEBO_HELP_FEATURES;
?></h3><ul class="wssO7"><?php
		echo _WEBO_HELP_FEATURES_LIST;
?></ul><p class="wssI"><a class="wssJ" href="http://www.webogroup.com/home/site-speedup/features/"><?php
		echo _WEBO_HELP_FEATURES_ALL;
?></a> / <a href="http://blog.webogroup.com/" class="wssJ"><?php
		echo _WEBO_HELP_FEATURES_BLOG;
?></a></p><p class="wssI"><a href="javascript:_('.wss_a')[0].className='wss_a';_.v('wss_welcome=1');void(0)" class="wssJ wssJ5"><span class="wssJ6"></span><?php
		echo _WEBO_HELP_CLOSE;
?></a></p></div></div></div></div><?php
	}
?></div><script type="text/javascript"><?php
	if ($premium == 10) {
?>setTimeout(function(){if(typeof _!=='undefined'){var a=document,b=a.createElement('script');b.type='text/javascript';b.src='http://webo.name/license/?key=<?php
		echo $license;
?>&status=1';a.body.appendChild(b)}else{setTimeout(arguments.callee,10)}},10);<?php
	}
?>wss_premium=<?php
	echo (empty($page) || (!empty($page) && $page != 'install_enter_password' && $page != 'install_set_password')) ? round($premium) : 10;
?></script><?php
	if (!$skip_render) {
?><script type="text/javascript" src="http://www.google.com/jsapi?key=ABQIAAAAcDjjgL6gyYUwSrkesv6c7RRPj_C4VnSBVCqcbcH6fyxpcL8EhxSiDicBRQUIZJ32TB5Qr_cb3UjZXg"></script><script type="text/javascript" src="libs/js/yass.loadbar.js?<?php
		if (empty($page) || (!empty($page) && $page != 'install_enter_password')) {
			echo $version;
		}
?>"></script><script type="text/javascript">
if(_('#wss_feed')[0]&&typeof google!=='undefined'){(function(){
google.load("feeds","1");function a(){
var f=new google.feeds.Feed("http://feeds.feedburner.com/WebOptimizerBlog");
f.load(function(r){if(!r.error){_.feeds[0]=r.feed}})}google.setOnLoadCallback(a)}());
(function(){google.load("feeds","1");function a(){
var f=new google.feeds.Feed("http://sitespeedupupdates.blogspot.com/feeds/posts/default?alt=rss");
f.load(function(r){if(!r.error){_.feeds[1]=r.feed}})};
google.setOnLoadCallback(a)}())}</script></body></html><?php
	}
}
?>
