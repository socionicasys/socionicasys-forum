<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Sends cache headers among the content of requested file.
 * Also adds GZip for known types of files (that can be gzipped).
 * Resticted filename to document root only.
 * Helps when there is no mod_expires and(or) no mod_deflate on the server.
 * License agreement: http://www.webogroup.com/about/EULA.txt
 *
 **/
/* replace all urls with absolute paths */
function replace_urls ($cached, $filename, $document_root) {
	$content = @file_get_contents($filename);
	$path = str_replace($document_root, "", $filename);
	$path = substr($path, 0, strrpos($path, '/') + 1);
	preg_match_all("@url\s*\(['\"\s]*([^\"'\)]+)['\"\s]*\)@", $content, $urls, PREG_SET_ORDER);
	$replaced = array();
	foreach ($urls as $url) {
		$u = $i = $url[1];
		if (empty($replaced[$i])) {
			switch ($u{0}) {
				case '/':
					break;
				case 'h':
					if (substr($u, 0, 5) == 'http:' || substr($u, 0, 6) == 'https:') {
						break;
					}
				case 'd':
					if (substr($u, 0, 5) == 'data:') {
						break;
					}
				case 'm':
					if (substr($u, 0, 6) == 'mhtml:') {
						break;
					}
			}
			$content = str_replace($i, $u, $content);
			$replaced[$i] = 1;
		}
	}
	$fp = @fopen($cached, "wb");
	if ($fp) {
		@fwrite($fp, $content);
		@fclose($fp);
	}
	return $content;
}

/* return heximal number for a decimal one, by jbleau at gmail dot com */
function dec_to_hex ($dec) {
	$hex = Array(	0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5,
					6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 'a',
					11 => 'b', 12 => 'c', 13 => 'd', 14 => 'e',
					15 => 'f' );
	$h = '';
	do {
		$h = $hex[($dec%16)] . $h;
		$dec /= 16;
	} while ($dec >= 1);
	return $h;
}
 
/* define gzip levels if they are not defines yet */
$gzip_level_css = empty($gzip_level_css) ? 7 : $gzip_level_css;
$gzip_level_javascript = empty($gzip_level_javascript) ? 7 : $gzip_level_javascript;
$gzip_level_fonts = empty($gzip_level_fonts) ? 7 : $gzip_level_fonts;

$document_root = $_SERVER['DOCUMENT_ROOT'];
/* Avoiding problems with Denwer and others CGI */
if (empty($document_root) || !is_dir($document_root) ||
	!is_file($document_root . getenv("SCRIPT_NAME"))) {
		$document_root = substr(getenv("SCRIPT_FILENAME"), 0,
			strpos(getenv("SCRIPT_FILENAME"), getenv("SCRIPT_NAME")));
}
$document_root = str_replace("\\", "/", realpath($document_root));
/* define website root if it's empty */
$website_root = empty($website_root) ? $document_root : $website_root;
/* calculate extension */
$dot = strrpos($_SERVER['QUERY_STRING'], '.');
$extension = strtolower(substr($_SERVER['QUERY_STRING'],
	$dot + 1, strlen($_SERVER['QUERY_STRING']) - $dot));
/* gzip or not this file? */
$gzip = 0;
/* calculate MIME type */
switch ($extension) {
	case 'js':
		$extension = 'application/x-javascript';
		$gzip = 1;
		$gzip_level = $gzip_level_javascript;
		break;		
	case 'css':
		$extension = 'text/css';
		$gzip = 1;
		$gzip_level = $gzip_level_css;
		break;
	case 'jpg':
		$extension = 'jpeg';
	case 'jpeg':
	case 'bmp':
	case 'gif':
	case 'png':
		$extension = 'image/' . $extension;
		break;
	case 'ico':
		$extension = 'image/x-icon';
		$gzip = 1;
		$gzip_level = $gzip_level_css;
		break;
	case 'flv':
		$extension = 'video/x-flv';
		break;
	case 'asf':
	case 'asx':
	case 'wmv':
	case 'wma':
	case 'wax':
	case 'wmx':
	case 'wm':
		$extension = 'video/x-ms-' . $extension;
		break;
	case 'swf':
		$extension = 'application/x-shockwave-flash';
		break;
	case 'pdf':
		$extension = 'application/pdf';
		break;
	case 'doc':
		$extension = 'application/msword';
		break;
	case 'rtf':
		$extension = 'application/rtf';
		break;
	case 'xls':
		$extension = 'application/vnd.ms-excel';
		break;
	case 'ppt':
		$extension = 'application/vnd.ms-powerpoint';
		break;
	case 'otf':
		$extension = 'application/x-font-opentype';
		$gzip = 1;
		$gzip_level = $gzip_level_fonts;
		break;
	case 'ttf':
		$extension = 'application/x-font-truetype';
		$gzip = 1;
		$gzip_level = $gzip_level_fonts;
		break;
	case 'svg':
		$extension = 'image/svg+xml';
		$gzip = 1;
		$gzip_level = $gzip_level_fonts;
		break;
	case 'eot':
		$extension = 'application/vnd.ms-fontobject';
		$gzip = 1;
		$gzip_level = $gzip_level_fonts;
		break;
	case 'woff':
		$extension = 'font/woff';
		break;
/* protect all other files from viewing */
	default:
		$extension = '';
		break;
}
/* handle cases with relative document root and redirect via .htaccess */
if ($_SERVER['QUERY_STRING']{0} === '/') {
	if ($_SERVER['QUERY_STRING']{1} === '/') {
		$_SERVER['QUERY_STRING'] = 'http' .
			(empty($_SERVER['HTTPS']) ? '' : 's') .
			':' . $_SERVER['QUERY_STRING'];
	} else {
		$filename = str_replace("\\", "/",
			realpath($document_root . $_SERVER['QUERY_STRING']));
	}
} elseif (substr($_SERVER['QUERY_STRING'], 0, 4) !== 'http') {
	$filename = str_replace("\\", "/",
		realpath($website_root . '/' . $_SERVER['QUERY_STRING']));
}
/* get external files */
if (substr($_SERVER['QUERY_STRING'], 0, 4) === 'http') {
	$filename = substr(str_replace("\\", "/", dirname(__FILE__)) .
		'/' . str_replace(array('/', '?', '&'),
			array('-', '-', '-'),
			$_SERVER['QUERY_STRING']), 0, 250);
	$mtime = @filemtime($filename);
	if (!@is_file($filename) || (time() - $mtime > 86400)) {
		if (@function_exists('curl_init')) {
/* start curl */
			$ch = @curl_init($_SERVER['QUERY_STRING']);
			$fp = @fopen($filename, "w");
			if ($fp && $ch) {
				@curl_setopt($ch, CURLOPT_FILE, $fp);
				@curl_setopt($ch, CURLOPT_HEADER, 0);
				@curl_setopt($ch, CURLOPT_USERAGENT, "Web Optimizer Downloader");
				@curl_setopt($ch, CURLOPT_ENCODING, "");
				@curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				@curl_exec($ch);
				@curl_close($ch);
				@fclose($fp);
			}
/* redirect if can't download */
			if (!@is_file($filename)) {
				header("Location: " . $_SERVER['QUERY_STRING']);
				die();
			}
/* redirect if no curl */
		} else {
			header("Location: " . $_SERVER['QUERY_STRING']);
			die();
		}
	}
}
/* check if we inside document root */
if (strpos($filename, $document_root) !== false && !empty($extension)) {
/* set correct content-encoding header */
	header('Content-Type: ' . $extension);
	$mtime = empty($mtime) ? @filemtime($filename) : $mtime;
	$mtime = empty($mtime) ? $_SERVER['REQUEST_TIME'] : $mtime;
	$contents = '';
	if ($gzip) {
		$gz = $xgzip = $deflate = $xdeflate = 0;
		if (!empty($_SERVER["HTTP_ACCEPT_ENCODING"])) {
			$gz = strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false ||
				!empty($_COOKIE["_wo_gzip"]);
			$xgzip = strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "x-gzip") !== false;
			$deflate = strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "deflate") !== false;
			$xdeflate = strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "x-deflate") !== false;
		}
/* Determine used compression method */
		$encoding = $gz ? "gzip" : ($xgzip ? "x-gzip" : ($deflate ? "deflate" : ($xdeflate ? "x-deflate" : "")));
		$gzip = 0;
		if (!empty($encoding)) {
/* Check for buggy versions of Internet Explorer */
			if (!empty($_SERVER["HTTP_USER_AGENT"]) &&
				!strstr($_SERVER["HTTP_USER_AGENT"], "Opera") &&
				preg_match("/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i",
					$_SERVER["HTTP_USER_AGENT"], $matches)) {
/* IE6- can loose first 2048 bytes of gzipped content, code from Bitrix */
						if (floatval($matches[1]) < 7) {
							$contents = str_repeat(" ", 2048) . "\r\n";
						}
			}
			$gzip = 1;
		}
	}
/* calculate ETag, mimicry to default Apache settings */
	$hash = dec_to_hex(@fileinode($filename)) . '-' .
		dec_to_hex(@filesize($filename)) . '-' .
		dec_to_hex($mtime) . ($gzip ? '-gzip' : '');
	if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
			stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $hash . '"') ||
		(isset($_SERVER['HTTP_IF_MATCH']) &&
			stripslashes($_SERVER['HTTP_IF_MATCH']) == '"' . $hash . '"')) {
/* return visit and no modifications, so do not send anything */
				header('HTTP/1.0 304 Not Modified');
				header('Content-Length: 0');
				exit();
	} else {
/* set correct Content-Disposition to correct end-filename */
		$slash = strrpos($filename, '/');
		header('Content-Disposition: inline;filename=' .
				substr($filename, $slash + 1, strlen($filename) - $slash) .
			';modification-date="' .
				date("r", $mtime) .
			'";');
/* set ETag */
		header('ETag: "' . $hash . '"');
/* cache timeout */
		$timeout = 360000000;
/* set Cache-Control header */
		header('Cache-Control: public, max-age=' . $timeout);
		if (function_exists('date_default_timezone_set')) {
			@date_default_timezone_set(@date_default_timezone_get());
		}
/* define cached filename */
		$cached = $extension == 'text/css' ? dirname(__FILE__) . '/wss' . md5($filename) . '.css' : $filename;
/* set Expires header */
		header('Expires: ' .
			gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $timeout). ' GMT');
/* create gzipped file */
		if ($gzip) {
/* try to get gzipped content from file */
			$extension = strpos($encoding, "gzip") !== false ? 'gz' : 'df';
			$compressed = $cached . '.' . $extension;
/* check file's existence and its mtime */
			if (!@is_file($compressed) || @filemtime($compressed) !== $mtime || !($contents = @file_get_contents($compressed))) {
				$content = @file_get_contents($cached);
				if (!empty($content)) {
/* Make compressed contents */
					if ($extension === 'gz') {
						$contents = @gzencode($content, $gzip_level, FORCE_GZIP);
					} else {
						$contents = @gzdeflate($content, $gzip_level);
					}
					if (!empty($contents)) {
						$fp = @fopen($compressed, "wb");
						if ($fp) {
							@fwrite($fp, $contents);
							@fclose($fp);
/* set the same mtime for compressed file as for the main one */
							@touch($compressed, $mtime);
						}
					} else {
						$contents = $content;
						$gzip = 0;
					}
/* create CSS file in cache with rewritten urls */
				} elseif ($cached != $filename) {
					$contents = replace_urls($cached, $filename, $document_root);
					$gzip = 0;
				} else {
					$contents = $content;
				}
			}
			if ($gzip) {
				header('Content-Encoding: ' . $encoding);
			}
			header('Content-Length: ' . strlen($contents));
		} else {
			$contents = @file_get_contents($cached);
			if (empty($contents) && $cached != $filename) {
				$contents = replace_urls($cached, $filename, $document_root);
			}
		}
/* finally output content */
		echo $contents;
	}
/* else just redirect to resource, maybe server can give it */
} else {
	header("Location: " . $_SERVER['QUERY_STRING']);
	die();
}

?>