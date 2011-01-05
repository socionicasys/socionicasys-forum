<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Gzips and minifies the JavaScript and CSS within the head tags of a page.
 * Can also gzip and minify the page itself
 * and 100+ other cool web performance optimization techniques
 * Based on Web Optimizer, which was based on PHP Speedy
 *
 **/
class web_optimizer {

	/**
	* Constructor
	* Sets the options and defines the gzip headers
	**/
	function web_optimizer ($options = false) {
/* initialize chained optimization */
		if(!empty($_GET['web_optimizer_disabled']))
		{
			$this->options['active'] = 0;
			return;
		}
		$this->web_optimizer_stage = round(empty($_GET['web_optimizer_stage']) ? 0 : $_GET['web_optimizer_stage']);
		$this->debug_mode = empty($_GET['web_optimizer_debug']) && empty($_COOKIE['web_optimizer_debug']) ? 0 : 1;
/* get chained optimization params */
		if (!empty($this->web_optimizer_stage)) {
			$this->username = htmlspecialchars(empty($_GET['username']) ? '' :
				$_GET['username']);
			$this->password = htmlspecialchars(empty($_GET['password']) ? '' :
				$_GET['password']);
			$this->auto_rewrite = round(empty($_GET['auto_rewrite']) ? '' :
				$_GET['auto_rewrite']);
			$this->chained_redirect = 'optimizing.php';
			$this->cache_version = round(empty($_GET['cache_version']) ? '' :
				$_GET['cache_version']);
/* get major stage number, all stages:
 -1		- system, envelope all <script> to try-catch-document.write
 0-9	- inilialization, starts in administrative interface
 10-13	- JS file generation, 1st major stage (common browsers)
 14-19	- CSS Sprites / data:URI generation, 1st major stage
 20-24	- CSS file generation + page parsing, 1st major stage
 25-28	- JS file generation, 2nd major stage (IE 6.0)
 29-34	- CSS Sprites / mhtml generation, 2nd major stage
 35-39	- CSS file generation + page parsing, 2nd major stage
 40-43	- JS file generation, 3rd major stage (IE 7.0)
 44-49	- CSS Sprites / mhtml generation, 2nd major stage
 50-54	- CSS file generation + page parsing, 2nd major stage
 55-58	- JS file generation, 4th major stage (IE 8.0)
 59-64	- CSS Sprites / data:URI generation, 4th major stage
 65-69	- CSS file generation + page parsing, 4th major stage
 70-73	- JS file generation, 5th major stage (IE 7.0 @ Vista)
 74-79	- CSS Sprites generation, 5th major stage
 80-84	- CSS file generation + page parsing, 5th major stage
*/
			$this->cache_stage = floor(($this->web_optimizer_stage - 10) / 15);
		}
/* allow merging of other classes with this one */
		foreach ($options as $key => $value) {
			$this->$key = $value;
		}
		$this->options['active'] = $this->debug_mode ? 1 : $this->options['active'];
/* disable any actions if not active */
		if (empty($this->options['active'])) {
			return;
		}
/* define head of the webpage for scripts / styles */
		$this->head = '';
/* remember current time */
		$this->time = empty($_SERVER['REQUEST_TIME']) ? time() : $_SERVER['REQUEST_TIME'];
		$this->host = $_SERVER['HTTP_HOST'];
		if (strpos($_SERVER['HTTP_HOST'], "www.") !== false ||
			strpos($_SERVER['HTTP_HOST'], "WWW.") !== false) {
				$this->host = substr($this->host, 4);
		}
/* define PHP version */
		$this->php = $this->options['php'];
/* skip buffering (need for integration as plugin) */
		$this->buffered = $this->options['buffered'];
/* Sets User Agent to differ IE from non-IE */
		$this->ua = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
/* HTTPS or not ? */
		$this->https = empty($_SERVER['HTTPS']) ? '' : 's';
/* Set options */
		$this->set_options();
/* Include base plugin class */
		if (is_array($this->options['plugins'])) {
			include_once($this->options['css']['installdir'] . 'libs/php/class.plugin.php');
		}
/* Remember current page encoding */
		$this->encoding = '';
/* Define the gzip headers */
		$this->set_gzip_headers();
/* Deal with flushed content or not? */
		$this->flushed = false;
		$excluded_html_pages = '';
		$included_user_agents = '';
		$retricted_cookie = 0;
		if (!empty($this->options['page']['cache'])) {
			$this->start_cache_engine();
/* HTML cache ? */
			if (!empty($this->options['page']['cache_ignore']) ||
				!empty($this->options['restricted'])) {
				$list = (empty($this->options['page']['cache_ignore']) ? '' : $this->options['page']['cache_ignore']) .
					(empty($this->options['restricted']) ? '' : ' ' . $this->options['restricted']);
				$excluded_html_pages = preg_replace("/ /", "|", preg_replace("/([\?!\^\$\|\(\)\[\]\{\}])/", "\\\\$1", $list));
			}
			if (!empty($this->options['page']['allowed_user_agents'])) {
				$included_user_agents = preg_replace("/ /", "|", preg_replace("/([\?!\^\$\|\(\)\[\]\{\}])/", "\\\\$1", $this->options['page']['allowed_user_agents']));
			}
			if (!empty($this->options['page']['exclude_cookies'])) {
				$cookies = explode(" ", $this->options['page']['exclude_cookies']);
				foreach ($cookies as $cookie) {
					if (isset($_COOKIE[$cookie])) {
						$retricted_cookie = 1;
					}
				}
			}
		}
/* cache if
  - option is enabled,
  - don't parse excluded pages,
  - or parse included USER AGENTS,
  - don't parse pages with excluded coockies,
  - flush or gzip for HTML are disabled,
  - headers have not been sent,
  - page is requested by GET,
  - no chained optimization,
  - no debug mode,
  - external cache restriction.
*/
		$this->cache_me = !empty($this->options['page']['cache']) &&
			(empty($this->options['page']['cache_ignore']) ||
				!preg_match("!" . $excluded_html_pages . "!is", $_SERVER['REQUEST_URI']) ||
				preg_match("!" . $included_user_agents . "!is", $this->ua)) &&
			!$retricted_cookie &&
			(empty($this->options['page']['gzip']) ||
				empty($this->options['page']['flush'])) &&
			!headers_sent() &&
			(getenv('REQUEST_METHOD') == 'GET') &&
			empty($this->web_optimizer_stage) &&
			!$this->debug_mode &&
			empty($this->no_cache);
/* check if we can get out cached page */
		if (!empty($this->cache_me)) {
			$this->uri = $this->convert_request_uri();
/* gzip cached content before output? (plugins have onCache) */
			$gzip_me = is_array($this->options['plugins']);
			$cache_plain_key = $this->view->ensure_trailing_slash($this->uri) . 'index' . $this->ua_mod . '.html';
			$cache_key = $cache_plain_key .
				($this->options['page']['flush'] ||
				empty($this->encoding_ext) ||
				$gzip_me ? '' : $this->encoding_ext);
			$timestamp = $this->cache_engine->get_mtime($cache_key);
/* try to get from cache non-gzipped page if gzipped one doesn't exist */
			if (!$timestamp && !$this->options['page']['flush'] && !empty($this->encoding_ext) && !$gzip_me) {
				$timestamp = $this->cache_engine->get_mtime($cache_plain_key);
				$gzip_me = 1;
			}
			if ($timestamp && $this->time - $timestamp < $this->options['page']['cache_timeout']) {
				$content = $this->cache_engine->get_entry($gzip_me ? $cache_plain_key : $cache_key);
				if (class_exists('JUtility'))
				{
					$token = JUtility::getToken();
					$content = str_replace('##WSS_JTOKEN_WSS##', $token, $content);
				}
/* execute plugin-specific logic */
				if (is_array($this->options['plugins'])) {
					foreach ($this->options['plugins'] as $plugin) {
						$plugin_file =
							$this->options['css']['installdir'] .
								'plugins/' . $plugin . '.php';
						if (@is_file($plugin_file)) {
							include_once($plugin_file);
							$web_optimizer_plugin = new $plugin;
							$content =
								$web_optimizer_plugin->onAfterOptimization($content);
						}
					}
				}
				if ($gzip_me) {
					$cnt = $this->create_gz_compress($content,
						in_array($this->encoding, array('gzip', 'x-gzip')));
					if (!empty($cnt)) {
						$content = $cnt;
/* skip gzip if we can't compress content */
					} else {
						$this->options['page']['gzip'] = 0;
						$this->encoding = '';
					}
				}
				$hash = crc32($content) .
					(empty($this->encoding) ? '' : '-' . str_replace("x-", "", $this->encoding));
/* check for return visits */
				if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
					stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $hash . '"') ||
					(isset($_SERVER['HTTP_IF_MATCH']) &&
					stripslashes($_SERVER['HTTP_IF_MATCH']) == '"' . $hash . '"')) {
/* return visit and no modifications, so do not send anything */
					header ("HTTP/1.0 304 Not Modified");
					header ("Content-Length: 0");
					while (@ob_end_clean());
					die();
				}
/* set ETag, thx to merzmarkus */
				header("ETag: \"" . $hash . "\"");
/* set content-type */
				if (!empty($this->options['charset'])) {
					header("Content-Type: text/html; charset=" . $this->compress_options['charset']);
				}
				if (empty($this->web_optimizer_stage) &&
					$this->options['page']['clientside_cache']) {
/* not really GMT but is valid locally */
					$ExpStr = date("D, d M Y H:i:s",
						$this->time + $this->options['page']['clientside_timeout']) . " GMT";
					header("Cache-Control: " .
						($this->options['page']['gzip'] ? 'private' : 'public') .
						", max-age=" .
						$this->options['page']['clientside_timeout']);
					header("Expires: " . $ExpStr);
				}
/* check if cached content must be gzipped, can't gzip twice via php for flush */
				if ($this->options['page']['gzip'] && !$this->options['page']['flush']) {
					$this->set_gzip_header();
				}
				while (@ob_end_clean());
				echo $content;
/* content is a head part, flush it after */
				if ($this->options['page']['flush']) {
					flush();
					$this->flushed = true;
				} else {
					die();
				}
			}
		}
/* change some hosts if HTTPS is used */
		if ($this->https && !empty($this->options['page']['parallel_https'])) {
			$this->options['javascript']['host'] =
			$this->options['css']['host'] =
			$this->options['page']['parallel_hosts'] = 
			$this->options['page']['parallel_https'];
		}
/* number of external files calls to process */
		$this->initial_files = array();
/* set internal encoding */
		$this->charset = empty($wss_encoding) ? 'utf8' : $wss_encoding;
/* prepare escaped host */
		$this->host_escaped = str_replace('.', '\.', $this->host);
/* activate application */
		$this->options['active'] = 1;
		if ($this->buffered) {
/* Start things off */
			$this->start();
		}
	}

	/**
	* Write installation progress to JavaScript file
	*
	**/
	function write_progress ($progress) {
		$this->write_file($this->options['javascript']['cachedir'] . 'progress.html', $progress);
	}

	/**
	* Options are read from config.webo.php
	**/
	function set_options () {
/* Set paths with new options */
		$this->options['document_root'] = empty($this->options['document_root']) ? '' : $this->options['document_root'];
		$this->view->set_paths($this->options['document_root']);
/* Set local root if chained optimization */
		if (!empty($this->web_optimizer_stage)) {
			$this->view->paths['full']['current_directory'] = $this->view->paths['full']['document_root'];
			$this->view->paths['relative']['current_directory'] = $this->view->paths['relative']['document_root'];
			$_SERVER['REQUEST_URI'] = '/';
/* force User Agent on chained optimization */
			$mods = array(
/* all common browsers except IE */
				'',
/* IE 6.0, when will it die? */
				'.ie6',
/* IE 7.0 */
				'.ie7',
/* IE 8.0 */
				'.ie8',
/* Mobile Agents */
				'.ma'
			);
			$this->ua_mod = $mods[$this->cache_stage];
		}
		$this->premium = $this->view->validate_license($this->options['license'], $this->options['html_cachedir'], $this->options['host']);
		$this->set_user_agent();
		$webo_cachedir = $this->view->unify_dir_separator(realpath(dirname(__FILE__) . '/../') . '/');
/* ensure trailing slashes */
		$this->options['html_cachedir'] = $this->view->ensure_trailing_slash($this->options['html_cachedir']);
		$this->options['css_cachedir'] = $this->view->ensure_trailing_slash($this->options['css_cachedir']);
		$this->options['javascript_cachedir'] = $this->view->ensure_trailing_slash($this->options['javascript_cachedir']);
/* normalize host */
		if (!empty($this->options['host'])) {
			$this->options['host'] = preg_replace("!^https?://!", "", $this->options['host']);
		}
/* Read in options */
		$full_options = array(
			"javascript" => array(
				"cachedir" => $this->options['javascript_cachedir'],
				"cachedir_relative" => str_replace($this->options['document_root'], "/", $this->options['javascript_cachedir']),
				"installdir" => $webo_cachedir,
				"gzip" => $this->options['gzip']['javascript'] &&
					((!$this->options['htaccess']['mod_gzip'] &&
						!$this->options['htaccess']['mod_deflate'] &&
						(!$this->options['htaccess']['mod_rewrite'] ||
							!$this->options['htaccess']['mod_mime'] ||
							!$this->options['htaccess']['mod_expires'])) ||
					!$this->options['htaccess']['enabled']),
				"gzip_level" => round($this->options['gzip']['javascript_level']),
				"minify" => $this->options['minify']['javascript'],
				"minify_body" => $this->options['minify']['javascript_body'],
				"minify_with" => $this->options['minify']['with_jsmin'] ?
					'jsmin' : ($this->options['minify']['with_yui'] ?
					'yui' : ($this->options['minify']['with_packer'] ?
					'packer' : '')),
				"minify_try" => $this->options['external_scripts']['include_try'],
				"minify_exclude" => $this->options['external_scripts']['minify_exclude'],
				"remove_duplicates" => $this->options['external_scripts']['duplicates'],
				"far_future_expires" => $this->options['far_future_expires']['javascript'] &&
					!$this->options['htaccess']['mod_expires'],
				"far_future_expires_php" => $this->options['far_future_expires']['javascript'],
				"far_future_expires_rewrite" => $this->options['htaccess']['mod_rewrite'] &&
					$this->options['htaccess']['enabled'] &&
					$this->options['far_future_expires']['javascript'],
				"unobtrusive_body" => $this->options['unobtrusive']['body'] &&
					!$this->options['unobtrusive']['all'],
				"external_scripts" => $this->options['external_scripts']['on'] &&
					$this->options['minify']['javascript'],
				"inline_scripts" => $this->options['external_scripts']['inline'] &&
					$this->options['minify']['javascript'],
				"external_scripts_head_end" => $this->options['external_scripts']['head_end'],
				"external_scripts_exclude" => $this->options['external_scripts']['ignore_list'],
				"dont_check_file_mtime" => $this->options['performance']['mtime'],
				"file" => $this->options['minify']['javascript_file'],
				"host" => $this->options['minify']['javascript_host'],
				"https" => $this->premium > 1 ? $this->options['parallel']['https'] : ''
			),
			"css" => array(
				"cachedir" => $this->options['css_cachedir'],
				"cachedir_relative" => str_replace($this->options['document_root'], "/", $this->options['css_cachedir']),
				"installdir" => $webo_cachedir,
				"gzip" => $this->options['gzip']['css'] &&
					((!$this->options['htaccess']['mod_gzip'] &&
						!$this->options['htaccess']['mod_deflate'] &&
						(!$this->options['htaccess']['mod_rewrite'] ||
							!$this->options['htaccess']['mod_mime'] ||
							!$this->options['htaccess']['mod_expires'])) ||
					!$this->options['htaccess']['enabled']),
				"gzip_level" => round($this->options['gzip']['css_level']),
				"minify" => $this->options['minify']['css'],
				"minify_body" => $this->options['minify']['css_body'],
				"minify_with" => $this->options['minify']['css_min'] == 2 ?
					'tidy' : ($this->options['minify']['css_min'] ? 'basic' : ''),
				"far_future_expires" => $this->options['far_future_expires']['css'] &&
					!$this->options['htaccess']['mod_expires'],
				"far_future_expires_php" => $this->options['far_future_expires']['css'],
				"far_future_expires_rewrite" => $this->options['htaccess']['mod_rewrite'] &&
					$this->options['htaccess']['enabled'] &&
					$this->options['far_future_expires']['css'],
				"data_uris" => $this->options['data_uris']['on'],
/* disable mhtml for IE7- under HTTPS */
				"data_uris_mhtml" => $this->options['data_uris']['mhtml'] &&
					(!$this->https || (!strpos($this->ua, 'MSIE 6') && !strpos($this->ua, 'MSIE 7'))),
				"data_uris_separate" => $this->options['data_uris']['separate'] &&
					((!empty($this->ua_mod) &&
							$this->options['data_uris']['mhtml']) ||
						(empty($this->ua_mod) &&
							$this->options['data_uris']['on'])),
				"data_uris_domloaded" => $this->options['data_uris']['domloaded'],
				"data_uris_size" => round($this->options['data_uris']['size']),
				"data_uris_mhtml_size" => round($this->options['data_uris']['mhtml_size']),
				"data_uris_exclude" => $this->options['data_uris']['ignore_list'],
				"data_uris_exclude_mhtml" => $this->options['data_uris']['additional_list'],
				"css_sprites" => $this->options['css_sprites']['enabled'],
				"css_sprites_expires_rewrite" => (!$this->options['htaccess']['mod_rewrite'] ||
					!$this->options['htaccess']['enabled']) &&
					$this->options['far_future_expires']['images'],
				"css_sprites_ignore" => $this->options['css_sprites']['ignore'],
				"css_sprites_exclude" => $this->options['css_sprites']['ignore_list'],
				"truecolor_in_jpeg" => $this->options['css_sprites']['truecolor_in_jpeg'],
				"aggressive" => $this->options['css_sprites']['aggressive'],
				"no_ie6" => $this->options['css_sprites']['no_ie6'],
				"dimensions_limited" => round($this->options['css_sprites']['dimensions_limited']),
				"css_sprites_extra_space" => $this->options['css_sprites']['extra_space'],
				"punypng" => (!empty($this->options['punypng']) ? $this->options['punypng'] : '') &&
					($this->premium > 1),
				"css_restore_properties" => $this->options['performance']['restore_properties'] &&
					($this->premium > 1),
				"unobtrusive_body" => false,
				"parallel" => $this->options['parallel']['enabled'],
				"parallel_hosts" => $this->options['parallel']['allowed_list'],
				"external_scripts" => $this->options['external_scripts']['css'],
				"inline_scripts" => $this->options['external_scripts']['css_inline'],
				"external_scripts_exclude" => $this->options['external_scripts']['additional_list'],
				"include_code" => $this->options['external_scripts']['include_code'],
				"dont_check_file_mtime" => $this->options['performance']['mtime'],
				"file" => $this->options['minify']['css_file'],
				"host" => $this->options['minify']['css_host'],
				"https" => $this->premium > 1 ? $this->options['parallel']['https'] : ''
			),
			"page" => array(
				"cachedir" => $this->options['html_cachedir'],
				"cache_engine" => $this->options['performance']['cache_engine'],
				"cache_engine_options" => $this->options['performance']['cache_engine_options'],
				"cachedir_relative" => str_replace($this->options['document_root'], "/", $this->options['html_cachedir']),
				"installdir" => $webo_cachedir,
				"host" => $this->options['host'],
				"gzip" => $this->options['gzip']['page'] &&
					((!$this->options['htaccess']['mod_gzip'] &&
							!$this->options['htaccess']['mod_deflate']) ||
						!$this->options['htaccess']['enabled']),
				"gzip_noie" => $this->options['gzip']['noie'],
				"gzip_level" => round($this->options['gzip']['page_level']),
				"gzip_cookie" => $this->options['gzip']['cookie'],
				"minify" => $this->options['minify']['page'],
				"minify_aggressive" => $this->options['minify']['html_one_string'],
				"remove_comments" => $this->options['minify']['html_comments'],
				"dont_check_file_mtime" => $this->options['performance']['mtime'],
				"cache_images" => $this->options['far_future_expires']['images'],
				"far_future_expires_rewrite" => (!($this->options['htaccess']['mod_rewrite'] ||
						$this->options['htaccess']['mod_expires']) ||
					!$this->options['htaccess']['enabled']) &&
					$this->options['far_future_expires']['images'],
				"far_future_expires_external" => $this->options['far_future_expires']['external'],
				"clientside_cache" => $this->options['far_future_expires']['html'],
				"clientside_timeout" => $this->options['far_future_expires']['html_timeout'],
				"cache" => $this->options['html_cache']['enabled'],
				"cache_timeout" => $this->options['html_cache']['timeout'],
				"flush" => $this->options['html_cache']['flush_only'],
				"flush_size" => $this->options['html_cache']['flush_size'],
				"cache_ignore" => $this->options['html_cache']['ignore_list'],
				"cache_params" => $this->options['html_cache']['params'],
				"allowed_user_agents" => $this->options['html_cache']['allowed_list'],
				"exclude_cookies" => $this->options['html_cache']['additional_list'],
				"parallel" => $this->options['parallel']['enabled'],
				"parallel_hosts" => $this->options['parallel']['allowed_list'],
				"parallel_satellites" => $this->options['parallel']['additional'],
				"parallel_satellites_hosts" => $this->options['parallel']['additional_list'],
				"parallel_ignore" => $this->options['parallel']['ignore_list'],
				"parallel_css" => $this->options['parallel']['css'],
				"parallel_javascript" => $this->options['parallel']['javascript'],
				"parallel_ftp" => $this->options['parallel']['ftp'],
				"parallel_https" => $this->options['parallel']['https'],
				"unobtrusive_informers" => $this->options['unobtrusive']['informers'] &&
					($this->premium > 1),
				"unobtrusive_counters" => $this->options['unobtrusive']['counters'] &&
					($this->premium > 1),
				"unobtrusive_ads" => $this->options['unobtrusive']['ads'] &&
					($this->premium > 1),
				"unobtrusive_all" => $this->options['unobtrusive']['all'] &&
					($this->premium > 1),
				"unobtrusive_iframes" => $this->options['unobtrusive']['iframes'] &&
					($this->premium > 1),
				"unobtrusive_onload" => $this->options['unobtrusive']['on'] &&
					($this->premium > 1),
				"unobtrusive_inline" => $this->options['unobtrusive']['on'] == 2 &&
					($this->premium > 1),
				"footer" => $this->premium ? $this->options['footer']['text'] : 1,
				"footer_image" => $this->options['footer']['image'],
				"footer_text" => $this->options['footer']['link'],
				"footer_style" => $this->options['footer']['css_code'],
				"spot" => $this->premium ? $this->options['footer']['spot'] : 1,
				"counter" => $this->options['footer']['counter'],
				"htaccess_username" => $this->options['external_scripts']['user'],
				"htaccess_password" => $this->options['external_scripts']['pass'],
				"html_tidy" => $this->options['performance']['plain_string'],
				"sprites" => $this->options['css_sprites']['html_sprites'],
				"dimensions_limited" => round($this->options['css_sprites']['html_limit']),
				"per_page" => $this->options['css_sprites']['html_page']
			),
			"document_root" => $this->options['document_root'],
			"document_root_relative" => str_replace("//", "/", str_replace($this->options['document_root'], "/", $this->options['website_root'])),
			"website_root" => $this->options['website_root'],
			"cache_version" => round($this->options['performance']['cache_version']),
			"uniform_cache" => $this->options['performance']['uniform_cache'],
			"plugins" => ($this->premium > 1) &&
				!empty($this->options['plugins']) ? explode(" ", $this->options['plugins']) : '',
			"restricted" => ($this->premium > 1) &&
				!empty($this->options['restricted']) ? $this->options['restricted'] : '',
			"days_to_delete" => round($this->options['performance']['delete_old']),
			"charset" => $this->options['charset']
		);
		$this->lc = $this->options['license'];
/* overwrite other options array that we passed in */
		$this->options = $full_options;
	}

	/**
	* Start saving the output buffer
	*
	**/
	function start () {
		ob_start();
		ob_start();
		ob_implicit_flush(0);
	}

	/**
	* Compress passes content directly
	*
	**/
	function compress ($content) {
		$this->finish($content);
	}

	/**
	* Do work and return output buffer
	*
	**/
	function finish ($content = false) {
/* disable any actions if not active */
		if (empty($this->options['active'])) {
			return $content;
		}
		if ($content === false) {
			$this->content = ob_get_clean();
/* clear all other buffers */
			while (@ob_end_clean());
		} else {
			$this->content = $content;
		}
/* execute plugin-specific logic, BeforeOptimization event */
		if (is_array($this->options['plugins'])) {
			foreach ($this->options['plugins'] as $plugin) {
				$plugin_file =
					$this->options['css']['installdir'] .
						'plugins/' . $plugin . '.php';
				if (@is_file($plugin_file)) {
					include_once($plugin_file);
					$web_optimizer_plugin = new $plugin;
					$this->content =
						$web_optimizer_plugin->onBeforeOptimization($this->content);
				}
			}
		}
		$skip = 0;
		if (function_exists('get_headers')) {
			$headers = headers_list();
/* define if Content-Type is text/html and allow it */
			foreach ($headers as $head) {
				$header = strtolower($head);
				if (strpos($header, 'content-type:') !== false || strpos($header, 'location:') !== false) {
					$skip++;
				}
				if (strpos($header, 'text/html') || strpos($header, 'application/xhtml+xml')) {
					$skip--;
				}
				if (strpos($header, 'content-base') !== false) {
					$this->basehref = substr($head, 14);
				}
			}
		}
/* also skip AJAX requests with X-Requested-With: XMLHttpRequest */
		if (!$skip &&
			!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
				$skip = 1;
		}
/* also skip some CMS-ralted parameters */
		if (!$skip && !empty($_GET['no_html'])) {
			$skip = 1;
		}
/* skip some extensions */
		if (!$skip && !empty($_SERVER['QUERY_STRING'])) {
			$query = explode('.', $_SERVER['QUERY_STRING']);
			$ext = strtolower($query[count($query) - 1]);
			if (in_array($ext, array('pdf', 'doc', 'xls', 'docx', 'xlsx'))) {
				$skip = 1;
			}
		}
/* skip some known cases of non-HTML content */
		if (!$skip) {
/* reduce amount of viewing content, accelerate 'fast check' by 1% */
			$spot = substr($this->content, 0, 60);
			if (strpos($spot, '<methodResponse') !== false ||
				strpos($spot, '<rss') !== false ||
				strpos($spot, '<feed') !== false ||
				strpos($spot, '<urlset') !== false ||
				strpos($spot, '<smf') !== false ||
				strpos($spot, '{') === 0 ||
				strlen($this->content) < 200) {
					$skip = 1;
			}
		}
/* restrict URL from configuration */
		if (!$skip &&
			!empty($this->options['restricted'])) {
				if (preg_match("@" . preg_replace("/ /", "|",
					preg_replace("/([\?!\^\$\|\(\)\[\]\{\}])/",
					"\\\\$1",
					$this->options['restricted'])) . "@",
					$_SERVER['REQUEST_URI'])) {
						$skip = 1;
				}
		}
/* skip RSS, SMF xml format */
		if (!$skip) {
/* find all files in head to process */
				$this->get_script_array();
/* Run the functions specified in options */
				if (is_array($this->options)) {
					foreach ($this->options as $func => $option) {
						if (method_exists($this, $func)) {
							if (!empty($option['gzip']) ||
								!empty($option['minify']) ||
								!empty($option['far_future_expires']) ||
								!empty($option['parallel']) ||
								!empty($option['unobtrusive_all']) ||
								!empty($option['unobtrusive_ads']) ||
								!empty($option['unobtrusive_counters']) ||
								!empty($option['unobtrusive_informers']) ||
								!empty($option['unobtrusive_iframes']) ||
								!empty($option['cache']) ||
								!empty($option['sprites'])) {
									if (!empty($this->web_optimizer_stage)) {
										$this->write_progress($this->web_optimizer_stage++);
									}
									$this->$func($option, $func);
							}
						}
					}
				}
				if (!empty($this->web_optimizer_stage)) {
					$this->write_progress($this->web_optimizer_stage);
/* redirect to installation page if chained optimization if finished */
					if ($this->web_optimizer_stage > 85) {
						if ($this->chained_redirect === 'optimizing.php') {
							$this->write_progress(97);
							header('Location: ../index.php?page=install_stage_3&Submit=1&web_optimizer_stage=97&wss__password=' .
								$this->password);
						}
/* else redirect to the next stage */
					} else {
						header('Location: ' . $this->chained_redirect . '?web_optimizer_stage=' . 
							$this->web_optimizer_stage .
							'&password=' .
							$this->password .
							'&web_optimizer_debug=1');
					}
					while (@ob_end_clean());
					die();
				}
		}
/* remove marker for styles */
		$this->content = str_replace('@@@WSSSTYLES@@@', '', $this->content);
/* Return content to requestor */
		if ($content) {
			return $this->content;
/* or echo content to the browser */
		} else {
/* HTTP/1.0 needs Content-Length sometimes. With PHP4 we can't check when exactly. */
			if (!empty($this->encoding)) {
				header('Content-Length: ' . strlen($this->content));
			}
			echo $this->content;
/* It's obvious to send anything right after gzipped content */
			if (!empty($this->encoding)) {
				while (@ob_end_clean());
				die();
			}
		}
	}

	/**
	* GZIP and minify the javascript as required
	*
	**/
	function javascript ($options,$type) {
/* prepare list of files to process */
		$script_files = array();
		foreach ($this->initial_files as $file) {
			if (!empty($file['tag']) && $file['tag'] == 'script') {
				$script_files[] = $file;
			}
		}
		if (!empty($options['minify']) && !empty($script_files)) {
			$this->content = $this->do_compress(
				array(
					'cachedir' => $options['cachedir'],
					'cachedir_relative' => $options['cachedir_relative'],
					'installdir' => $options['installdir'],
					'host' => $options['host'],
					'tag' => 'script',
					'type' => 'text/javascript',
					'ext' => 'js',
					'src' => 'src',
					'self_close' => false,
					'gzip' => $options['gzip'],
					'gzip_level' => $options['gzip_level'],
					'minify' => $options['minify'],
					'minify_body' => $options['minify_body'],
					'minify_with' => $options['minify_with'],
					'minify_try' => $options['minify_try'],
					'minify_exclude' => $options['minify_exclude'],
					'remove_duplicates' => $options['remove_duplicates'],
					'far_future_expires' => $options['far_future_expires'],
					'far_future_expires_php' => $options['far_future_expires_php'],
					'far_future_expires_rewrite' => $options['far_future_expires_rewrite'],
					'header' => $type,
					'css_sprites' => false,
					'css_sprites_exclude' => false,
					'aggressive' => false,
					'no_ie6' => false,
					'dimensions_limited' => false,
					'css_sprites_extra_space' => false,
					'data_uris' => false,
					'mhtml' => false,
					'unobtrusive_body' => $options['unobtrusive_body'],
					'external_scripts' => $options['external_scripts'],
					'inline_scripts' => $options['inline_scripts'],
					'external_scripts_head_end' => $options['external_scripts_head_end'],
					'external_scripts_exclude' => $options['external_scripts_exclude'],
					'dont_check_file_mtime' => $options['dont_check_file_mtime'],
					'file' => $options['file'],
					'https' => $options['https']
				),
				$this->content,
				$script_files
			);
		}
	}

	/**
	* GZIP and minify the CSS as required
	*
	**/
	function css ($options, $type) {
/* prepare list of files to process */
		$link_files = array();
		foreach ($this->initial_files as $file) {
			if (!empty($file['tag']) && $file['tag'] == 'link') {
				$link_files[] = $file;
			}
		}
		if (!empty($options['minify']) && !empty($link_files)) {
/* Compress separately for each media type*/
			$this->content = $this->do_compress(
				array(
					'cachedir' => $options['cachedir'],
					'cachedir_relative' => $options['cachedir_relative'],
					'installdir' => $options['installdir'],
					'host' => $options['host'],
					'tag' => 'link',
					'type' => 'text/css',
					'ext' => 'css',
					'src' => 'href',
					'rel' => 'stylesheet',
					'data_uris' => $options['data_uris'],
					'mhtml' => $options['data_uris_mhtml'],
					'data_uris_separate' => $options['data_uris_separate'],
					'data_uris_domloaded' => $options['data_uris_domloaded'],
					'data_uris_size' => $options['data_uris_size'],
					'data_uris_exclude' => $options['data_uris_exclude'],
					'mhtml' => $options['data_uris_mhtml'],
					'mhtml_size' => $options['data_uris_mhtml_size'],
					'mhtml_exclude' => $options['data_uris_exclude_mhtml'],
					'css_sprites' => $options['css_sprites'],
					'css_sprites_ignore' => $options['css_sprites_ignore'],
					'css_sprites_exclude' => $options['css_sprites_exclude'],
					'truecolor_in_jpeg' => $options['truecolor_in_jpeg'],
					'aggressive' => $options['aggressive'],
					'no_ie6' => $options['no_ie6'],
					'dimensions_limited' => $options['dimensions_limited'],
					'css_sprites_extra_space' => $options['css_sprites_extra_space'],
					'css_sprites_expires_rewrite' => $options['css_sprites_expires_rewrite'],
					'punypng' => $options['punypng'],
					'css_restore_properties' => $options['css_restore_properties'],
					'self_close' => true,
					'gzip' => $options['gzip'],
					'gzip_level' => $options['gzip_level'],
					'minify' => $options['minify'],
					'minify_body' => $options['minify_body'],
					'minify_with' => $options['minify_with'],
					'far_future_expires' => $options['far_future_expires'],
					'far_future_expires_php' => $options['far_future_expires_php'],
					'far_future_expires_rewrite' => $options['far_future_expires_rewrite'],
					'header' => $type,
					'unobtrusive_body' => $options['unobtrusive_body'],
					'parallel' => $options['parallel'],
					'parallel_hosts' => $options['parallel_hosts'],
					'external_scripts' => $options['external_scripts'],
					'inline_scripts' => $options['inline_scripts'],
					'external_scripts_exclude' => $options['external_scripts_exclude'],
					'include_code' => $options['include_code'],
					'dont_check_file_mtime' => $options['dont_check_file_mtime'],
					'file' => $options['file'],
					'https' => $options['https']
				),
				$this->content,
				$link_files
			);
		}
	}

	/**
	* GZIP and minify the page itself as required
	*
	**/
	function page ($options, $type) {
		if (empty($this->web_optimizer_stage) && $options['clientside_cache'] && empty($this->flushed)) {
/* not really GMT but is valid locally */
			$ExpStr = date("D, d M Y H:i:s",
				$this->time + $this->options['page']['clientside_timeout']) . " GMT";
			header("Cache-Control: private, max-age=" .
				$this->options['page']['clientside_timeout']);
			header("Expires: " . $ExpStr);
		}
/* move informers, counters, ads, and iframes before </body> */
		$this->replace_informers($options);
/* Minify page itself or parse multiple hosts */
		if (!empty($options['minify']) ||
			(!empty($options['parallel']) &&
				!empty($options['parallel_hosts'])) ||
			!empty($options['unobtrusive_all']) ||
			!empty($this->options['page']['far_future_expires_rewrite']) ||
			!empty($this->options['page']['far_future_expires_external']) ||
			!empty($this->options['page']['sprites'])) {
				$this->content = $this->trimwhitespace($this->content);
		}
/* remove marker for styles and BOM */
		$this->content = str_replace(array("@@@WSSSTYLES@@@", "@@@WSSSCRIPT@@@", "﻿"), "", $this->content);
/* Add script to check gzip possibility */
		if (!empty($options['gzip_cookie']) && empty($_COOKIE['_wo_gzip_checked']) && empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			$cookie = '<script type="text/javascript" src="' . $options['cachedir_relative'] . 'wo.cookie.php"></script>';
			if ($options['html_tidy'] && ($bodypos = strpos($this->content, '</body>'))) {
				$this->content = substr_replace($this->content, $cookie, $bodypos, 0);
			} elseif ($options['html_tidy'] && ($bodypos = strpos($this->content, '</BODY>'))) {
				$this->content = substr_replace($this->content, $cookie, $bodypos, 0);
			} else {
				$this->content = preg_replace('@(</body>)@is', $cookie . "$1", $this->content);
/* a number of engines doesn't set </body> */
				if (!strpos($this->content, "wo.cookie")) {
					$this->content .= $cookie;
				}
			}
		}
/* execute plugin-specific logic, AfterOptimization event */
		if (is_array($this->options['plugins'])) {
			foreach ($this->options['plugins'] as $plugin) {
				$plugin_file =
					$this->options['css']['installdir'] .
						'plugins/' . $plugin . '.php';
				if (@is_file($plugin_file)) {
					include_once($plugin_file);
					$web_optimizer_plugin = new $plugin;
					$this->content =
						$web_optimizer_plugin->onAfterOptimization($this->content);
				}
			}
		}
		if (!empty($this->web_optimizer_stage)) {
			$this->write_progress($this->web_optimizer_stage++);
		}
/* check if we need to store cached page */
		if (!empty($this->cache_me)) {
/* add client side replacement for WordPress comment fields */
			if (defined('WP_CACHE')) {
				foreach ($_COOKIE as $key => $value) {
					if (strpos($key, 'comment_author') === 0) {
						$this->content = str_replace('value="'. urldecode($value) .'"', 'value=""', $this->content);
					}
				}
				$this->content = preg_replace("@(</body>)@is", '<script type="text/javascript">(function(){var a=document.cookie.split(";"),b,c=0,d,e;while(b=a[c++]){if(b.indexOf("comment_author_")!=-1){d=b.split("=");e=document.getElementById(d[0].replace(/(_?[a-f0-9]{32,}|\s?comment_author_)/g,"")||"author");if(e){e.value=unescape(d[1].replace(/\+/g," "))}}}}())</script>$1', $this->content);
			}
			if (class_exists('JUtility'))
			{
				$token = JUtility::getToken();
				$this->content = str_replace($token, '##WSS_JTOKEN_WSS##', $this->content);
			}				
/* prepare flushed part of content */
			if (!empty($options['flush']) && empty($this->encoding)) {
				if (empty($options['flush_size'])) {
					if ($this->options['page']['html_tidy'] && ($headpos = strpos($source, '</head>'))) {
						$content_to_write = substr($this->content, 0, $headpos + 7);
					} elseif ($this->options['page']['html_tidy'] && ($headpos = strpos($source, '</HEAD>'))) {
						$content_to_write = substr($this->content, 0, $headpos + 7);
					} else {
						$content_to_write = preg_replace("!(.*<\/head>).*!is", "$1", $this->content);
					}
				} else {
					$content_to_write =
						substr($this->content, 0, $options['flush_size']);
				}
			}
			$ordinary_cache_key = $this->view->ensure_trailing_slash($this->uri) . 'index' . $this->ua_mod . '.html';
			$cache_key = $ordinary_cache_key . (empty($this->encoding_ext) ? '' : $this->encoding_ext);
			$timestamp = $this->cache_engine->get_mtime($cache_key);
/* set ETag, thx to merzmarkus */
			if (empty($options['flush'])) {
				header("ETag: \"" .
					md5($this->content) .
					(empty($this->encoding) ? '' : '-' .
						str_replace("x-", "", $this->encoding)) .
					"\"");
			}
			if (empty($timestamp) || $this->time - $timestamp > $options['cache_timeout']) {
				if (!empty($options['gzip']) && !empty($this->encoding)) {
					$content_to_write = $this->create_gz_compress($this->content,
						in_array($this->encoding, array('gzip', 'x-gzip')));
/* or just write full or non-gzipped content */
				} elseif (empty($options['flush']) || !empty($this->encoding)) {
					$content_to_write = $this->content;
				}
/* don't create empty files */
				if (!empty($content_to_write)) {
					$this->cache_engine->put_entry($cache_key, $content_to_write, $this->time);
				}
/* create uncompressed file for plugins */
				if (is_array($this->options['plugins']) &&
					!empty($this->encoding_ext)) {
						$this->cache_engine->put_entry($ordinary_cache_key, $this->content, $this->time);
				}
			}
		}
/* execute plugin-specific logic, Cache event */
		if (is_array($this->options['plugins'])) {
			foreach ($this->options['plugins'] as $plugin) {
				$plugin_file =
					$this->options['css']['installdir'] .
						'plugins/' . $plugin . '.php';
				if (@is_file($plugin_file)) {
					include_once($plugin_file);
					$web_optimizer_plugin = new $plugin;
					$this->content =
						$web_optimizer_plugin->onCache($this->content);
				}
			}
		}
/* strip from content flushed part */
		if (!empty($this->flushed)) {
			if (empty($options['flush_size'])) {
				$options['flush_size'] = strlen($content_to_write);
			}
			$this->content = substr($this->content, $options['flush_size']);
		}
/* Gzip page itself */
		if(!empty($options['gzip']) && !empty($this->encoding)) {
			$content = $this->create_gz_compress($this->content,
				in_array($this->encoding, array('gzip', 'x-gzip')));
			if (!empty($content)) {
				$this->set_gzip_header();
				$this->content = $content;
			}
		}
	}

	/**
	* Write content to file
	* 
	**/
	function write_file ($file, $content, $upload = 0, $mime = '') {
		if (@function_exists('file_put_contents')) {
			@file_put_contents($file, $content);
		} else {
			$fp = @fopen($file, "a");
			if ($fp) {
/* block file from writing */
				@flock($fp, LOCK_EX);
/* erase content and move to the beginning */
				@ftruncate($fp, 0);
				@fseek($fp, 0);
				@fwrite($fp, $content);
				@fclose($fp);
			}
		}
		@touch($file, $this->time);
		@chmod($file, octdec("0644"));
		if ($upload && !empty($this->options['page']['parallel_ftp'])) {
			$this->view->upload_cdn($file,
				$this->options['document_root'],
				$this->options['page']['parallel_ftp'],
				$mime,
				$this->options['page']['host']);
		}
	}

	/**
	* Adds multiple hosts to HTML for images
	*
	**/
	function add_multiple_hosts ($content, $hosts, $satellites, $satellites_hosts) {
/* limit by 4 */
		if (count($hosts) > 4) {
			$hosts = array($hosts[0], $hosts[1], $hosts[2], $hosts[3]);
		}
		if (count($satellites_hosts) > 4) {
			$satellites_hosts = array($satellites_hosts[0], $satellites_hosts[1], $satellites_hosts[2], $satellites_hosts[3]);
		}
		$count = count($hosts);
		$count_satellites = count($satellites_hosts);
		$replaced = array();
		$IMG = strpos($content, '<IMG');
		if (!empty($this->options['page']['html_tidy']) && !$IMG) {
			$_content = $content;
			while ($pos = strpos($_content, '<img')) {
				$len = strpos(substr($_content, $pos), '>');
/* gets image tag w/o the closing >, it's OK */
				$imgs[] = array(substr($_content, $pos, $len));
				$_content = substr_replace($_content, '', $pos, $len);
			}
		} elseif (empty($this->options['page']['html_tidy']) || $IMG) {
			preg_match_all("!<img[^>]+>!is", $content, $imgs, PREG_SET_ORDER);
		}
		if (!empty($this->options['page']['sprites']) && !empty($imgs)) {
			require($this->options['css']['installdir'] . 'libs/php/html.sprites.php');
			$html_sprites = new html_sprites($imgs, $this->options, $this);
			$content = $html_sprites->process($content);
		}
		if (!empty($imgs)) {
			$ignore_list = explode(" ", $this->options['page']['parallel_ignore']);
			$ignore_sprites = explode(" ", $this->options['css']['css_sprites_exclude']);
			foreach ($imgs as $image) {
				if (!empty($this->options['page']['html_tidy']) && ($pos=strpos($image[0], ' src="'))) {
					$old_src = substr($image[0], $pos+6, strpos(substr($image[0], $pos+6), '"'));
				} elseif (!empty($this->options['page']['html_tidy']) && ($pos=strpos($image[0], " src='"))) {
					$old_src = substr($image[0], $pos+6, strpos(substr($image[0], $pos+6), "'"));
				} else {
					$old_src = preg_replace("!^['\"\s]*(.*?)['\"\s]*$!is", "$1", preg_replace("!.*[\"'\s]src\s*=\s*(\"[^\"]+\"|'[^']+'|[\S]+).*!is", "$1", $image[0]));
				}
				$old_src = $this->convert_basehref($old_src);
				$old_src_param = ($old_src_param_pos = strpos($old_src, '?')) ? substr($old_src, $old_src_param_pos) : '';
/* image file name to check through ignore list */
				$img = preg_replace("@.*/@", "", $old_src);
				$absolute_src = $this->convert_path_to_absolute($old_src,
					array('file' => $_SERVER['REQUEST_URI']));
				if (empty($replaced[$image[0]])) {
					if (!empty($this->options['page']['sprites']) &&
						((!in_array($img, $ignore_sprites) && empty($this->options['css']['css_sprites_ignore'])) ||
						(in_array($img, $ignore_sprites) && !empty($this->options['css']['css_sprites_ignore']))) &&
						!empty($html_sprites->css_images[$absolute_src]) && !empty($html_sprites->css_images[$absolute_src][2]) &&
						(empty($this->ua_mod) || $this->ua_mod != '.ie6' || empty($this->options['css']['no_ie6']))) {
							$class = substr($html_sprites->css_images[$absolute_src][8], 1);
							if (!empty($this->options['page']['html_tidy']) &&
								(strpos($image[0], 'class') || strpos($image[0], 'CLASS'))) {
									if ($pos=strpos($image[0], ' class="')) {
										$end = strpos(substr($image[0], $pos + 7), '"');
										$new_image = substr($image[0], 0, $pos + 7 + $end) .
											' ' . $class . substr($image[0], $pos + 7 + $end);
									} elseif ($pos=strpos($image[0], " class='")) {
										$end = strpos(substr($image[0], $pos + 7), "'");
										$new_image = substr($image[0], 0, $pos + 7 + $end) .
											' ' . $class . substr($image[0], $end);
									} else {
										$new_image = preg_replace("!(.*['\"\s]class\s*=\s*)([\"'])?([^\"']+)([\"'])?([\s/>])(.*)!is", "$1$2$3 " .
											$class . "$4$5$6", $image[0]);
									}
							} elseif (preg_match("@['\"\s]class\s*=\s*['\"]@is", $image[0])) {
								$new_image = preg_replace("!(.*['\"\s]class\s*=\s*)([\"'])?([^\"']+)([\"'])?([\s/>])(.*)!is", "$1$2$3 " .
									$class . "$4$5$6", $image[0]);
							} elseif (preg_match("@['\"\s]class\s*=\s*@is", $image[0])) {
								$new_image = preg_replace("!(.*['\"\s]class\s*=\s*)([^\s]+)\s!is", "$1\"$2 " .
									$class . "\" ", $image[0]);
							} else {
								$new_image = substr($image[0], 0, 4) . ' class="' .
									$class . '"' . substr($image[0], 4);
							}
/* add transparent GIF or data:URI chunk */
							$new_src = (empty($this->ua_mod) ||
								substr($this->ua_mod, 3, 1) > 7) &&
								!$this->options['uniform_cache'] ?
								'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==' :
								((empty($this->options['page']['far_future_expires_rewrite']) ?
									'' : $this->options['page']['cachedir_relative'] . 'wo.static.php?') .
									$this->options['page']['cachedir_relative'] . '0.gif');
/* are we operating with multiple hosts */
					} elseif (!empty($this->options['page']['parallel']) &&
						!empty($this->options['page']['parallel_hosts']) &&
						(!count($ignore_list) || !in_array(str_replace($old_src_param, '', $img), $ignore_list))) {
/* skip images on different hosts */
						if (preg_match("!//(www\.)?" . $this->host_escaped . "/+!i", $old_src) || strpos($old_src, '//') === false) {
/* using secure host */
							if ($this->https && !empty($this->options['page']['parallel_https'])) {
								$new_host = $this->options['page']['parallel_https'];
							} else {
/* calculating unique sum from image src */
								$sum = 0;
								$i = ceil(strlen($old_src)/2);
								while (isset($old_src{$i++})) {
									$sum += ord($old_src{$i-1});
								}
								$host = $hosts[$sum%$count];
/* if we have dot in the distribution host - it's a domain name */
								$new_host = $host .
									((strpos($host, '.') === false) ?
									'.' . $this->host : '');
							}
							$new_src = "//" .
								$new_host .
								$absolute_src .
								preg_replace("!(www\.)?" . $this->host_escaped . "!i",
									$new_host, $old_src_param);
						} elseif ($count_satellites && !empty($satellites_hosts[0]) && empty($replaced[$old_src])) {
							$img_host = preg_replace("@(https?:)?//(www\.)?([^/]+)/.*@", "$3", $old_src);
/* check if we can distribute this image through satellites' hosts */
							if (in_array($img_host, $satellites)) {
								$new_src = preg_replace("@(https?://)(www\.)?([^/]+)/@", "$1" . $hosts[strlen($old_src)%$count] . ".$3/", $old_src);
							}
						}
/* or replacing images with rewrite to Expires setter? */
					} elseif (!empty($this->options['page']['far_future_expires_rewrite']) ||
						!empty($this->options['page']['far_future_expires_external'])) {
/* add static proxy for external images */
							if (!$absolute_src &&
								$this->options['page']['far_future_expires_external']) {
									$absolute_src = $old_src;
							}
/* do not touch dynamic images / styles / scripts -- how we can handle them? */
							if ($absolute_src &&
								(preg_match("@\.(bmp|gif|png|ico|jpe?g)$@is", $absolute_src) ||
									!empty($this->options['page']['far_future_expires_external']))) {
										$new_src =
											$this->options['page']['cachedir_relative'] .
											'wo.static.php?' . $absolute_src;
							}
					}
					if (!empty($new_src)) {
						if (empty($new_image)) {
/* prevent replacing images from oher domains with the same file name */
							$new_src_image = str_replace($old_src, $new_src, $image[0]);
						} else {
/* replace src in image with new class */
							$new_src_image = str_replace($old_src, $new_src, $new_image);
						}
						$content = str_replace($image[0], $new_src_image, $content);
						$new_src = '';
						$new_image = '';
					}
					$replaced[$image[0]] = 1;
				}
			}
		}
		return $content;
	}

	/**
	* Returns GZIP compressed content string with header
	*
	**/
	function create_gz_compress ($content, $force_gzip = true) {
		if (!empty($this->encoding)) {
			if (!empty($force_gzip) && function_exists('gzcompress')) {
				$size = strlen($content);
				$crc = crc32($content);
				$cnt = "\x1f\x8b\x08\x00\x00\x00\x00\x00";
				$content = gzcompress($content, $this->options['page']['gzip_level']);
				$content = substr($content, 0, -4);
				$cnt .= $content;
				$cnt .= pack('V', $crc);
				$cnt .= pack('V', $size);
			} elseif (empty($force_gzip) && function_exists('gzdeflate')) {
				$cnt = gzdeflate($content, $this->options['page']['gzip_level']);
			}
			return $cnt;
		} else {
			return false;
		}
	}

	/**
	* Sets current encoding for HTML document
	*
	**/
	function set_gzip_encoding () {
		if (!empty($_SERVER["HTTP_ACCEPT_ENCODING"]) && !empty($this->options['page']['gzip'])) {
			$gzip_no_ie = !in_array($this->ua_mod, array('.ie6', '.ie7')) || empty($this->options['page']['gzip_noie']);
			$ae = strtolower($_SERVER["HTTP_ACCEPT_ENCODING"]);
			if (strpos($ae, 'x-gzip') !== false && $gzip_no_ie) {
				$this->encoding = 'x-gzip';
				$this->encoding_ext = '.gz';
			} elseif ((strpos($ae, 'gzip') !== false || !empty($_COOKIE['_wo_gzip'])) && $gzip_no_ie) {
				$this->encoding = 'gzip';
				$this->encoding_ext = '.gz';
			} elseif (strpos($ae, 'x-deflate') !== false) {
				$this->encoding = 'x-deflate';
				$this->encoding_ext = '.df';
			} elseif (strpos($ae, 'deflate') !== false) {
				$this->encoding = 'deflate';
				$this->encoding_ext = '.df';
			}
		} elseif (empty($_SERVER['HTTP_ACCEPT_ENCODING']) && !empty($_COOKIE['_wo_gzip'])) {
			$this->encoding = 'gzip';
			$this->encoding_ext = '.gz';
		}
	}
	
	/**
	* Sets the correct gzip header
	*
	**/
	function set_gzip_header () {
		if(!empty($this->encoding)) {
			header("Content-Encoding: " . $this->encoding);
			header("Vary: Accept-Encoding,User-Agent");
		}
	}

	/**
	* Compress JS or CSS and return source
	*
	**/
	function do_compress ($options, $source, $files) {
/* Save the original extension */
		$options['original_ext'] = $options['ext'];
/* Change the extension */
		if (!empty($options['gzip']) || !empty($options['far_future_expires'])) {
			$options['ext'] = "php";
		}
/* Set cachedir */
		$cachedir = $options['cachedir'];
		if ($this->web_optimizer_stage) {
			$this->write_progress($this->web_optimizer_stage++);
		}
		$source = $this->do_include($options, $source, $cachedir, $files);
		return $source;
	}

	/**
	* Include a single file
	*
	**/
	function include_bundle ($source, $newfile, $handlers, $cachedir, $include, $href = '') {
		switch ($include) {
/* move CSS file to the first occurent of CSS or before any scripts */
			default:
				if ($this->options['page']['html_tidy']) {
					$styles = strpos($source, "@@@WSSSTYLES@@@");
					$script1 = strpos($source, "<script");
					$script2 = strpos($source, "<SCRIPT");
					if ($script1 !== false && $script1 < $styles) {
						$source = substr_replace($source, $newfile, $script1, 0);
					} elseif ($script2 !== false && $script2 < $styles) {
						$source = substr_replace($source, $newfile, $script2, 0);
					} else {
						$source = substr_replace($source, $newfile, $styles, 0);
					}
				} else {
					$source = str_replace('@@@WSSSTYLES@@@', $newfile . '@@@WSSSTYLES@@@', $source);
				}
				break;
/* no unobtrusive but external scripts exist, avoid excluded scripts */
			case 1:
				if ($this->options['page']['html_tidy'] && ($headpos = strpos($source, '</head>'))) {
					$source = substr_replace($source, $newfile, $headpos, 0);
				} elseif ($this->options['page']['html_tidy'] && ($headpos = strpos($source, '</HEAD>'))) {
					$source = substr_replace($source, $newfile, $headpos, 0);
				} else {
					$source = preg_replace("!</head>!is", $newfile . "$0", $source);
				}
/* additional check in case of non-existing </head>, insert before <body> */
				if (!strpos($source, $newfile)) {
					$source = preg_replace("!<body[^>]*>!is", $newfile . "$0", $source);
				}
				break;
/* inject merged script to the first <script> occurrence, replace WSSSCRIPT */
			case 2:
				$source = str_replace("@@@WSSSCRIPT@@@", $newfile, $source);
				break;
/* add JavaScript calls before </body> */
			case 3:
				if ($this->options['page']['html_tidy'] && ($bodypos = strpos($source, '</body>'))) {
					$source = substr_replace($source, $newfile, $bodypos, 0);
				} elseif ($this->options['page']['html_tidy'] && ($bodypos = strpos($source, '</BODY>'))) {
					$source = substr_replace($source, $newfile, $bodypos, 0);
				} else {
					$source = preg_replace("!</body>!is", $newfile . "$0", $source);
/* a number of engines doesn't set </body> */
					if (!strpos($source, $newfile)) {
						$source .= $newfile;
					}
				}
/* remove JS file marker from content */
				$source = str_replace('@@@WSSSCRIPT@@@', '', $source);
				break;
/* place second CSS call to onDOMready */
			case 4:
				$include = '<script type="text/javascript">function _weboptimizer_load(){var d=document,l=d.createElement("link");l.rel="stylesheet";l.type="text/css";l.href="'. $href .'";d.getElementsByTagName("head")[0].appendChild(l);window._weboptimizer_load=function(){}}(function(){var d=document;if(d.addEventListener){d.addEventListener("DOMContentLoaded",_weboptimizer_load,false)}';
				if (!empty($this->ua_mod) && substr($this->ua_mod, 3, 1) < 8) {
					$include .= 'd.write("\x3cscript id=\"_weboptimizer\" defer=\"defer\" src=\"\">\x3c\/script>");(d.getElementById("_weboptimizer")).onreadystatechange=function(){if(this.readyState=="complete"){setTimeout(function(){if(typeof _weboptimizer_load!=="undefined"){_weboptimizer_load()}},0)}};';
				}
				$include .= 'if(/WebK/i.test(navigator.userAgent)){var wssload=setInterval(function(){if(/loaded|complete/.test(document.readyState)){clearInterval(wssload);if(typeof _weboptimizer_load!=="undefined"){_weboptimizer_load()}}},10)}window[/*@cc_on !@*/0?"attachEvent":"addEventListener"](/*@cc_on "on"+@*/"load",_weboptimizer_load,false)}());document.write("\x3c!--");</script>' . $newfile . '<!--[if IE]><![endif]-->';
				$source = str_replace("@@@WSSSTYLES@@@", "@@@WSSSTYLES@@@" . $include, $source);
				break;
		}
		return $source;
	}

	/**
	* Include compressed JS or CSS into source and return it
	*
	**/
	function do_include ($options, $source, $cachedir, $external_array) {
		$cachedir_relative = $options['cachedir_relative'];
		$handlers = '';
/* If only one script found */
		if (!is_array($external_array)) {
			$external_array = array($external_array);
		}
		if (empty($options['file'])) {
/* Glue scripts' content / filenames */
			$scripts_string = $this->https;
			foreach ($external_array as $script) {
				$scripts_string .= (empty($script['source']) ? '' : $script['source']) . (empty($script['content']) ? '' : $script['content']);
			}
/* Get date string to make hash */
			$datestring = $this->get_file_dates($external_array, $options);
/* get options string */
			$optstring = '';
			foreach ($options as $key => $value) {
				if (is_array($value)) {
					$optstring .= '_' . implode('_', $value);
				} else {
					$optstring .= '_' . $value;
				}
			}
/* Get the cache hash, restrict by 10 symbols */
			$cache_file = substr(md5($scripts_string . $datestring . $optstring), 0, 10);
/* use general file if it has been defined */
		} else {
			$cache_file = $options['file'];
		}
		$cache_file = urlencode($cache_file . $this->ua_mod);
		$physical_file = $options['cachedir'] . $cache_file . "." . $options['ext'];
		$external_file = 'http' . $this->https . '://' .
			(empty($options['host']) ?
				$_SERVER['HTTP_HOST'] :
				(empty($options['https']) ?
				$options['host'] :
				$options['https'])) .
			str_replace($this->options['document_root'], "/", $physical_file);
		if (empty($this->options['cache_version'])) {
			if (@is_file($physical_file)) {
				$timestamp = @filemtime($physical_file);
			} else {
				$timestamp = 0;
			}
		} else {
			$timestamp = $this->options['cache_version'];
		}
/* add BackgroundImageCache for IE6 to prevent CSS Sprites blinking */
		if ($this->ua_mod === '.ie6'&& !empty($options['css_sprites'])) {
			$source = $this->include_bundle($source, '<script type="text/javascript">try{document.execCommand("BackgroundImageCache",false,true)}catch(e){}</script>', $handlers, $cachedir, 1);
		}
/* Check if the cache file exists */
		if ($timestamp) {
/* Put in locations and remove certain scripts */
			if (!is_array($external_array)) {
				$external_array = array($external_array);
			}
			$source = $this->_remove_scripts($external_array, $source,
				$options['header'] != 'css' ? $options['header'] == 'javascript' && !$options['external_scripts_head_end'] ? 1 : 0 : 2);
/* Create the link to the new file with data:URI / mhtml */
			if (!empty($options['data_uris_separate']) && (!empty($this->options['cache_version']) || @is_file($physical_file . '.' . $options['ext']))) {
				$newfile = $this->get_new_file($options, $cache_file, $timestamp, '.' . $options['ext']);
/* raw include right after the main CSS file */
				if (empty($options['data_uris_domloaded'])) {
					$source = $this->include_bundle($source, $newfile, $handlers, $cachedir_relative, 0);
/* include via JS loader to provide fast flush of content */
				} else {
					$source = $this->include_bundle($source, $newfile, $handlers, $cachedir_relative, 4, $this->get_new_file_name($options, $cache_file, $timestamp, '.' . $options['ext']));
				}
			}
			$newfile = $this->get_new_file($options, $cache_file, $timestamp);
			$source = $this->include_bundle($source, $newfile, $handlers, $cachedir_relative, $options['unobtrusive_body'] ? 3 : ($options['header'] == 'javascript' && $options['external_scripts_head_end'] ? 1 : ($options['header'] == 'javascript' ? 2 : 0)));
/* fix for some JS libraries to load resrouces dynamically */
			if (!empty($this->shadowbox_base) &&
				$options['header'] == 'javascript' &&
				!$options['inline_scripts']) {
					$source = str_replace('Shadowbox.init(', 'Shadowbox.path="' .
						$this->shadowbox_base . '";Shadowbox.init(', $source);
			}
			return $source;
		}
		foreach ($this->libraries as $klass => $library) {
			if (!class_exists($klass, false)) {
				require($options['installdir'] . 'libs/php/' . $library);
			}
		}
/* If the file didn't exist, continue. Get files' content */
		if (!empty($options['dont_check_file_mtime'])) {
			$this->get_script_content($options['tag']);
/* Replace existing array with the new content */
			$external_array = array();
			foreach ($this->initial_files as $key => $file) {
				if ($file['tag'] == $options['tag']) {
					$external_array[] = $file;
				}
			}
		}
/* Delete old files from cache */
		if (!empty($this->options['days_to_delete'])) {
			$dir = @getcwd();
			@chdir($options['cachedir']);
			foreach (glob('*.' . $options['ext']) as $file) {
				if (!in_array($file, array('wo.cookie.php', 'wo.static.php', 'yass.loader.js', 'webo-site-speedup.php')) &&
					$this->time - filemtime($file) >
					$this->options['days_to_delete'] * 86400) {
						@unlink($file);
				}
			}
			foreach (glob('*.' . $options['ext'] . '.gz') as $file) {
				if ($this->time - filemtime($file) >
					$this->options['days_to_delete'] * 86400) {
						@unlink($file);
				}
			}
			foreach (glob('*.png') as $file) {
				if (!in_array($file, array('webo-site-speedup88.png', 'webo-site-speedup125.png', 'webo-site-speedup161.png', 'webo-site-speedup250.png')) &&
					$this->time - filemtime($file) >
					$this->options['days_to_delete'] * 86400) {
						@unlink($file);
				}
			}
			foreach (glob('*.gif') as $file) {
				if ($file != '0.gif' && $this->time - filemtime($file) >
					$this->options['days_to_delete'] * 86400) {
						@unlink($file);
				}
			}
			foreach (glob('*.jpg') as $file) {
				if ($this->time - filemtime($file) >
					$this->options['days_to_delete'] * 86400) {
						@unlink($file);
				}
			}
			@chdir($dir);
		}
/* Create file */
		$contents = "";
		if (is_array($external_array)) {
/* can't simply merge&minify if we need to exclude some files */
			if (empty($options['minify_exclude']) || empty($options['minify'])) {
				foreach($external_array as $key => $info) {
/* Get the code */
					if ($file_contents = $info['content']) {
						if (!empty($options['minify_try'])) {
							$contents .= 'try{';
						}
						$contents .= $file_contents . "\n";
						if (!empty($options['minify_try'])) {
							$contents .= '}catch(e){';
							if (!empty($info['file'])) {
								$contents .= 'document.write("' .
									str_replace(array('<', '"', "\n", "\r"), array('\x3c', '\"', ' ', ''), $info['source']) .
									'")';
							}
							$contents .= '}';
						}
					}
				}
			}
			if ($options['tag'] === 'link' && !empty($options['include_code'])) {
				$contents .= str_replace("<br/>", "", $options['include_code']);
			}
			$source = $this->_remove_scripts($external_array, $source,
				$options['header'] != 'css' ? $options['header'] == 'javascript' && !$options['external_scripts_head_end'] ? 1 : 0 : 2);
			if ($options['css_sprites'] || ($options['data_uris'] && empty($this->ua_mod)) || ($options['mhtml'] && !empty($this->ua_mod))) {
				$options['css_sprites_partly'] = 0;
				$remembered_data_uri = $options['data_uris'];
				$remembered_mhtml = $options['mhtml'];
				$options['data_uris'] = $options['mhtml'] = 0;
/* start new PHP process to create CSS Sprites */
				if (!empty($this->web_optimizer_stage) && !(($this->web_optimizer_stage - 13)%15) && $this->web_optimizer_stage < 85) {
/* skip 2 minor stages if we not in the first major stage */
					$delta = $this->web_optimizer_stage > 20 ? 6 : 0;
					header('Location: ' . $this->chained_redirect .
						'?web_optimizer_stage=' . 
							($this->web_optimizer_stage + $delta) .
						'&username=' .
							$this->username .
						'&password=' .
							$this->password .
						'&auto_rewrite=' .
							$this->auto_rewrite .
						'&cache_version=' .
							$this->cache_version .
						'&web_optimizer_debug=1');
					while (@ob_end_clean());
					die();
/* prepare first 4 Sprites */
				} elseif (!empty($this->web_optimizer_stage) && !(($this->web_optimizer_stage - 16)%15) && $this->web_optimizer_stage < 85) {
					$options['css_sprites_partly'] = 1;
					$this->convert_css_sprites($contents, $options, $external_file);
					header('Location: ' . $this->chained_redirect .
						'?web_optimizer_stage=' . 
							$this->web_optimizer_stage .
						'&username=' .
							$this->username .
						'&password=' .
							$this->password .
						'&auto_rewrite=' .
							$this->auto_rewrite .
						'&cache_version=' .
							$this->cache_version .
						'&web_optimizer_debug=1');
					while (@ob_end_clean());
					die();
				} elseif (!empty($this->web_optimizer_stage) && !(($this->web_optimizer_stage - 19)%15) && $this->web_optimizer_stage < 85) {
/* Create CSS Sprites in CSS dir */
					$this->convert_css_sprites($contents, $options, $external_file);
/* start new PHP process to create data:URI */
					header('Location: ' . $this->chained_redirect .
						'?web_optimizer_stage=' . 
							($this->web_optimizer_stage + 1) .
						'&username=' .
							$this->username .
						'&password=' .
							$this->password .
						'&auto_rewrite=' .
							$this->auto_rewrite .
						'&cache_version=' .
							$this->cache_version .
						'&web_optimizer_debug=1');
					while (@ob_end_clean());
					die();
				} else {
/* we created all Sprites -- ready for data:URI + mhtml */
					$options['data_uris'] = $remembered_data_uri;
					$options['mhtml'] = $remembered_mhtml;
/* create correct resource file name for data:URI / mhtml inclusion */
					$resource_file = $external_file;
					if (!empty($options['data_uris_separate'])) {
						$resource_file = $this->get_new_file_name($options, $resource_file, $this->time, '.' . $options['ext']);
					}
					if (!empty($options['minify_with']) && $options['minify_with'] == 'tidy') {
						$minified_content_array = $this->convert_css_sprites($contents, $options, $resource_file);
					} else {
						$minified_content_array = $this->convert_data_uri($contents, $options, $resource_file);
					}
					$minified_content = $minified_content_array[0];
					$minified_resource = $minified_content_array[1];
/* write data:URI / mhtml content */
					if (!empty($minified_resource) && !empty($options['data_uris_separate'])) {
/* Allow for gzipping and headers */
						if (($options['gzip'] || $options['far_future_expires']) && !empty($minified_resource)) {
							$minified_resource = $this->gzip_header[$options['header']] . $minified_resource;
						}
						$this->write_file($physical_file . '.' . $options['ext'], $minified_resource, in_array($options['ext'], array('css', 'js')), 'text/css');
/* create static gzipped versions for static gzip in nginx, Apache */
						if ($options['ext'] == 'css') {
							$c = @gzencode($minified_resource, $options['gzip_level'], FORCE_GZIP);
							if (!empty($c)) {
								$this->write_file($physical_file . '.' . $options['ext']. '.gz', $c);
							}
						}
						$newfile = $this->get_new_file($options, $cache_file, $this->time, '.' . $options['ext']);
/* raw include right after the main CSS file */
						if (empty($options['data_uris_domloaded'])) {
							$source = $this->include_bundle($source, $newfile, $handlers, $cachedir_relative, 0);
/* include via JS loader to provide fast flush of content */
						} else {
							$source = $this->include_bundle($source, $newfile, $handlers, $cachedir_relative, 4, $this->get_new_file_name($options, $cache_file, $this->time, '.' . $options['ext']));
						}
					} elseif (!empty($minified_content)) {
						$ie = in_array($this->ua_mod, array('.ie5', '.ie6', '.ie7'));
						$minified_content .= $minified_resource;
					}
				}
				if (!empty($minified_content)) {
					$contents = $minified_content;
				}
			}
		}
		if (!empty($contents)) {
/* Allow for minification of javascript */
			if ($options['header'] == "javascript" && $options['minify']) {
				$contents = $this->minify_javascript($contents, $options);
			}
/* we need to exclude some files from ninify */
		} elseif(!empty($options['minify_exclude']) && $options['minify']) {
			$exclude_list = explode(" ", trim($options['minify_exclude']));
			foreach($external_array as $key => $info) {
/* Get the code */
				if ($file_contents = $info['content']) {
					$content = '';
					if (!empty($options['minify_try'])) {
						$content .= 'try{';
					}
					$content .= $file_contents . "\n";
					if (!empty($options['minify_try'])) {
						$content .= '}catch(e){';
						if (!empty($info['file'])) {
							$content .= 'document.write("' .
								str_replace(array('<', '"', "\n", "\r"), array('\x3c', '\"', ' ', ''), $info['source']) .
								'")';
						}
						$content .= '}';
					}
					if ($options['header'] == "javascript" &&
						!in_array(preg_replace("@.*/@", "", $info['file']), $exclude_list)) {
							$content = $this->minify_javascript($content, $options);
					}
					$contents .= $content;
				}
			}
		}
		if (!empty($contents)) {
/* fix for some JS libraries to load resrouces dynamically */
			if (!empty($this->shadowbox_base) &&
				$options['header'] == 'javascript' &&
				$options['inline_scripts']) {
					$contents = str_replace('Shadowbox.init(', 'Shadowbox.path="' .
						$this->shadowbox_base . '";Shadowbox.init(', $contents);
			}
/* Allow for minification of CSS, CSS Sprites uses CSS Tidy -- already minified CSS */
			if ($options['minify_with'] == 'basic' &&
				!empty($options['minify']) &&
				empty($options['css_sprites'])) {
/* Minify CSS */
				$contents = $this->minify_text($contents);
			}
/* Allow for gzipping and headers */
			if ($options['gzip'] || $options['far_future_expires']) {
				$contents = $this->gzip_header[$options['header']] . $contents;
			}
			if (!empty($contents)) {
/* Write to cache and display */
				$this->write_file($physical_file, $contents, in_array($options['ext'], array('css', 'js')), $options['ext'] == 'js' ? 'application/javascript' : 'text/css');
/* create static gzipped versions for static gzip in nginx, Apache */
				if ($options['ext'] == 'css' || $options['ext'] == 'js') {
					$c = @gzencode($contents, $options['gzip_level'], FORCE_GZIP);
					if (!empty($c)) {
						$this->write_file($physical_file . '.gz', $c);
					}
				}
/* Create the link to the new file */
				$newfile = $this->get_new_file($options, $cache_file, $this->time);
				$source = $this->include_bundle($source, $newfile, $handlers, $cachedir_relative, $options['unobtrusive_body'] ? 3 : ($options['header'] == 'javascript' && $options['external_scripts_head_end'] ? 1 : ($options['header'] == 'javascript' ? 2 : 0)));
			}
		}
		return $source;
	}
	
	/**
	* Minifies JavaScript code according to current options
	*
	*/

	function minify_javascript ($code, $options) {
		$minified_code = '';
		if ($options['minify_with'] == 'packer') {
			$this->packer = new JavaScriptPacker($code, 'Normal', false, false);
			$minified_code = $this->packer->pack();
		} elseif ($options['minify_with'] == 'yui' ) {
			$this->yuicompressor = new YuiCompressor($options['cachedir'],
				$options['installdir'], $this->charset);
			$minified_code = $this->yuicompressor->compress($code);
		}
		if ($options['minify_with'] == 'jsmin' ||
			(!empty($options['minify_with']) &&
			empty($minified_code))) {
					$this->jsmin = new JSMin($code);
					$minified_code = $this->jsmin->minify($code);
		}
		if (!empty($minified_code)) {
			$code = $minified_code;
		}
		return $code;
	}

	/**
	* Replaces scripts calls or css links in the source with a marker
	*
	*/
	function _remove_scripts ($external_array, $source, $mark = false) {
		$replacement = $mark ? $mark > 1 ? '@@@WSSSTYLES@@@' : '@@@WSSSCRIPT@@@' : '';
		if (is_array($external_array)) {
			foreach ($external_array as $key => $value) {
/* Remove script, replace the first one with the mark to insert merged script */
				$source = str_replace($value['source'], $replacement, $source);
				if ($mark) {
					$replacement = '';
				}
			}
		}
		return $source;
	}

	/**
	* Returns the HTML code for our new compressed file
	*
	**/
	function get_new_file ($options, $cache_file, $timestamp = false, $add = false) {
		$newfile = '<' . $options['tag'] .
			' type="' . $options['type'] . '" ' .
			$options['src'] . '="' . $this->get_new_file_name($options, $cache_file, $timestamp, $add) . '"'.
/* IE7- don't understand stylesheet nofollow in rel */
			((empty($this->ua_mod) && $options['ext'] == 'php') || !empty($options['rel']) ? ' rel="' .
				(empty($options['rel']) ? '' : $options['rel']) .
				(!empty($options['rel']) && $options['ext'] == 'php' && empty($this->ua_mod) ? ' ' : '') .
				(empty($this->ua_mod) && $options['ext'] == 'php' ? 'nofollow' : '') . '"' : '') . 
			(empty($options['self_close']) ? '></' . $options['tag'] . '>' : (empty($this->xhtml) ? '>' : '/>'));
		return $newfile;
	}

	/**
	* Returns the filename for our new compressed file
	*
	**/
	function get_new_file_name ($options, $cache_file, $timestamp = false, $add = false) {
		$timestamp = $options['far_future_expires_php'] ? $timestamp : false;
		return (empty($options['host']) ? '' : '//' . $options['host']) .
			$options['cachedir_relative'] . 
			$cache_file .
			($add ?  '.' . $options['ext'] : '') .
			($timestamp && $options['far_future_expires_rewrite'] ? '.wo' . $timestamp : '') .
			($add ? $add : '.' . $options['ext']) .
			($timestamp && !$options['far_future_expires_rewrite'] ? '?' . $timestamp : '');
	}

	/**
	* Returns the last modified dates of the files being compressed
	* In this way we can see if any changes have been made
	**/
	function get_file_dates ($files, $options) {
/* option added by janvarev */
		if (!empty($options['dont_check_file_mtime']) && strlen($this->lc) == 29) {
			return;
		}
		$dates = false;
		if (!empty($files) && is_array($files)) {
			foreach($files AS $key => $value) {
				if (!empty($value['file'])) {
					$value['file'] = $this->get_file_name($value['file']);
					if (@file_exists($value['file'])) {
						$thedate = filemtime($value['file']);
						$dates[] = $thedate;
					}
				}
			}	
		}
		if (is_array($dates)) {
			return implode(".", $dates);
		}
		return;
	}

	/**
	* Get full pathname for the given file
	*
	**/
	function get_file_name ($file) {
		if (is_array($file) && count($file)>0) {
			$file = $file[0];
		}
		$file = $this->strip_querystring(preg_replace("@^https?://(www\.)?" . $this->host_escaped . "/+@", "/", $file));
		if (substr($file, 0, 1) == "/") {
			return $this->view->prevent_trailing_slash($this->options['document_root']) . $file;
		} else {
			return $this->view->paths['full']['current_directory'] . $file;
		}
	}

	/**
	* Resursively resolve all @import in CSS files and get files' content
	* The second param marks inline styles case
	*
	**/
	function resolve_css_imports ($src, $inline = false) {
		$content = false;
		$file = '';
		if (!$inline) {
			$file = $this->get_file_name($src);
/* dynamic file */
			if (!preg_match("!\.css$!is", $file)) {
				$dynamic_file = $src;
/* touch only non-external scripts */
				if (!strpos($dynamic_file, "://") || strpos($dynamic_file, '//') === 0) {
					$dynamic_file = "http://" . $_SERVER['HTTP_HOST'] . $this->convert_path_to_absolute($dynamic_file, array('file' => $file), true);
				}
				$file = $this->options['css']['cachedir'] . $this->get_remote_file($this->resolve_amps($dynamic_file), 'link');
			}
			if (@is_file($file)) {
				$content = @file_get_contents($file);
			}
		} else {
			$content = $src;
		}
/* remove BOM */
		$content = $this->resolve_amps($content);
		if (@is_file($file) || $inline) {
/* remove commented @import. First of all glue CSS files, optimiza only secondly */
			$content = preg_replace("!/\*\s*@import.*?\*/!is", "", $content);
/* new RegExp from xandrx */
			preg_match_all('!@import\s*(url)?\s*\(?([^;]+?)\)?;!i', $content, $imports, PREG_SET_ORDER);
			if (is_array($imports)) {
				foreach ($imports as $import) {
					$src = false;
					if (isset($import[2])) {
						$src = $import[2];
						$src = trim($src, '\'" ');
					}
					if (strpos($src, "//") && !preg_match('@//(www\.)?' . $this->host_escaped . '/@', $src)) {
						$src = $this->get_remote_file($src);
					}
					if ($src) {
						$saved_directory = $this->view->paths['full']['current_directory'];
						$this->view->paths['full']['current_directory'] = preg_replace("/[^\/]+$/", "", $file);
/* start recursion */
						$content = str_replace($import[0], $this->convert_paths_to_absolute($this->resolve_css_imports($src), array('file' => str_replace($this->options['document_root'], "/", $this->get_file_name($src)))), $content);
/* return remembed directory */
						$this->view->paths['full']['current_directory'] = $saved_directory;
					}
				}
			}
		}
		return $content;
	}

	/**
	* Gets an array of scripts/css files to be processed
	*
	**/
	function get_script_array () {
/* get head with all content */
		$this->get_head();
		$curl = function_exists('curl_init');
		if ($this->options['javascript']['minify'] || $this->options['javascript']['gzip'] || $this->options['page']['parallel_javascript']) {
			if (empty($this->options['javascript']['minify_body'])) {
				$toparse = $this->head;
			} else {
				$toparse = $this->body;
			}
/* find all scripts from head */
			$regex = "!(<script[^>]*>)(.*?</script>)!is";
			preg_match_all($regex, $toparse, $matches, PREG_SET_ORDER);
			if (!empty($matches)) {
				foreach($matches as $match) {
					$file = array(
						'tag' => 'script',
						'source' => $match[0],
						'content' => preg_replace("@(<script[^>]*>|</script>)@is", "", $match[0])
					);
					preg_match_all("@src\s*=\s*(?:\"([^\"]+)\"|'([^']+)'|([\S]+))@is", $match[1], $variants, PREG_SET_ORDER);
					if (is_array($variants)) {
						foreach ($variants as $variant_type) {
							$variant_type[1] = ($variant_type[1] === '') ? (($variant_type[2] === '') ? str_replace('>', '', $variant_type[3]) : $variant_type[2]) : $variant_type[1];
							$file['file'] = $this->convert_basehref(trim($this->strip_querystring($variant_type[1])));
							$file['file_raw'] = $variant_type[1];
						}
					}
/* skip external files if option is disabled */
					if (($this->options['javascript']['external_scripts'] && $curl) ||
						(!empty($file['file']) && preg_match("@(index\.php/|\.js$)@i", $file['file']) &&
							(!strpos($file['file'], '//') ||
							preg_match("@//(www\.)?" . $this->host_escaped . "/@is", $file['file']))) ||
						(empty($file['file']) &&
							$this->options['javascript']['inline_scripts'])) {
								$this->initial_files[] = $file;
/* fix shadowbox loader */
								if (!empty($file['file']) && strpos($file['file'], 'shadowbox.js')) {
									$this->shadowbox_base = preg_replace("@https?://" .
										$this->host_escaped . "/(.*/)[^/]+@",
										(empty($this->options['javascript']['host']) ?
										'' : '//' . $this->options['javascript']['host']) . "/$1", $file['file']);
								}
/* fix scriptaculous loader */
								if (!empty($file['file']) && ($acpos = strpos($variant_type[1], '?load='))) {
									$scripts = explode(',', substr($variant_type[1], $acpos + 6));
									$acbase = preg_replace("@/[^/]+$@", '/', $file['file']);
									foreach ($scripts as $script) {
										$acfile = array(
											'tag' => 'script',
											'source' => '',
											'content' => '',
											'file' => $acbase . $script . '.js'
										);
										$this->initial_files[] = $acfile;
									}
								}
					}
				}
			}
		}
		if ($this->options['css']['minify'] || $this->options['css']['gzip'] || $this->options['page']['parallel_css']) {
			if (empty($this->options['css']['minify_body'])) {
				$toparse = $this->head;
			} else {
				$toparse = $this->body;
			}
/* find all CSS links from head and inine styles */
			if (empty($this->options['css']['inline_scripts']) && $this->options['page']['html_tidy'] && !strpos($toparse, '<style') && !strpos($toparse, '<STYLE')) {
				$regex = "!(<link[^>]+rel\\s*=\\s*(\"stylesheet\"|'stylesheet'|stylesheet)[^>]*>)!is";
			} else {
				$regex = "!(<link[^>]+rel\\s*=\\s*(\"stylesheet\"|'stylesheet'|stylesheet)[^>]*>|<style[^>]*>.*?</style>)!is";
			}
			preg_match_all($regex, $toparse, $matches, PREG_SET_ORDER);
			if (!empty($matches)) {
				foreach($matches as $match) {
					$file = array(
						'tag' => 'link',
						'source' => $match[0],
						'content' => preg_replace("@(<link[^>]+>|<style[^>]*>|<\/style>)@is", "", $match[0]),
					);
					preg_match_all("@(media|href)\s*=\s*(?:\"([^\"]*)\"|'([^']*)'|([^\s>]*))@is", $match[0], $variants, PREG_SET_ORDER);
					if (is_array($variants)) {
						foreach($variants as $variant_type) {
							$variant_type[1] = strtolower($variant_type[1]);
							$variant_type[2] = empty($variant_type[2]) ? (empty($variant_type[3]) ? (empty($variant_type[4]) ? '' : $variant_type[4]) : $variant_type[3]) : $variant_type[2];
							switch ($variant_type[1]) {
								case "href":
									$file['file'] = $this->convert_basehref(trim($this->strip_querystring($variant_type[2])));
									$file['file_raw'] = $variant_type[2];
									break;
								default:
/* skip media="all|screen" to prevent Safari bug with @media all{} and @media screen{} */
									if ($variant_type[1] != 'media' || ($variant_type[1] == 'media' && !preg_match("@^(all|screen|''|\"\")$@i", $variant_type[2]))) {
										$file[$variant_type[1]] = $variant_type[2];
									}
									break;
							}
						}
					}
/* skip external files if option is disabled */
					if (($this->options['css']['external_scripts'] && $curl) ||
						(!empty($file['file']) && preg_match("@(index\.php/|\.css$)@i", $file['file']) &&
							(!strpos($file['file'], '//') ||
							preg_match("@//(www\.)?" . $this->host_escaped . "/@is", $file['file']))) ||
						(empty($file['file']) && $this->options['css']['inline_scripts'])) {
							$this->initial_files[] = $file;
					}
				}
			}
		}
/* strange thing: array is filled even if string is empty */
		$excluded_scripts_css = explode(" ", $this->options['css']['external_scripts_exclude']);
		$excluded_scripts_js = explode(" ", $this->options['javascript']['external_scripts_exclude']);
		if (is_array($this->initial_files)) {
/* enable caching / gzipping proxy? */
			$rewrite_css = ($this->options['page']['far_future_expires_external'] ||
				$this->options['css']['gzip']);
			$rewrite_js = ($this->options['page']['far_future_expires_external'] ||
				$this->options['javascript']['gzip']);
/* Remove empty sources and any externally linked files */
			foreach ($this->initial_files as $key => $value) {
				$use_proxy = (!$this->options['javascript']['minify'] && $value['tag'] == 'script') ||
					(!$this->options['css']['minify'] && $value['tag'] == 'link');
/* but keep CSS/JS w/o src to merge into unobtrusive loader, also exclude files from ignore_list */
				if (($value['tag'] == 'script' && ((empty($value['file']) &&
					!$this->options['javascript']['inline_scripts']) ||
					(!empty($excluded_scripts_js[0]) &&
						!empty($value['file']) &&
						in_array(preg_replace("/.*\//", "", $value['file']), $excluded_scripts_js)) ||
						(!$this->options['javascript']['minify'] && $this->options['page']['parallel_javascript']))) ||
					($value['tag'] == 'link' && ((empty($value['file']) &&
					!$this->options['css']['inline_scripts']) ||
					(!empty($excluded_scripts_css[0]) &&
						!empty($value['file'] ) &&
						in_array(preg_replace("/.*\//", "", $value['file']), $excluded_scripts_css)) ||
						(!$this->options['css']['minify'] && $this->options['page']['parallel_css'])))) {
/* just skip them */
					unset($this->initial_files[$key]);
					$use_proxy = 1;
/* rewrite skipped file with CDN host */
					if (!empty($value['file']) &&
						(($value['tag'] == 'link' && $this->options['page']['parallel_css']) ||
						($value['tag'] == 'script' && $this->options['page']['parallel_javascript'])) &&
						(preg_match("@//(www\.)?" . $this->host_escaped . "/+@", $value['file']) ||
						(substr($value['file'], 0, 1) == '/' && substr($value['file'], 1, 1) != '/'))) {
							$host = $value['tag'] == 'link' ?
								$this->options['css']['host'] : 
								$this->options['javascript']['host'];
							$new_src = (empty($host) ? "" : "//" . $host) .
								preg_replace("@https?://(www\.)?" .
								$this->host_escaped .
								"/+@", "/", $value['file']);
							$new_script = str_replace($value['file'],
								$new_src, $value['file_raw']);
							$this->content = str_replace($value['file_raw'],
								$new_script, $this->content);
							$use_proxy = 0;
					}
/* rewrite skipped file with caching proxy, skip dynamic files */
				}
				if ($use_proxy && !empty($value['file']) &&
					(($value['tag'] == 'link' && $rewrite_css) ||
					($value['tag'] == 'script' && $rewrite_js)) &&
					!preg_match("!\.php$!", $value['file'])) {
						$value['file'] = preg_replace("@https?://(www\.)?" .
							$this->host_escaped . "/+@", "/", $value['file']);
						$new_src =
							$this->options['page']['cachedir_relative'] . 
							'wo.static.php?' . $this->convert_path_to_absolute($value['file'],
							array('file' => $_SERVER['REQUEST_URI']));
						$new_script = str_replace($value['file'],
							$new_src, $value['file_raw']);
						$this->content = str_replace($value['file_raw'],
							$new_script, $this->content);
				}
			}
/* skip mining files' content if don't check MTIME */
			if (empty($this->options['javascript']['dont_check_file_mtime']) ||
				strlen($this->lc) != 29) {
					$this->get_script_content();
			}
		}
	}

	/**
	* Gets an content for array of scripts/css files
	*
	**/
	function get_script_content ($tag = false) {
/* to get inline values */
		$last_key = array();
/* to get inline values on empty non-inline */
		$last_key_flushed = array();
		$stored = array();
/* duplicates spots */
		$duplicates = array(
/* jQuery */
			array(
				'regexp' => 'jquery([v0-9\.\-\[\]])*(pack|min)?\.(js|php)(\.gz)?',
				'exists' => 0
			),
/* Prototype */
			array(
				'regexp' => 'prototype([rev0-9\.\-_])*(packer|min|lite)?\.(js|php)(\.gz)?',
				'exists' => 0
			),
/* MooTools */
			array(
				'regexp' => 'mootools(_release)?([xv0-9\.\-_])*(core-yc|core|yui-compressed|comp|min)?\.(js|php)(\.gz)?',
				'exists' => 0
			)
		);
		if (is_array($this->initial_files)) {
			foreach($this->initial_files as $key => $value) {
/* don't touch all files -- just only requested ones */
				if (!$tag || $value['tag'] == $tag) {
					if (!empty($value['file']) && strlen($value['file']) > 7 && strpos($value['file'], "://")) {
/* exclude files from the same host */
						if(!preg_match("@//(www\.)?". $this->host_escaped . "@s", $value['file'])) {
/* don't get actual files' content if option isn't enabled */
								if ($this->options[$value['tag'] == 'script' ? 'javascript' : 'css']['external_scripts']) {
/* get an external file */
								if (!preg_match("/\.(css|js)$/is", $value['file'])) {
/* dynamic file */
									$file = $this->get_remote_file($this->resolve_amps($value['file_raw']), $value['tag']);
/* static file */
								} else {
									$file = $this->get_remote_file($value['file'], $value['tag']);
								}
								if (!empty($file)) {
									$value['file'] = $this->initial_files[$key]['file'] = $this->options['javascript']['cachedir_relative'] . $file;
								} else {
									unset($this->initial_files[$key]);
								}
							} else {
								if (empty($value['content'])) {
									unset($this->initial_files[$key]);
								}
							}
						} else {
							$value['file'] = preg_replace("!https?://(www\.)?".
								$this->host_escaped . "/+!s", "/", $value['file']);
						}
					}
					$content_from_file = '';
					if (!empty($value['file'])) {
/* convert dynamic files to static ones */
						if (!preg_match("/\.(css|js)$/is", $value['file']) || strpos($value['file'], 'index.php/')) {
							$dynamic_file = $value['file_raw'];
/* touch only non-external scripts */
							if (!strpos($dynamic_file, "://")) {
								$dynamic_file = "http://" . $_SERVER['HTTP_HOST'] . $this->convert_path_to_absolute($dynamic_file, array('file' => $value['file']), true);
							}
							$static_file = ($this->options[$value['tag'] == 'script' ? 'javascript' : 'css']['cachedir']) . $this->get_remote_file($this->resolve_amps($dynamic_file), $value['tag']);
							if (@is_file($static_file)) {
								$value['file'] = str_replace($this->options['document_root'], "/", $static_file);
							} else {
								unset($value['file']);
							}
						}
						if ($value['tag'] == 'link') {
/* recursively resolve @import in files */
							$content_from_file = (empty($value['media']) ? "" : "@media " . $value['media'] . "{") .
									$this->resolve_css_imports($value['file']) .
								(empty($value['media']) ? "" : "}");
/* convert CSS images' paths to absolute */
							$content_from_file = $this->convert_paths_to_absolute($content_from_file, array('file' => $value['file']));
						} else {
							$content_from_file = @file_get_contents($this->get_file_name($value['file']));
						}
/* remove duplicates */
						if ($value['tag'] == 'script' &&
							$this->options['javascript']['remove_duplicates']) {
								foreach ($duplicates as $k => $duplicate) {
									if (preg_match("@" . $duplicate['regexp'] . "$@is", $value['file'])) {
										if ($duplicate['exists']) {
											$content_from_file = '';
										} else {
											$duplicates[$k]['exists'] = 1;
										}
									}
								}
						}
					}
/* remove BOM */
					$content_from_file = str_replace('﻿', '', $content_from_file);
/* don't delete any detected scripts from array -- we need to clean up HTML page from them */
					if (empty($value['file']) && (empty($last_key[$value['tag']]) || $key != $last_key[$value['tag']])) {
/* glue inline and external content */
						if (($this->options['javascript']['inline_scripts'] && $value['tag'] == 'script') || ($this->options['css']['inline_scripts'] && $value['tag'] == 'link')) {
/* resolve @import from inline styles */
							if ($value['tag'] == 'link') {
								$value['content'] = (empty($value['media']) ? "" : "@media " . $value['media'] . "{") .
										$this->resolve_css_imports($value['content'], true) . 
									(empty($value['media']) ? "" : "}");
/* convert CSS images' paths to absolute */
								$value['content'] = $this->convert_paths_to_absolute($value['content'],
									array('file' => $this->options['document_root_relative']));
							}
							$text = (empty($value['content']) ? '' : "\n" . $value['content']);
/* if we can't add to existing tag -- store for the future */
							if (empty($last_key[$value['tag']])) {
								$stored[$value['tag']] = empty($stored[$value['tag']]) ? $text : $stored[$value['tag']] . $text;
								$last_key_flushed[$value['tag']] = $key;
							} else {
								$this->initial_files[$last_key[$value['tag']]]['content'] .= $text;
							}
/* null content not to include anywhere, we still have source code in 'source' */
							$this->initial_files[$key]['content'] = '';
						}
					} elseif (!empty($content_from_file)) {
/* don't rewrite existing content inside script tags */
						$this->initial_files[$key]['content'] = $content_from_file . (empty($value['content']) ? '' : "\n" . $value['content']);
/* add stored content before, but leave styles stored */
						if (!empty($stored[$value['tag']])) {
/* preserve order of merged content */
							if ($last_key_flushed[$value['tag']] < $key) {
								$this->initial_files[$key]['content'] = $stored[$value['tag']] . "\n" . $this->initial_files[$key]['content'];
							} else {
								$this->initial_files[$key]['content'] .= "\n" . $stored[$value['tag']];
							}
							$stored[$value['tag']] = '';
						}
						$last_key[$value['tag']] = $key;
					}
				}
			}
/* check for stored content and flush it */
			foreach ($stored as $tag => $stored_content) {
				$this->initial_files[$last_key_flushed[$tag]]['content'] = $stored_content;
			}
		}
	}

	/**
	* Sets the headers to be sent in the javascript and css files
	*
	**/
	function set_gzip_headers () {
/* define encoding for HTML page */
		$this->set_gzip_encoding();
/* When will the file expire? */
		$offset = 6000000 * 60 ;
		$ExpStr = "Expires: " .
		gmdate("D, d M Y H:i:s",
		$this->time + $offset) . " GMT";
		$types = array("css", "javascript");

		foreach ($types as $type) {
/* Always send etag */
			$this->gzip_header[$type] = '<?php
			// Determine supported compression method
			if (!empty($_SERVER["HTTP_ACCEPT_ENCODING"])) {
				$_SERVER["HTTP_ACCEPT_ENCODING"] = strtolower($_SERVER["HTTP_ACCEPT_ENCODING"]);
				$gzip = strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") || !empty($_COOKIE["_wo_gzip"]);
				$xgzip = strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "x-gzip");
				$deflate = strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "deflate");
				$xdeflate = strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "x-deflate");
			} elseif (empty($_SERVER["HTTP_ACCEPT_ENCODING"]) && !empty($_COOKIE["_wo_gzip"])) {
				$gzip = 1;
			}
			// Determine used compression method
			$encoding = empty($gzip) ? (empty($xgzip) ? (empty($deflate) ? (empty($xdeflate) ? "none" : "x-deflate") : "deflate") : "x-gzip") : "gzip";
			$hash = "' . $this->time .  '-" . str_replace("x-", "", $encoding);
			header ("Etag: \"" . $hash . "\"");
?>';
/* Send 304? */
			$this->gzip_header[$type] .= '<?php

			if ((isset($_SERVER["HTTP_IF_NONE_MATCH"]) &&
				stripslashes($_SERVER["HTTP_IF_NONE_MATCH"]) == "\"" . $hash . "\"") ||
				(isset($_SERVER["HTTP_IF_MATCH"]) &&
				stripslashes($_SERVER["HTTP_IF_MATCH"]) == "\"" . $hash . "\"")) {
				// Return visit and no modifications, so do not send anything
				header ("HTTP/1.0 304 Not Modified");
				header ("Content-Length: 0");
				exit();
			}

?>';
/* ob_start ("ob_gzhandler"); */
			if (!empty($this->options[$type]['gzip'])) {
				$this->gzip_header[$type] .= '<?php
				ob_start("compress_output_option");
				function compress_output_option($contents) {
					global $encoding, $gzip, $xgzip;
					// Check for buggy versions of Internet Explorer
					if (!empty($_SERVER["HTTP_USER_AGENT"]) && !strstr($_SERVER["HTTP_USER_AGENT"], "Opera") &&
						preg_match("/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i", $_SERVER["HTTP_USER_AGENT"], $matches)) {
						$version = floatval($matches[1]);
						// IE6- can loose first 2048 bytes of gzipped content, code from Bitrix
						if ($version < 7) {
							$contents = str_repeat(" ", 2048) . "\r\n" . $contents;
						}
					}

					if (isset($encoding) && $encoding != "none")
					{
						// try to get gzipped content from file
						$extension = $gzip || $xgzip ? "gz" : "df";
						$content = @file_get_contents(__FILE__ . "." . $extension);
						$gzipped = 0;
						if (empty($content)) {
						// Send compressed contents
							if ($gzip || $xgzip) {
								if (function_exists("gzencode")) {
									$contents = gzencode($contents, '. $this->options[$type]['gzip_level'] .', FORCE_GZIP);
									$gzipped = 1;
								}
							} else {
								if (function_exists("gzdeflate")) {
									$contents = gzdeflate($contents, '. $this->options[$type]['gzip_level'] .');
									$gzipped = 1;
								}
							}
							if ($gzipped) {
								$fp = @fopen(__FILE__ . "." . $extension, "wb");
								if ($fp) {
									@fwrite($fp, $contents);
									@fclose($fp);
								}
							}
						} else {
							$contents = $content;
							$gzipped = 1;
						}
						if ($gzipped) {
							header ("Content-Encoding: " . $encoding);
						}
						header ("Content-Length: " . strlen($contents));
					}

					return $contents;

				}
?>';
			}

			if (!empty($this->options[$type]['far_future_expires_php'])) {
				$this->gzip_header[$type] .= '<?php
				header("Cache-Control: private, max-age=315360000");
				header("' . $ExpStr . '");
?>';
			}

			$this->gzip_header[$type] .= '<?php
				header("Content-type: text/' . $type .'; charset: UTF-8");
?>';

		} // end FE
	}

	/**
	* Returns a path or url without the querystring and anchor
	*
	**/
	function strip_querystring ($path) {
		if ($commapos = strpos($path, '?')) {
			$path = substr($path, 0, $commapos);
		}
		if ($numberpos = strpos($path, '#')) {
			$path = substr($path, 0, $numberpos);
		}		
		return $path;
	}

	/**
	* Minifies CSS - removes unnecessary symbols
	*
	**/
	function minify_text ($txt) {
/* Remove simple comments */
		$txt = preg_replace('!(/\*.*?\*/|^ | $)!is', '', $txt);
/* Remove line breaks, compress whitespaces */
		$txt = preg_replace('![\s\t\r\n]+!', ' ', $txt);
/* Remove spaces for }, {, ;, ,: */
		$txt = str_replace(array(' :', ': ', ' ,', ', ', ' ;', '; ', ' {', '{ ', ' }', '} '), array(':', ':', ',', ',', ';', ';', '{', '{', '}', '}'), $txt);
/* Remove excessive symbols */
		$txt = str_replace(array(' 0px', ':0px', ';}', ':0 0 0 0', ':0.', ' 0.'), array(' 0', ':0', '}', ':0', ':.', ' .'), $txt);
		return trim($txt);
	}

	/**
	* Safely trims whitespace from an HTML page
	* Adapted from smarty code http://www.smarty.net/
	**/
	function trimwhitespace ($source) {
		if (!empty($this->options['page']['minify']) ||
			!empty($this->options['page']['unobtrusive_all'])) {
				if (!empty($this->options['page']['html_tidy'])) {
					$_script_blocks = array(array(), array(), array(),
											array(), array(), array());
/* Pull out the script, textarea and pre blocks */
					$this->trimwhitespace_find('<script', '</script>',
						'@@@WBO:TRIM:SCRIPT0@@@', $source, $_script_blocks[0]);
					$this->trimwhitespace_find('<SCRIPT', '</SCRIPT>',
						'@@@WBO:TRIM:SCRIPT1@@@', $source, $_script_blocks[1]);
					$this->trimwhitespace_find('<textarea', '</textarea>',
						'@@@WBO:TRIM:SCRIPT2@@@', $source, $_script_blocks[2]);
					$this->trimwhitespace_find('<TEXTAREA', '</TEXTAREA>',
						'@@@WBO:TRIM:SCRIPT3@@@', $source, $_script_blocks[3]);
					$this->trimwhitespace_find('<pre', '</pre>',
						'@@@WBO:TRIM:SCRIPT4@@@', $source, $_script_blocks[4]);
					$this->trimwhitespace_find('<PRE', '</PRE>',
						'@@@WBO:TRIM:SCRIPT5@@@', $source, $_script_blocks[5]);
				} else {
					preg_match_all("!(<script.*?</script>|<textarea.*?</textarea>|<pre.*?</pre>)!is", $source, $match);
					$_script_blocks = $match[0];
					$source = preg_replace("!(<script.*?</script>|<textarea.*?</textarea>|<pre.*?</pre>)!is", '@@@WBO:TRIM:SCRIPT@@@', $source);
				}
		}
/* add multiple hosts or redirects for static images */
		if ((!empty($this->options['page']['parallel']) &&
				!empty($this->options['page']['parallel_hosts'])) ||
			!empty($this->options['page']['far_future_expires_rewrite']) ||
			!empty($this->options['page']['sprites'])) {
				$source = $this->add_multiple_hosts($source,
					explode(" ", $this->options['page']['parallel_hosts']),
					explode(" ", $this->options['page']['parallel_satellites']),
					explode(" ", $this->options['page']['parallel_satellites_hosts']));
		}
/* remove all leading spaces, tabs and carriage returns NOT preceeded by a php close tag */
		if (!empty($this->options['page']['minify'])) {
			$source = trim(preg_replace('/((?<!\?>)\n)[\t\s]+/m', '\1', $source));
/* replace ' >' with '>', remove \r symbols */
			$source = str_replace(array(' >', "\r"), array('>', ''), $source);
		}
/* one-strig-HTML takes about 20-50ms */
		if (!empty($this->options['page']['minify_aggressive'])) {
/* ' />' with '/>' breaks System - Cache in Joomla! */
			$source = str_replace(' />', '/>', $source);
/* replace breaks with nothing for block tags */
			$source = preg_replace("@[\s\t\r\n]*(</?)(!--|!DOCTYPE|address|area|audioscope|base|bgsound|blockquote|body|br|caption|center|col|colgroup|comment|dd|div|dl|dt|embed|fieldset|form|frame|frameset|h[123456]|head|hr|html|iframe|keygen|layer|legend|li|link|map|marquee|menu|meta|noembed|noframes|noscript|object|ol|optgroup|option|p|param|samp|select|sidebar|style|table|tbody|td|tfoot|th|title|tr|ul|var)([\s/][^>]*)?>[\s\t\r\n]+@si", "$1$2$3>", $source);
/* replace breaks with space for inline tags */
			$source = preg_replace("@(</?)(a|abbr|acronym|b|basefont|bdo|big|blackface|blink|button|cite|code|del|dfn|dir|em|font|i|img|input|ins|isindex|kbd|label|q|s|small|span|strike|strong|sub|sup|u)([\s/][^>]*)?>[\s\t\r\n]+@si", "$1$2$3> ", $source);
		}
/* replace multiple spaces with single one 
		$source = preg_replace("/[\s\t\r\n]+/", " ", $source); */
/* replace script, textarea, pre blocks */
		if (!empty($this->options['page']['unobtrusive_all']) ||
			!empty($this->options['page']['minify'])) {
				$before_body = '';
				if (!empty($this->options['page']['html_tidy'])) {
					for ($i = 0; $i < 6; $i++) {
						$_block = $_script_blocks[$i];
						if (count($_block)) {
							$before_body .=
								$this->trimwhitespace_replace("@@@WBO:TRIM:SCRIPT" .
									$i . "@@@", $_block, $source);
						}
					}
				} else {
					$before_body =
						$this->trimwhitespace_replace("@@@WBO:TRIM:SCRIPT@@@",
							$_script_blocks, $source);
				}
/* move all scripts to </body> */
				if (!empty($before_body)) {
					if (!empty($this->options['page']['html_tidy']) &&
						($bodypos = strpos($source, "</body>"))) {
							$source = substr_replace($source,
								$before_body, $bodypos, 0);
					} elseif (!empty($this->options['page']['html_tidy']) &&
						($bodypos = strpos($this->content, "</BODY>"))) {
							$source = substr_replace($source,
								$before_body, $bodypos, 0);
					} else {
/* a number of engines doesn't set </body> */
						if (!preg_match('@</body>@is', $source)) {
							$source .= $before_body;
						} else {
							$source = preg_replace('@(</body>)@is',
								$before_body . "$1", $source);
						}
					}
				}
		}
/* remove website host */
		if (!empty($this->options['page']['minify_aggressive'])) {
/* fix for base tag */
			preg_match_all("@<base[^>]+>@is", $source, $matches);
			$basetag = false;
			if (count($matches) && count($matches[0])) {
				$basetag = $matches[0][0];
			}
			if ($basetag) {
				$source = str_replace($basetag, '@@@WSSBASE@@@', $source);
			}
			$source = preg_replace("@(src|href)=(['\"])(http" .
				$this->https . "://)(www\.)?" .
				$this->host . "/*@", "$1=$2/", $source);
			if ($basetag) {
				$source = str_replace('@@@WSSBASE@@@', $basetag, $source);
			}
		}
		return $source;
	}

	/**
	* Helper function for trimwhitespace, finds all blocks
	*
	**/
	function trimwhitespace_find ($block_begin, $block_end, $spot, &$subject, &$return) {
		$len = strlen($block_end);
		while ($posbegin = strpos($subject, $block_begin)) {
			if ((($posend = strpos($subject, $block_end)) !== false) && ($posend > $posbegin)) {
				$return[] = substr($subject, $posbegin, $posend - $posbegin + $len);
				$subject = substr_replace($subject, $spot, $posbegin, $posend - $posbegin + $len);
			} else {
				break;
			}
		}
	}

	/**
	* Helper function for trimwhitespace
	*
	**/
	function trimwhitespace_replace ($search_str, $replace, &$subject) {
		$_len = strlen($search_str);
		$_pos = 0;
		$_to_body = '';
		for ($_i=0, $_count = count($replace); $_i<$_count; $_i++) {
			if (($_pos = strpos($subject, $search_str, $_pos)) !== false) {
/* move scripts to </body>. Skip dynamic styles loader */
				if (!empty($this->options['page']['unobtrusive_all']) &&
					!strpos($replace[$_i], '_weboptimizer_load')) {
					if ((!empty($this->options['html_tidy']) &&
							(strpos($replace[$_i], '<script') ||
							strpos($replace[$_i], '<SCRIPT'))) ||
						preg_match("@<script@is", $replace[$_i])) {
							$_to_body .= $replace[$_i];
							$replace[$_i] = '';
					}
				}
				$subject = substr_replace($subject, $replace[$_i], $_pos, $_len);
			} else {
				break;
			}
		}
		return $_to_body;
	}

	/**
	* Replaces one JS code in HTML with another
	* Returns string to place before </body>
	*
	**/
	function replace_unobtrusive_generic ($match_string, $stuff, $height = 0, $inline = false, $onload_mask = false, $onload_result = false) {
		$return = '';
		$initial_height = empty($height) ? 0 : $height;
		$onload = !empty($this->options['page']['unobtrusive_onload']) &&
			$onload_mask && $onload_result;
		preg_match_all($match_string, $this->content, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach ($matches as $key => $value) {
				$height = $initial_height;
				if (empty($height)) {
/* try to calculate height for AdWords */
					switch ($stuff) {
						case 'gw':
							$height = round(substr($value[0], strpos($value[0], 'google_ad_height =') + 18, 5));
							break;
						case 'aa':
							$height = round(substr($value[0], strpos($value[0], 'amazon_ad_height = "') + 20, 5));
							break;
						case 'cp':
							$pos = strpos($value[0], 'thumb_size:') + 11;
							$posend = strpos($value[0], ',', $pos);
							$dims = explode('x', str_replace(array("'", " ", '"'), array(), substr($value[0], $pos, $posend)));
							$height = round($dims[1]);
							break;
						case 'if':
						case 'IF':
							if (preg_match("@height\s*=@is", $value[0])) {
								$height = round(preg_replace("@.*height\s*=[\s'\"](.*)[\s'\"]@", "$1", $value[0]));
							}
							break;
					}
				}
/* count param for str_replace available only in PHP5 */
				$pos = strpos($this->content, $value[0]);
				$len = strlen($value[0]);
				$tag = $inline ? 'span' : 'div';
				$this->content = substr_replace($this->content,
					($stuff == 'fc' ? '<?xml:namespace prefix="fb"/>' : '') .
					'<' .
						$tag .
					' id="' .
						$stuff .
					'_dst_' .
						$key .
					'"' .
						($height && $inline ? ' style="'.
						($onload ? 'position:relative;' : '') .
						'height:' .
							$height .
						'px;display:inline-block"' : '') .
						($height && !$inline ? ' style="'.
						($onload ? 'position:relative;' : '') .
						'height:' .
							$height .
						'px"' : '') .
					'></' .
						$tag .
					'>', $pos, $len);
				if (!$onload) {
					$return .= '<' .
						$tag .
					' id="'.
						$stuff .'_src_' . $key . 
					'">' .
						$value[0] .
					'</' .
						$tag .
					'><script type="text/javascript">(function(){var a=document,b=a.getElementById("' .
						$stuff . '_dst_' . $key . '"),c=b.parentNode,d=a.getElementById("' .
						$stuff . '_src_' . $key . '");if(c===a.body){c.insertBefore(d,b);c.removeChild(b)}else{c.innerHTML=c.innerHTML.replace(/\x3c' .
						$tag .
					'[^>]+id="?' .
						$stuff .
					'_dst_' .
						$key .
					'["\s>].*?\x3c\/' . 
						$tag .
					'>/i,d.innerHTML);b=a.getElementById("' .
						$stuff . '_src_' . $key . '");b.parentNode.removeChild(b)}}())</script>';
				} else {
					$return .= '<script type="text/javascript">wss_onload[wss_onload.length]=function(){wss_parentNode=document.getElementById(\'' .
						$stuff . '_dst_' . $key
					.'\');' .
						str_replace(array("\n", "\r", '###WSS###', '<div', '</div', '// ]]>'),
							array(' ', '', $key, '\x3cdiv', '\x3c/div', ''),
							preg_replace("@(<!--.*?-->|/\*.*?\*/)@is", "", preg_replace("@" . $onload_mask . "@is",
							$onload_result, $value[0]))) .
						'}</script>';
				}
			}
		}
		return $return;
	}

	/**
	* Moves all known informers before </body>
	* Also handles counters and ads
	* Leaves placeholders for them in content
	*
	**/
	function replace_informers ($options) {
		$before_body = '';
		$before_body_onload = empty($this->options['page']['unobtrusive_onload']) ?
			'' : (empty($this->options['page']['unobtrusive_inline']) ?
				'<script type="text/javascript" src="//' .
				(empty($this->options['javascript']['host']) ?
				$this->options['page']['host'] :
				$this->options['javascript']['host']) .
				(empty($this->options['javascript']['far_future_expires_rewrite']) ?
				'' : $this->options['page']['cachedir_relative'] . 'wo.static.php?') .
				$this->options['javascript']['cachedir_relative'] .
				'yass.loader.js"></script>' :
				'<script type="text/javascript">(function(){function j(a){var b={};a=a.split(",");for(var g=0;g<a.length;g++)b[a[g]]=true;return b}var o=document,h;o.write=function(a){h=wss_parentNode||document.body;new x(a,{start:function(b,g,k){b=o.createElement(b);for(var d=0;d<g.length;d++)b.setAttribute(g[d].name,g[d].value);h.appendChild(b);k||(h=b)},end:function(){h=h.parentNode},chars:function(b){switch(h.nodeName.toLowerCase()){case "script":b&&eval(b);break;default:b&&h.appendChild(o.createTextNode(b));break}},comment:function(b){h.appendChild(o.createComment(b))}})};var r=/^<(\w+)((?:\s+\w+(?:\s*=\s*(?:(?:"[^"]*")|(?:\'[^\']*\')|[^>\s]+))?)*)\s*(\/?)>/,s=/^<\/(\w+)[^>]*>/,y=/(\w+)(?:\s*=\s*(?:(?:"((?:\\\\.|[^"])*)")|(?:\'((?:\\\\.|[^\'])*)\')|([^>\s]+)))?/g,z=j("area,base,basefont,br,col,frame,hr,img,input,isindex,link,meta,param,embed"),A=j("address,applet,blockquote,button,center,dd,del,dir,div,dl,dt,fieldset,form,frameset,hr,iframe,ins,isindex,li,map,menu,noframes,noscript,object,ol,p,pre,script,table,tbody,td,tfoot,th,thead,tr,ul"),B=j("a,abbr,acronym,applet,b,basefont,bdo,big,br,button,cite,code,del,dfn,em,font,i,iframe,img,input,ins,kbd,label,map,object,q,s,samp,script,select,small,span,strike,strong,sub,sup,textarea,tt,u,var"),C=j("colgroup,dd,dt,li,options,p,td,tfoot,th,thead,tr"),D=j("checked,compact,declare,defer,disabled,ismap,multiple,nohref,noresize,noshade,nowrap,readonly,selected"),E=j("script,style"),x=function(a,b){function g(m,f,e,i){if(A[f])for(;c.last()&&B[c.last()];)k("",c.last());C[f]&&c.last()==f&&k("",f);(i=z[f]||!!i)||c.push(f);if(b.start){var t=[];e.replace(y,function(p,q,u,v,w){p=u?u:v?v:w?w:D[q]?q:"";t.push({name:q,value:p,escaped:p.replace(/(^|[^\\\\])"/g,\'$1\\\\"\')})});b.start&&b.start(f,t,i)}}function k(m,f){if(f)for(e=c.length-1;e>=0;e--){if(c[e]==f)break}else var e=0;if(e>=0){for(var i=c.length-1;i>=e;i--)b.end&&b.end(c[i]);c.length=e}}var d,n,l,c=[];c.last=function(){return this[this.length-1]};this.parse=function(m){for(last=a=m;a;){n=true;if(!c.last()||!E[c.last()]){if(a.indexOf("<!--")==0){d=a.indexOf("--\>");if(d>=0){b.comment&&b.comment(a.substring(4,d));a=a.substring(d+3);n=false}}else if(a.indexOf("</")==0){if(l=a.match(s)){a=a.substring(l[0].length);l[0].replace(s,k);n=false}}else if(a.indexOf("<")==0)if(l=a.match(r)){a=a.substring(l[0].length);l[0].replace(r,g);n=false}if(n){d=a.indexOf("<");m=d<0?a:a.substring(0,d);a=d<0?"":a.substring(d);b.chars&&b.chars(m)}}else{a=a.replace(new RegExp("(.*)</"+c.last()+"[^>]*>"),function(f,e){e=e.replace(/<!--(.*?)--\>/g,"$1").replace(/<!\[CDATA\[(.*?)]]\>/g,"$1");b.chars&&b.chars(e);return""});k("",c.last())}if(a&&a==last)throw"Parse Error: "+a;last=a}};this.parse(a)}})();</script>') .
				'<script type="text/javascript">wss_onload=[]</script>';
		require($options['installdir'] . 'libs/php/config.unobtrusive.php');
		foreach ($unobtrusive_items as $group => $items) {
			if (!empty($options[$group])) {
				foreach ($items as $key => $item) {
					if (strpos($this->content, $item['marker'])) {
						$before = $this->replace_unobtrusive_generic("@" . $item['regexp'] . "@is",
							$key, empty($item['height']) ? 0 : $item['height'],
							empty($item['inline']) ? false : $item['inline'],
							empty($item['onload_before']) ? false : $item['onload_before'],
							empty($item['onload_after']) ? false : $item['onload_after']);
/* switch between window.onload and onDOMready handlers */
						if (!empty($item['onload_before']) && !empty($item['onload_after'])) {
							$before_body_onload .= $before;
						} else {
							$before_body .= $before;
						}
					}
				}
			}
		}
		$before_body .= $before_body_onload;
		$before_body .= empty($this->options['page']['unobtrusive_onload']) ?
			'' : '<script type="text/javascript">wss_onload_ready=1;window[/*@cc_on!@*/0?"attachEvent":"addEventListener"](/*@cc_on "on"+@*/"load",function(){wss_onload_counter=0;setTimeout(function(){var a=wss_onload[wss_onload_counter];if(wss_onload_ready){wss_onload_ready=0;if(a){a()}wss_onload_counter++}if(a){setTimeout(arguments.callee,10)}},10)},false)</script>';
		if (!empty($before_body)) {
			if (!empty($options['html_tidy']) && ($bodypos = strpos($this->content, '</body>'))) {
				$this->content = substr_replace($this->content, $before_body, $bodypos, 0);
			 } elseif (!empty($options['html_tidy']) && ($bodypos = strpos($this->content, '</BODY>'))) {
				$this->content = substr_replace($this->content, $before_body, $bodypos, 0);
			 } else {
				$this->content = preg_replace('@</body>@i', $before_body . "$0" , $this->content);
/* a number of engines doesn't set </body> */
				if (!strpos($this->content, $before_body)) {
					$this->content .= $before_body;
				}
			 }
		}
	}

	/**
	* Removes all secondary stuff from HTML code
	*
	**/
	function prepare_html ($source, $cssonly = false) {
		$dest = $source;
/* remove conditional comments for current browser */
		$dest = $this->remove_conditional_comments($dest);
/* Pull out the comment blocks to avoid touching conditional comments,
	and some semi-standard complaint hacks, skip if we fetch body but not head */
		if (!empty($this->options['javascript']['inline_scripts']) && !$cssonly) {
			$dest = str_replace(
				array('//]]>',		'// ]]>',	'<!--//-->',	'<!-- // -->',
					'<![CDATA[',	'//><!--',	'//--><!]]>',	'//-->',
					'<!--/*--><![CDATA[//><!--','//-->',		'//<!--',
					'// <!--', '// -->'), '', $dest);
			$dest = preg_replace("@(<script[^>]*>)[\r\n\t\s]*<!--@is", "$1", $dest);
			$dest = preg_replace("@-->[\r\n\t\s]*(</script>)@is", "$1", $dest);
		}
/* remove comments from <style> constructions */
		if (!empty($this->options['css']['inline_scripts'])) {
			$dest = preg_replace("@(<style type=[\"']text/css[^>]*>)[\t\s\r\n]*<!--@is", "$1", $dest);
			$dest = preg_replace("@[\t\s\r\n]*-->(</style>)@is", "$1", $dest);
		}
		if ($dest !== $source) {
/* replace current content with updated version */
			$this->content = str_replace($source, $dest, $this->content);
		}
/* and now remove all comments and parse result code -- to avoid IE code mixing with other browsers */
		while ($compos = strpos($dest, '<!--')) {
			$end = strpos(substr($dest, $compos), '-->');
			$dest = substr_replace($dest, '', $compos, $end + 3);
		}
		return $dest;
	}

	/**
	* Gets the head (and body) part(s) of the HTML document
	*
	**/
	function get_head () {
		if (empty($this->head) && empty($this->body)) {
/* try to define base URI for the document */
			if ($this->options['page']['html_tidy']) {
				if (($basepos = strpos($this->content, '<base')) || ($basepos = strpos($this->content, '<BASE'))) {
					$basepos = strpos($this->content, 'href=', $basepos);
					if (!$basepos) {
						$basepos = strpos($this->content, 'HREF=', $basepos);
					}
					$baseend = strpos($this->content, '>', $basepos);
					if ($this->content{$baseend-1} === '/') {
						$baseend--;
					}
					$this->basehref = trim(str_replace(array('"', ""), array(), substr($this->content, $basepos + 5, $baseend - $basepos - 6)));
				}
			} elseif (preg_match("@<base\s+href@is", $this->content)) {
				$this->basehref = preg_replace("@.*<base\s*href\s*=\s*['\"](.*?)['\"].*@is", "$1", $this->content);
			}
			if (!empty($this->basehref) && preg_match("@https?://(www\.)?" . $this->host . "@", $this->basehref)) {
				$this->basehref = '';
			}
/* change all links on the page according to DEBUG mode */
			if ($this->debug_mode) {
				$this->content = preg_replace("@(<a[^>]+href\s*=\s*['\"])([^\?]*?)(\?(.+?))?(['\"])@is", "$1$2?$4&amp;web_optimizer_debug=1$5", $this->content);
			}
/* Remove comments ?*/
			if (!empty($this->options['page']['remove_comments'])) {
/* skip removing escaped JavaScript code, thx to smart */
				$this->content = str_replace(
					array('//]]>',		'// ]]>',	'<!--//-->',	'<!-- // -->',
						'<![CDATA[',	'//><!--',	'//--><!]]>',	'// -->',
						'<!--/*--><![CDATA[//><!--','//-->',		'--></script>',
						'<script type="text/javascript"><!--',
						'<script language="javascript"  type="text/javascript" ><!--'),
					array('@@@WSSLEAVE1@@@', '@@@WSSLEAVE2@@@', '@@@WSSLEAVE3@@@', '@@@WSSLEAVE4@@@',
						'@@@WSSLEAVE5@@@', '@@@WSSLEAVE6@@@', '@@@WSSLEAVE7@@@', '@@@WSSLEAVE8@@@',
						'@@@WSSLEAVE9@@@', '@@@WSSLEAVE10@@@', '@@@WSSLEAVE11@@@', '@@@WSSLEAVE12@@@',
						'@@@WSSLEAVE13@@@'), $this->content);
				$this->content = preg_replace("@<!--[^\[].*?-->@is", '', $this->content);
				$this->content = str_replace(
					array('@@@WSSLEAVE1@@@', '@@@WSSLEAVE2@@@', '@@@WSSLEAVE3@@@', '@@@WSSLEAVE4@@@',
						'@@@WSSLEAVE5@@@', '@@@WSSLEAVE6@@@', '@@@WSSLEAVE7@@@', '@@@WSSLEAVE8@@@',
						'@@@WSSLEAVE9@@@', '@@@WSSLEAVE10@@@', '@@@WSSLEAVE11@@@', '@@@WSSLEAVE12@@@',
						'@@@WSSLEAVE13@@@'),
					array('//]]>',		'// ]]>',	'<!--//-->',	'<!-- // -->',
						'<![CDATA[',	'//><!--',	'//--><!]]>',	'// -->',
						'<!--/*--><![CDATA[//><!--','//-->',		'--></script>',
						'<script type="text/javascript"><!--',
						'<script language="javascript"  type="text/javascript" ><!--'), $this->content);
			}
/* fix script positioning for DLE */
			if ($this->options['javascript']['minify'] && strpos($this->content, '<div id="loading-layer"')) {
				$this->content = preg_replace("@(</head>)[\r\n\t\s]*(<body[^>]*>)?[\r\n\t\s]*(<script.*?)(<div id=\"loading-layer.*=10\); \"></div>)[\r\n\t\s]*(<script.*?)<(body|div|table)@is", "$3$5$1$2$4<$6", $this->content);
			}
/* fix Shadowbox inclusions */
			if (($this->options['javascript']['minify'] || $this->options['css']['minify']) && strpos($this->content, 'Shadowbox.load')) {
				$this->content = preg_replace("@Shadowbox.loadSkin\(['\"](.+?)['\"]\s*,\s*['\"](.+?)['\"]\);@is", "</script><link rel=\"stylesheet\" type=\"text/css\" href=\"$2/$1/skin.css\"><script type=\"text/javascript\" src=\"$2/$1/skin.js\"></script><script type=\"text/javascript\">", $this->content);
				$this->content = preg_replace("@Shadowbox.loadLanguage\(['\"](.+?)['\"]\s*,\s*['\"](.+?)['\"]\);@is", "</script><script type=\"text/javascript\" src=\"$2/shadowbox-$1.js\"></script><script type=\"text/javascript\">", $this->content);
				preg_match_all("@Shadowbox.loadPlayer\(\[([^\]]+?)\]\s*,\s*['\"](.*?)['\"]\);@", $this->content, $matches, PREG_SET_ORDER);
				foreach ($matches as $match) {
					$inclusion = '</script>';
					$players = explode(",", $match[1]);
					foreach ($players as $player) {
						$player = str_replace(array('\'', '"', ' '), '', $player);
						$inclusion .= "<script type=\"text/javascript\" src=\"" .
							$match[2] .
							"/shadowbox-" .
							$player .
							".js\"></script>";
					}
					$this->content = str_replace($match[0], $inclusion . "<script type=\"text/javascript\">", $this->content);
				}
			}
/* skip parsing head if we include both CSS and JavaScript from head+body */
			if (empty($this->options['javascript']['minify_body']) ||
				empty($this->options['css']['minify_body'])) {
					if (empty($this->options['page']['html_tidy'])) {
						preg_match("!<head(\s+[^>]+)?>.*?<body!is",
							$this->content, $matches);
						$head = $matches[0];
					} else {
						if ($headpos = strpos($this->content, '<head')) {
							$head = substr($this->content, $headpos,
								strpos($this->content, '</head>') - $headpos);
						} elseif ($headpos = strpos($this->content, '<HEAD')) {
							$head = substr($this->content, $headpos,
								strpos($this->content, '</HEAD>') - $headpos);
						}
					}
					if (!empty($head)) {
						$this->head = $this->prepare_html($head);
					}
			}
/* get head+body if required */
			if (!empty($this->options['javascript']['minify_body']) || !empty($this->options['css']['minify_body'])) {
				preg_match("!<head.*!is", $this->content, $matches);
				if (!empty($matches[0])) {
					$this->body = $this->prepare_html($matches[0], empty($this->options['javascript']['minify_body']));
				}
			}
			$xhtml = strpos($this->content, 'XHTML');
/* split XHTML behavior from HTML */
			$this->xhtml = $xhtml > 34 && $xhtml < 100;
/* add WEBO Site SpeedUp spot */
			if (!empty($this->options['page']['spot'])) {
				$this->content .= '<!--WSS-->';
			}
			if ($this->debug_mode || !empty($this->options['page']['counter'])) {
				$stamp = '<script type="text/javascript">__WSS=(new Date()).getTime()</script>';
				if ($this->options['page']['html_tidy'] &&
					($headpos = strpos($this->content, '<head'))) {
						$headend = strpos($this->content, '>', $headpos);
						$this->content = substr_replace($this->content,
							$stamp, $headend + 1, 0);
				} elseif ($this->options['page']['html_tidy'] &&
					($headpos = strpos($this->content, '<HEAD'))) {
						$headend = strpos($this->content, '>', $headpos);
						$this->content = substr_replace($this->content,
							$stamp, $headend + 1, 0);
				} else {
					$this->content = preg_replace("@<head[^>]*>@is",
						"$0" . $stamp, $this->content);
				}
			}
/* add info about client side load speed */
			if ($this->debug_mode) {
				$this->content = preg_replace("@(<head[^>]*>)@is", "$1<script type=\"text/javascript\">window[/*@cc_on !@*/0?'attachEvent':'addEventListener'](/*@cc_on 'on'+@*/'load',function(){__WSS=(new Date()).getTime()-__WSS},false);window.onerror=function(){window.__WSSERR=(typeof window.__WSSERR!=='undefined'?window.__WSSERR:0)+1;return false}</script>", $this->content);
			}
			if (!empty($this->options['page']['footer']) || !empty($this->options['page']['counter'])) {
				$stamp = '';
/* add WEBO Site SpeedUp stamp */
				if (!empty($this->options['page']['footer'])) {
					$style = empty($this->options['page']['footer_style']) ? '' :
						' style="' . $this->options['page']['footer_style'] . '"';
					$title = empty($this->options['page']['footer_text']) ? '' :
						' title="' . $this->options['page']['footer_text'] . '"';
					$text = empty($this->options['page']['footer_text']) ||
						!empty($this->options['page']['footer_image']) ? '' : $this->options['page']['footer_text'];
/* place or not image? */
					if (empty($this->options['page']['footer_image'])) {
						$background_image = $background_style = '';
					} else {
						$background_image = $this->options['css']['cachedir_relative'] . $this->options['page']['footer_image'];
						$image_style =
							'display:block;text-decoration:none;width:100px;height:100px;';
						if (in_array($this->ua_mod, array('.ie5', '.ie6'))) {
							$background_style = $image_style . 
								'filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=' .
									$background_image .
								',sizingMethod=\'scale\')';
						} else {
							$background_style = $image_style . 
								'background:url(' .
									$background_image .
								')';
						}
						$background_style = ' style="' . $background_style . '"';
					}
/* choose between link or span */
					if (empty($text)) {
						$el = 'a href="http://www.webogroup.com/" rel="nofollow"';
						$el_close = 'a';
					} else {
						$el = $el_close = 'span';
					}
/* finally from stamp */
					$stamp .= '<div' .
							$style .
						'><' .
							$el .
							$title .
							$background_style .
						'>'.
							$text .
						'</' .
							$el_close .
						'></div>';
				}
/* add WEBO Site SpeedUp page load counter */
				if (!empty($this->options['page']['counter'])) {
					$stamp .= '<script type="text/javascript">(function(){window[/*@cc_on !@*/0?"attachEvent":"addEventListener"](/*@cc_on "on"+@*/"load",function(){if(typeof _gat!=="undefined"){var a,b=_gat.vb,c;for(a in _gat.vb){c=b[a].s}a=_gat._getTracker(c);b=(new Date()).getTime()-__WSS;a._trackEvent("WEBO Site SpeedUp","Page Load Time",50*Math.round(b/50)+"ms",b)}},false)})()</script>';
				}
				if ($this->options['page']['html_tidy'] &&
					($bodypos = strpos($this->content, '</body>'))) {
						$this->content = substr_replace($this->content,
							$stamp, $bodypos, 0);
				} elseif ($this->options['page']['html_tidy'] &&
					($bodypos = strpos($this->content, '</BODY>'))) {
						$this->content = substr_replace($this->content,
							$stamp, $bodypos, 0);
				} else {
					$this->content = preg_replace("@</body>@i",
						$stamp . "$0", $this->content);
/* a number of engines doesn't set </body> */
					if (!strpos($this->content, $stamp)) {
						$this->content .= $stamp;
					}
				}
			}
		}
	}

	/**
	* Removes conditional comments for MSIE 5-9
	*
	**/
	function remove_conditional_comments ($source) {
		if (!empty($this->ua_mod)) {
/* preliminary strpos saves about 50% of CPU */
			if (strpos($source, 'IE]>') !== false) {
				$source = preg_replace("@<!--\[if \(?IE\)?\]>(.*?)<!\[endif\]-->@s", "$1", $source);
			}
			for ($version = $this->min_ie_version; $version < $this->max_ie_version; $version++) {
/* detect */
				if ($this->ua_mod == ".ie" . $version || ($version == 7 && $this->ua_mod == '.ie7')) {
/* detect equality */
					if (strpos($source, 'IE ' . $version . ']>') !== false) {
						$source = preg_replace("@<!--\[if ((gte|lte) )?\(?IE " . $version . "[^\]]*\)?\]>(.*?)<!\[endif\]-->@s", "$3", $source);
					}
/* detect lesser versions */
					for ($i = $this->min_ie_version; $i < $version; $i++) {
						if (strpos($source, 'IE ' . $i . ']>') !== false) {
							$source = preg_replace("@<!--\[if gte? IE " . $i . "[^\]]*\]>(.*?)<!\[endif\]-->@s", "$1", $source);
						}
					}
/* detect greater versions */
					for ($i = $version + 1; $i < $this->max_ie_version; $i++) {
						if (strpos($source, 'IE ' . $i . ']>') !== false) {
							$source = preg_replace("@<!--\[if lte? IE " . $i . "[^\]]*\]>(.*?)<!\[endif\]-->@s", "$1", $source);
						}
					}
				}
			}
		} elseif (empty($this->options['uniform_cache']) && strpos($source, '<!--[if') !== false) {
			$source = preg_replace("@<!--\[if.*?\[endif\]-->@s", "", $source);
		}
		return $source;
	}

	/**
	* Converts sinlge path to the absolute one
	*
	**/
	function convert_path_to_absolute ($file, $path, $leave_querystring = false) {
		$endfile = '';
		$root = $this->options['document_root'];
		if (!empty($path['file'])) {
			$endfile = $path['file'];
		}
		if (!$leave_querystring) {
			$file = $this->strip_querystring($file);
			$endfile = $this->strip_querystring($endfile);
		}
/* Don't touch data URIs, or mhtml:, or external files */
		if (preg_match("!^(https?|data|mhtml):!is", $file) && !preg_match("@//(www\.)?". $this->host_escaped ."@is", $file)) {
			return false;
		}
		$absolute_path = $file;
/* external source file */
		if (preg_match("!^https?://!", $endfile) && !preg_match("!^https?://!", $file)) {
			if (substr($file, 0, 1) != '/') {
				$absolute_path = preg_replace("@[^\/]+$@", "", $endfile) . $absolute_path;
			} else {
				$absolute_path = preg_replace("@(https?://[^\/]+).*@", "$1", $endfile) . $absolute_path;
			}
		} else {
/* Not absolute or external */
			if (substr($file, 0, 1) != '/' && !preg_match("!^https?://!", $file)) {
/* add relative directory. Need somehow parse current meta base... */
				if (substr($endfile, 0, 1) != "/" && !preg_match("!^https?://!", $endfile)) {
					$endfile = preg_replace("@([^\?&]*/).*@", "$1", $_SERVER['REQUEST_URI']) . $endfile;
				}
				$full_path_to_image = preg_replace("@[^/\\\]+$@", "", $endfile);
				$absolute_path = str_replace($root, "/", $this->view->unify_dir_separator($full_path_to_image . $file));
			}
		}
/* remove HTTP host from absolute URL */
		return preg_replace("!https?://(www\.)?". $this->host_escaped ."/+!i", "/", $absolute_path);
	}

	/**
	* Finds background images in the CSS and converts their paths to absolute
	*
	**/
	function convert_paths_to_absolute ($content, $path, $leave_querystring = false) {
		preg_match_all( "/url\s*\(\s*['\"]?(.*?)['\"]?\s*\)/is", $content, $matches);
		if(count($matches[1]) > 0) {
			foreach($matches[1] as $key => $file) {
				$absolute_path = $this->convert_path_to_absolute($file, $path, $leave_querystring);
				if (!empty($absolute_path)) {
/* add quotes if there is not plain URL */
					if (strpos($absolute_path, ' ')) {
						$absolute_path = "'" . $absolute_path . "'";
					}
/* replace path in initial CSS */
					$content = preg_replace("@url\s*\(\s*['\"]?" .
						str_replace("?", "\?", $file) .
						"['\"]?\s*\)@is", "url(" . $absolute_path . ")", $content);
				}
			}
		}
		return $content;
	}

	/**
	* Convert all background image to CSS Sprites if possible
	**/
	function convert_css_sprites ($content, $options, $css_url) {
/* try to get and increase memory limit */
		$memory_limit = round(str_replace("M", "000000", str_replace("K", "000", @ini_get('memory_limit'))));
/* 64M must enough for any operations with images. I hope... */
		if ($memory_limit < 64000000) {
			@ini_set('memory_limit', '64M');
		}
		@chdir($options['cachedir']);
		$css_sprites = new css_sprites($content, array(
			'root_dir' => $options['installdir'],
			'current_dir' => $options['cachedir'],
			'html_cache' => $this->options['page']['cachedir'],
			'website_root' => $this->options['document_root'],
			'truecolor_in_jpeg' => $options['truecolor_in_jpeg'],
			'aggressive' => $options['aggressive'],
			'no_ie6' => $options['no_ie6'],
			'ignore' => $options['css_sprites_ignore'],
			'ignore_list' => $options['css_sprites_exclude'],
			'partly' => $options['css_sprites_partly'],
			'extra_space' => $options['css_sprites_extra_space'],
			'expires_rewrite' => $options['css_sprites_expires_rewrite'],
			'cache_images' => $this->options['page']['cache_images'],
			'cache_images_rewrite' => $this->options['page']['far_future_expires_rewrite'],
			'data_uris' => $options['data_uris'],
			'data_uris_separate' => $options['data_uris_separate'],
			'data_uris_size' => $options['data_uris_size'],
			'data_uris_ignore_list' => $options['data_uris_exclude'],
			'mhtml' => $options['mhtml'],
			'mhtml_size' => $options['mhtml_size'],
			'mhtml_ignore_list' => $options['mhtml_exclude'],
			'css_url' => $css_url,
			'dimensions_limited' => $options['dimensions_limited'],
			'no_css_sprites' => !$options['css_sprites'],
			'multiple_hosts' => empty($options['parallel']) ? array() : explode(" ", $options['parallel_hosts']),
			'user_agent' => $this->ua_mod,
			'punypng' => $options['punypng'],
			'restore_properties' => $options['css_restore_properties'],
			'ftp_access' => $this->options['page']['parallel_ftp'],
			'http_host' => $this->options['page']['host'],
			'https_host' => $this->options['page']['parallel_https'],
			'uniform_cache' => $this->options['uniform_cache']
		));
		return $css_sprites->process();
	}

	/**
	* Convert all background image to data:URI / mhtml
	**/
	function convert_data_uri ($content, $options, $css_url) {
		@chdir($options['cachedir']);
		$compressed = '';
		preg_match_all("!([^\{\}]+){[^\}]*(background[^:]*):([^;]+);[^\}]*}!is", $content, $imgs, PREG_SET_ORDER);
		if (is_array($imgs)) {
			$replaced = array();
			$mhtml = in_array($this->ua_mod, array('.ie6', '.ie7'));
			$location = 0;
			$data_uri_exclude = explode(" ", $options['data_uris_exclude']);
			$mhtml_exclude = explode(" ", $options['data_uris_exclude_html']);
			foreach ($imgs as $image) {
				$base64 = '';
				if (strpos(strtolower($image[3]), "url") !== false) {
					$css_image = trim(str_replace(array('"', "'"), '', preg_replace("@.*url\(([^\)]+)\).*@is", "$1", $image[3])));
					$image_saved = $css_image;
					$css_image = $css_image{0} == '/' ? $this->options['document_root'] . $css_image : $options['cachedir'] . '/' .$css_image;
					$chunks = explode(".", $css_image);
					$extension = str_replace('jpg', 'jpeg', strtolower(array_pop($chunks)));
					$chunks = explode("/", $css_image);
					$filename = array_pop($chunks);
					if (empty($replaced[$image_saved])) {
						if (!@is_file($css_image) ||
							in_array($extension, array('htc', 'cur', 'eot', 'ttf', 'svg', 'otf', 'woff')) ||
							strpos($css_image, "://") ||
							strpos($css_image, "mhtml:") !== false ||
							strpos($css_image, "data:") !== false) {
								$css_image = $image_saved;
						} else {
							$encoded = base64_encode(@file_get_contents($css_image));
							if ($mhtml) {
								if (@filesize($css_image) < $options['data_uris_mhtml_size'] &&
									!in_array($filename, $mhtml_exclude) &&
									!empty($encoded)) {
										$compressed .= "\n\n--_\nContent-Location:" .
											$location .
											"\nContent-Transfer-Encoding:base64\n\n" .
											$encoded;
										$css_image = 'mhtml:' . $css_url . '!' . $location;
										$location++;
								} else {
									$css_image = $image_saved;
								}
							} else {
								if (@filesize($css_image) < $options['data_uris_size'] &&
									!in_array($filename, $data_uri_exclude) &&
									!empty($encoded)) {
										$css_image = '';
										$base64 = 'data:image/' .
											$extension .
											';base64,' .
											$encoded;
								} else {
									$css_image = $image_saved;
								}
							}
						}
						$replaced[$image_saved] = $css_image;
						$content = str_replace($image_saved, $css_image, $content);
					}
					if (!$mhtml && $base64) {
						$compressed .= $image[1] .
							'{' .
							$image[2] . 
							':' .
							str_replace($image_saved, $base64, $image[3]) .
							'}';
					}
					if ($this->options['uniform_cache']) {
						$sel = 'html ' .
							$image[1] .
							'{' .
							$image[2] .
							':' .
							$image[3] .
							'}';
						$content .= '* ' . $sel . '*+' . $sel;
					}
				} else {
					$compressed .= $image[1] .
						'{' .
						$image[2] . 
						':' .
						$image[3] .
						'}';
				}
			}
		}
		return array($content, $compressed);
	}

	/**
	 * Converts REQUEST_URI to cached file name
	 *
	 **/
	function convert_request_uri ($uri = false) {
		$uri = $uri ? $uri : preg_replace("@index\.php$@", "", $_SERVER['REQUEST_URI']);
/* remove excessive GET params */
		if (trim($this->options['page']['cache_params'])) {
			$params = str_replace(" ", "|", $this->options['page']['cache_params']);
			$uri = preg_replace("@(" . $params . ")=[^&\?]+[\?&]?@", "", $uri);
		}
/* replace /, ?, & with - */
		$uri = str_replace(
			array('/?', '/', '?', '&'),
			array('+', '+', '+', '+'),
			$uri);
		return $uri;
	}

	/**
	 * Downloads remote files to include
	 *
	 **/
	function get_remote_file ($file, $tag = "link") {
/* check if we already have this file */
		if (preg_match("/\/wo[abcdef0-9]+$/", $file)) {
			return preg_replace("/.*(wo[abcdef0-9]+$)/", "$1", $file);
		}
		$current_directory = @getcwd();
/* dirty fix for buggy getcwd call */
		if ($current_directory === '/') {
			$current_directory = $this->options['css']['installdir'];
		}
		if (function_exists('curl_init')) {
			if ($tag == 'link') {
				@chdir($this->options['css']['cachedir']);
			} else {
				@chdir($this->options['javascript']['cachedir']);
			}
			$ua = empty($_SERVER['HTTP_USER_AGENT']) ||
				!empty($this->options['uniform_cache']) ?
				"Mozilla/5.0 (WEBO Site SpeedUp; http://www.webogroup.com/) Firefox 3.6" :
				$_SERVER['HTTP_USER_AGENT'];
			$return_filename = 'wo' . md5($file . $ua) . '.' . ($tag == 'link' ? 'css' : 'js');
			if (@file_exists($return_filename)) {
				$timestamp = @filemtime($return_filename);
			} else {
				$timestamp = 0;
			}
/* prevent download more than 1 time a day */
			if (!empty($timestamp) && $timestamp + 86400 > $this->time) {
				@chdir($current_directory);
				return $return_filename;
			}
/* try to download remote file */
			$ch = @curl_init($file);
			$fp = @fopen($return_filename, "w");
			$fph = @fopen($return_filename . '.headers', "w");
			if ($fp && $ch) {
				@curl_setopt($ch, CURLOPT_FILE, $fp);
				@curl_setopt($ch, CURLOPT_HEADER, 0);
				@curl_setopt($ch, CURLOPT_USERAGENT, $ua);
				@curl_setopt($ch, CURLOPT_ENCODING, "");
				@curl_setopt($ch, CURLOPT_REFERER, $host);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				@curl_setopt($ch, CURLOPT_WRITEHEADER, $fph);
				if (!empty($this->options['page']['htaccess_username']) && !empty($this->options['page']['htaccess_password'])) {
					@curl_setopt($ch, CURLOPT_USERPWD, $this->options['page']['htaccess_username'] . ':' . $this->options['page']['htaccess_password']);
				}
				@curl_exec($ch);
				@curl_close($ch);
				@fclose($fp);
				@fclose($fph);
/* check if we deal with 404 error, remove result */
				$headers = @file_get_contents($return_filename . '.headers');
				if (strpos($headers, 'HTTP/1.1 404') !== false ||
					strpos($headers, 'HTTP/1.0 404') !== false ||
					strpos($headers, 'HTTP/0.1 404') !== false ||
					strpos($headers, 'HTTP/0.9 404') !== false) {
						@unlink($return_filename);
				} else {
/* try to replace background images to local ones */
					$contents = @file_get_contents($return_filename);
					if (!empty($contents) && $tag == 'link') {
/* correct background-images in CSS file */
						$this->write_file($return_filename, $this->convert_paths_to_absolute($contents, array('file' => $file)));
					}
				}
				@unlink($return_filename . '.headers');
				chdir($current_directory);
				return $return_filename;
			}
		}
		@chdir($current_directory);
		return false;
	}

	/**
	 * Sets User Agent modificator
	 *
	 **/
	function set_user_agent () {
		$this->ua_mod = '';
		if (!$this->options['performance']['uniform_cache']) {
/* min. supported IE version */
			$this->min_ie_version = 5;
/* max. supported IE version */
			$this->max_ie_version = 11;
			if (strpos($this->ua, 'MSIE') && !strpos($this->ua, 'Opera')) {
				for ($version = $this->min_ie_version; $version < $this->max_ie_version; $version++) {
					if (strpos($this->ua, 'MSIE ' . $version)) {
						$this->ua_mod = '.ie' . $version;
					}
				}
			}
/* check for mobile agents */
			if (empty($this->ua_mod)) {
				$mobiles = array(
					'Android',
					'BlackBerry',
					'HTC',
					'iPhone',
					'iPod',
					'LG',
					'MOT',
					'Mobile',
					'NetFront',
					'Nokia',
					'Opera Mini',
					'Palm',
					'PPC',
					'SAMSUNG',
					'Smartphone',
					'SonyEricsson',
					'Symbian',
					'UP.Browser',
					'webOS');
				$j = 0;
/* strpos here is 2.5x faster than stristr and 6x faster than regexp */
				while (strpos($this->ua, $mobiles[$j++]) === false &&
					!empty($mobiles[$j])) {}
				if ($j != count($mobiles)) {
					$this->ua_mod = '.ma';
				}
			}
		}
	}
	
	/**
	* Replaces html entities with amps
	*
	*/
	function resolve_amps ($str) {
		return str_replace(array('&amp;', '&#38;', '﻿'), array('&', '&', ''), $str);
	}
	
	/**
	* Determines cache engine and create instance of it
	* 
	**/
	function start_cache_engine () {
		$cache_engines = array('0' => 'files',
			'1' => 'memcached',
			'2' => 'apc',
			'3' => 'xcache'
			);
		$cache_engines_options = array('0' => array('cache_dir' => $this->options['page']['cachedir']),
			'1' => array('server' => @$this->options['page']['cache_engine_options']),
			'2' => '',
			'3' => ''
			);
		if (!empty($cache_engines[$this->options['page']['cache_engine']]))
		{
			$engine_name = 'webo_cache_' . $cache_engines[$this->options['page']['cache_engine']];
			$engine_num = $this->options['page']['cache_engine'];
		}
		else
		{
			$engine_name = 'webo_cache_' . $cache_engines[0];
			$engine_num = 0;
		}
		include_once($this->options['page']['installdir'] . 'libs/php/cache_engine.php');
		$this->cache_engine = new $engine_name ($cache_engines_options[$engine_num]);
	}

	/**
	* Deletes cached HTML files determined by patterns. Just an interface for cache_engine delete_entries method.
	* 
	**/	
	function clear_html_cache ($patterns) {
		if (!empty($patterns))
		{
			$this->cache_engine->delete_entries($patterns);
		}
	}
	
	/**
	* Convert given URL to another with base URI (<base> tag / header)
	*
	**/
	function convert_basehref ($uri) {
/* check if BASE URI is given */
		$slash = substr($uri, 0, 1) != '/';
		if (!empty($this->basehref) &&
/* convert only non-external URI */
			strpos($uri, '//') !== 0 && !strpos($uri, '://')) {
/* convert absolute URL, change host */
				if ($slash) {
					return preg_replace("@^https?://[^/]+/@", '', $this->basehref) . $uri;
/* convert relative URL, change host + folder */
				} else {
					return preg_replace("@[^/]*$@", '', $this->basehref) . $uri;
				}
		} else {
			return $uri;
		}
	}

} // end class

?>
