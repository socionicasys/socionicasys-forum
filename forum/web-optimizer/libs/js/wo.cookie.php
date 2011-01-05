<?php
/**
 * File from Web Optimizer, Nikolay Matsievsky (http://webo.in/)
 * Sends compressed JavaScript to set cookie about gzip possibility.
 * Helps when browser supports gzip but doesn't send headers (due to firewall).
 * Inspired after Velocity'2009 and Google presentation about gzip.
 *
 **/
	$expiration_time = time() + 6000000 * 60;
	header("Content-Type: application/javascript");
	header("Content-Encoding: gzip");
/* send headers to cache script for 10 years */
	header("Cache-Control: private, max-age=315360000");
	header("Expires: " . gmdate("D, d M Y H:i:s", $expiration_time) . " GMT");
	header("ETag: \"wo-cookie-check-gzip\"");
/* set cookie that that we checked for gzip */
	setcookie('_wo_gzip_checked', 1, $expiration_time, '/', $_SERVER['HTTP_HOST']);
/* send compressed content to set cookie via JavaScript */
	echo gzencode('document.cookie="_wo_gzip=1;expires="+(new Date(new Date().getTime()+315360000000)).toGMTString()+";path=/;domain="+document.domain', 9, FORCE_GZIP);
?>