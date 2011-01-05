<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Outputs external achievements page
 *
 **/
?><!DOCTYPE html><html><head><title><?php
	echo _WEBO_DASHBOARD_AWARDS;
?> by WEBO Site SpeedUp</title><meta http-equiv="content-type" content="text/html;charset=utf-8"/><link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACEklEQVQ4T3WST0iTcRjHf3nwtNOC/twW6kGvMXArYd1EN9pi4C5BJHRYSO80oqRAGsgGleDy1Nb8c8j0IpMVYjDqEhP6g77uUnpZHhL0IKRbyz3t8wuHbe4LDzw838/nfbcxpWqSzea8y8vZz/Pz7wrT0+kyw86NrpavJpNZt6RSH8xY7LWMj8+eOHQwsHVyMrm4Mzr6UqLRSVla+ihbW9tSLP7Ww86NDiaZTO3+95CpqUVzeHhCIpFJDTcKHQwsjpYXFt73GMZTGRwck3z+Z0P5KDCwOLgqFpv91N//WGZm0nVwqfRHDg/LdXdYHFxlGE8KgcAD2dj4IWtrm6JOuSrf8a0G4/G0HpJIvNEdDCwOrvL775XdbkMODgoavNw1IM5Lt/XbbRcCetg7HUHdEVgcXNXdPVB2uW7J/v6/B8zNZfSbjNBzsZ726GHnRkdgcXCVz3e3YLdfl1xuU5fFYknOnPVqIRp9pYedGx2BxcFVwWDkS3u7X8LhePVHevgoIefOX6t81KIedm5HgcXBVeHwix6bzS1tbT4xze8ayOe3qz8eYedGYFpargoOrv4v9PXdN63WK9LR4ZfV1W/SKHQwsDjVf+LIyITF6by509zcKRZLl4RCz2RlZV329n7pYedGB+Nw3NjFUcfDobf3jtnUZBelLp44dDB18vEMDY15PZ7Q19ZWb6HyxjLDzo2ulv8LN6Bqnkiu8fYAAAAASUVORK5CYII=" type="image/x-icon"/><style type="text/css">body,ul,h1,h2{margin:0;padding:0}img{border:0}ol,ul{list-style:none}li{margin:.5em 0;padding:0}p{margin:.25em 0;padding:0}a:link{color:#34678c}a:visited{color:#000140}a:hover{color:#000180}body{font:14px/1.25 "Trebuchet MS",Arial,Tahoma,sans-serif}body{min-width:870px;background:#fff}.wg-content{height:857px;background:#ff8200 url(webo-site-speedup.back.jpg) no-repeat center top}.wg-title{padding:80px 0 20px;color:#fff;font-size:45px;text-align:center}.wg-subtitle{color:#ff7f00;font-size:24px;margin:0 0 .5em}.wg-content-text{height:320px;margin:10px auto;width:840px;background:#fff}.wg-footer{padding:20px 0;position:relative;font-size:16px}.wg-teeth{display:block;position:absolute;height:9px;width:100%;top:-9px;left:0;background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAJCAYAAAALpr0TAAAATklEQVQYGY3BTRFAUAAGwG8EEEGNF0YVI4wwasjg7LDGifG/m5ygRZs3aDBjRpM7qDDajahyhs5VnyMULK4WlGxQY/JsQh0Mvg3xU/y0AgXf++/+7UXZAAAAAElFTkSuQmCC);background-repeat:repeat-x}.wg-footer-columns{overflow:hidden;margin:0 auto;width:870px}.wg-footer-column-left{float:left;width:420px;margin:0 0 20px}.wg-footer-column-right{float:right;width:420px;margin:0 0 20px}.wg-footer-copyrights{clear:both;font-size:12px;color:#999}.wg-footer-logo{display:inline-block;background-image:url(data:image/png;base64,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); background-repeat:no-repeat;width:88px;height:34px;vertical-align:text-bottom;float:left;margin-right:20px;float:left;margin-top:-2px}.wssO66{background:url(webo-site-speedup.rocket.png) -65px -362px no-repeat;display:block;top:9px;font:700 13px/16px"Trebuchet MS",Arial,Tahoma,Sans-serif;position:relative;height:452px;margin:0 auto;width:515px;z-index:2}.wssO67{cursor:pointer;cursor:hand;display:block;list-style:none!important;position:absolute;left:273px;height:100px;padding:33px 0 0 210px;top:122px;width:200px}.wssO67:hover{background:url(webo-site-speedup.rocket.png) -397px -814px no-repeat}.wssO32{cursor:pointer;cursor:hand;display:block;list-style:none!important;position:absolute;height:203px;left:-43px;top:22px;width:338px}.wssO32:hover{background:url(webo-site-speedup.rocket.png) 212px -837px no-repeat}.wssO33{cursor:pointer;cursor:hand;display:block;list-style:none!important;position:absolute;height:100px;left:214px;padding:141px 0 0 199px;top:223px;width:10px}.wssO33:hover{background:url(webo-site-speedup.rocket.png) -124px -811px no-repeat}.wssO34{cursor:pointer;cursor:hand;display:block;list-style:none!important;position:absolute;height:200px;left:-158px;padding-top:106px;top:252px;width:450px}.wssO34:hover{background:url(webo-site-speedup.rocket.png) -228px -1032px no-repeat}.wssO35{cursor:pointer;cursor:hand;display:block;list-style:none!important;position:absolute;height:200px;left:-219px;padding-top:20px;top:132px;width:450px}.wssO35:hover{background:url(webo-site-speedup.rocket.png) 210px -1054px no-repeat}.wssO36{cursor:pointer;cursor:hand;display:block;list-style:none!important;position:absolute;width:117px;height:117px;background:url(webo-site-speedup.rocket.png) no-repeat}.wssO37{background-position:0 0;left:80px;top:0}.wssO38{background-position:0 -122px;left:80px;top:0}.wssO39{background-position:0 -244px;left:80px;top:0}.wssO40{background-position:-122px 0;left:225px;top:-29px}.wssO41{background-position:-122px -122px;left:225px;top:-29px}.wssO42{background-position:-122px -244px;left:225px;top:-29px}.wssO43{background-position:-244px 0;left:69px;top:107px}.wssO44{background-position:-244px -122px;left:69px;top:107px}.wssO45{background-position:-244px -244px;left:69px;top:107px}.wssO46{background-position:-366px 0;left:224px;top:75px}.wssO47{background-position:-366px -122px;left:224px;top:75px}.wssO48{background-position:-366px -244px;left:224px;top:75px}.wssO49{background-position:-488px 0;left:222px;top:-11px}.wssO50{background-position:-488px -122px;left:222px;top:-11px}.wssO51{background-position:-488px -244px;left:222px;top:-11px}.wssO52{color:#ff4000;display:block;font-size:14px}.wssO61{background:#f7f4e9;display:block;position:absolute;right:0;top:260px;width:260px}#wss_awrd{display:block;height:161px;left:-20px;margin:182px auto -343px;position:relative;width:161px}.wssO71{font-weight:400;background:#eef;display:none;margin:-75px 0 0 -20px;padding:80px 20px 15px;text-align:left;width:200px;z-index:101;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px}.wssO67:hover .wssO71,.wssO32:hover .wssO71,.wssO33:hover .wssO71,.wssO34:hover .wssO71,.wssO35:hover .wssO71{display:block;position:relative;top:0;left:0}.wssO72{display:block;position:relative;width:180px;z-index:102}.wssO32 .wssO72,.wssO34 .wssO72,.wssO35 .wssO72{padding-left:20px}.wssO32 .wssO71{z-index:110}.wssO32 .wssO72{text-align:right;z-index:111}.wssO35 .wssO71{z-index:108}.wssO34 .wssO72{text-align:right}.wssO35 .wssO72{text-align:right;z-index:109}.wssO67 .wssO71{z-index:106}.wssO67 .wssO72{z-index:107}.wssO99{margin:76px 5% 0}</style><!--[if lt IE 8]><link rel="stylesheet" type="text/css" href="webo-site-speedup.css"/><![endif]--></head><body><div class="wg-content"> <h1 class="wg-title"><?php
	echo _WEBO_AWARDS_RESULT;
?></h1><div class="wg-content-text"><img src="<?php
	if ($local) {
?>webo-site-speedup161.png<?php
	} else {
?>http://webo.in/rocket/?size=161&amp;top=<?php
		echo $level1;
?>&amp;middle=<?php
		echo $level2;
?>&amp;bottom=<?php
		echo $level3;
?>&amp;tail=<?php
		echo $level4;
?>&amp;circle=<?php
		echo $level5;
	}
?>" id="wss_awrd" alt="<?php
	echo _WEBO_DASHBOARD_AWARDS_TITLE;
?> WEBO Site SpeedUp" title="<?php
	echo _WEBO_DASHBOARD_AWARDS_TITLE;
?> WEBO Site SpeedUp"/><ul class="wssO66"><li class="wssO67"><?php
	if ($level1) {
?><span class="wssO36 wssO<?php
		echo 36 + $level1;
?>"></span><?php
	}
?><span class="wssO72"><?php
	echo _WEBO_AWARDS_TOP;
	if ($level1) {
?><span class="wssO52"><?php
		echo constant('_WEBO_AWARDS_LEVEL' . $level1);
?></span><?php
	}
?></span><div class="wssO71"><?php
	echo _WEBO_AWARDS_TOP_INFO;
?></div></li><li class="wssO34"><?php
	if ($level4) {
?><span class="wssO36 wssO<?php
		echo 45 + $level4;
?>"></span><?php
	}
?><span class="wssO72"><?php
	echo _WEBO_AWARDS_TAIL;
	if ($level4) {
?><span class="wssO52"><?php
		echo constant('_WEBO_AWARDS_LEVEL' . $level4);
?></span><?php
	}
?></span><div class="wssO71"><?php
	echo _WEBO_AWARDS_TAIL_INFO;
?></div></li><li class="wssO35"><?php
	if ($level5) {
?><span class="wssO36 wssO<?php
		echo 48 + $level5;
?>"></span><?php
	}
?><span class="wssO72"><?php
	echo _WEBO_AWARDS_CIRCLE;
	if ($level5) {
?><span class="wssO52"><?php
		echo constant('_WEBO_AWARDS_LEVEL' . $level5);
?></span><?php
	}
?></span><div class="wssO71"><?php
	echo _WEBO_AWARDS_CIRCLE_INFO;
?></div></li><li class="wssO32"><?php
	if ($level2) {
?><span class="wssO36 wssO<?php
		echo 39 + $level2;
?>"></span><?php
	}
?><span class="wssO72"><?php
	echo _WEBO_AWARDS_MIDDLE;
	if ($level2) {
?><span class="wssO52"><?php
		echo constant('_WEBO_AWARDS_LEVEL' . $level2);
?></span><?php
	}
?></span><div class="wssO71"><?php
	echo _WEBO_AWARDS_MIDDLE_INFO;
?></div></li><li class="wssO33"><?php
	if ($level3) {
?><span class="wssO36 wssO<?php
		echo 42 + $level3;
?>"></span><?php
	}
?><span class="wssO72"><?php
	echo _WEBO_AWARDS_BOTTOM;
	if ($level3) {
?><span class="wssO52"><?php
		echo constant('_WEBO_AWARDS_LEVEL' . $level3);
?></span><?php
	}
?></span><div class="wssO71"><?php
	echo _WEBO_AWARDS_BOTTOM_INFO;
?></div></li></ul><img src="webonautes.png" alt="Webonautes" class="wssO99"/></div></div><div class="wg-footer"><span class="wg-teeth"></span> <div class="wg-footer-columns"><div class="wg-footer-column-left"><h2 class="wg-subtitle"><?php
	echo _WEBO_AWARDS_SENSE;
?>?</h2><p><?php 
	echo _WEBO_AWARDS_TEXT1;
?> <span title="<?php
	echo $host;	
?>"><?php
	echo substr($host, 0, 33);
?></span> <?php
	echo _WEBO_AWARDS_TEXT2;
?></p><p><?php
	echo _WEBO_AWARDS_TEXT3;
?></p></div><div class="wg-footer-column-right"><h2 class="wg-subtitle"><?php
	echo _WEBO_AWARDS_OWN;
?></h2><p><?php
	echo _WEBO_AWARDS_TEXT4;
?></p><p><?php
	echo _WEBO_AWARDS_TEXT5;
?></p><p><?php
	echo _WEBO_AWARDS_TEXT6;
?></p></div><div class="wg-footer-copyrights"><p><a href="http://www.webogroup.com/?utm_source=product&amp;utm_medium=internal&amp;utm_campaign=webo.awards"><span class="wg-footer-logo"></span></a> &copy; <?php
	echo date("Y");
?> WEBO Software<br/><?php
	echo _WEBO_AWARDS_RIGHTS;
?>.</p></div></div></div></body></html>