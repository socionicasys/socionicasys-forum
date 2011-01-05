<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Provides admin interface.
 *
 **/
class admin {
	/**
	* Constructor
	* Sets the options and defines the gzip headers
	**/
	function admin ($options = null) {
		if (!empty($options['skip_startup'])) {
			return;
		}
		foreach ($options as $key => $value) {
			$this->$key = $value;
		}
		$this->skip_render = empty($this->skip_render) ? 0 : $this->skip_render;
		if (!$this->skip_render) {
/* Ensure no caching */
			header('Expires: ' . date("r"));
			header("Cache-Control: no-store, no-cache, must-revalidate, private");	
			header("Pragma: no-cache");
/* Fixes time zone for future usage */
			if (function_exists('date_default_timezone_set')) {
				@date_default_timezone_set('Europe/Moscow');
			}
		}
/* Set name of options file */
		$this->options_file = "config.webo.php";
/* try to restore options backup */
		if (@is_file($this->basepath . '.config.webo.php') && !strpos(@file_get_contents($this->basepath . $this->options_file), '$compress_options[\'license\']')) {
			@copy($this->basepath . '.config.webo.php', $this->basepath . $this->options_file);
			$this->error = array(-1 => 1);
		}
		require($this->basepath . $this->options_file);
		$this->compress_options = empty($compress_options) ? '' : $compress_options;
		$this->start_cache_engine();

/* to check and download new Web Optimizer version */
		$this->svn_generic = 'http://web-optimizator.googlecode.com/svn/';
		$this->svn = $this->svn_generic . 'trunk-stable/';
		$this->svn_beta = $this->svn_generic . 'trunk/';
		$this->version = str_replace("\r\n", "", @file_get_contents($this->basepath . 'version'));
		$this->version_stable = preg_replace("[^0-9\.]", "", empty($this->input['wss_version_stable']) ? '' : $this->input['wss_version_stable']);
/* get the latest version */
		$version_new_file = $this->compress_options['html_cachedir'] . 'version.new';
		if (in_array($this->input['wss_page'],
			array('install_dashboard',
				'install_set_password',
				'install_enter_password',
				'install_system',
				'install_update',
				'install_beta',
				'install_stable'))) {
			$this->view->download($this->svn . 'version', $version_new_file);
		}
		$this->version_new = $this->version . '+';
		if (@is_file($version_new_file)) {
			$this->version_new = @file_get_contents($version_new_file);
			@unlink($version_new_file);
		}
		$this->version_beta = $this->version;
/* check for beta version */
		if (in_array($this->input['wss_page'],
			array('install_system',
				'install_beta',
				'install_stable'))) {
			$this->view->download($this->svn_beta . 'version', $version_new_file);
			if (@is_file($version_new_file)) {
				$this->version_beta = @file_get_contents($version_new_file);
				@unlink($version_new_file);
			}
		}
/* validate license */
		if (!empty($compress_options)) {
/* reset license check */
			if (!empty($this->input['wss_license'])) {
				@unlink($this->basepath . 'cache/wo');
			}
			$this->compress_options['license'] =
				empty($this->input['wss_license']) ?
					$this->compress_options['license'] :
						$this->input['wss_license'];
			$this->premium = $this->view->validate_license($this->compress_options['license'],
				$this->compress_options['html_cachedir'], $this->compress_options['host']);
			$this->need_access = in_array($this->input['wss_page'],
				array('install_enter_password', 'install_set_password'));
/* Make sure password valid */
			$this->check_password();
			if (!$this->access) {
				$this->input['wss_page'] = 'install_set_password';
			} elseif ($this->need_access) {
				$this->input['wss_page'] = 'install_dashboard';
			}
/* default multiple hosts */
			$this->default_hosts = array('img', 'img1', 'img2', 'img3', 'img4', 'i', 'i1', 'i2', 'i3', 'i4', 'image', 'images', 'assets', 'static', 'css', 'js');
/* Set page functions for the installation and admin, makes sure nothing else can be run */
			$this->page_functions = array(
				'install_set_password' => 1,
				'install_enter_password' => 1,
				'install_dashboard' => 1,
				'install_install' => 1,
				'install_uninstall' => 1,
				'install_promo' => 1,
				'install_about' => 1,
				'install_gzip' => 1,
				'install_image' => 1,
				'install_cdn' => 1,
				'install_status' => 1,
				'install_account' => 1,
				'install_refresh' => 1,
				'install_renew' => 1,
				'install_options' => 1,
				'install_system' => 1,
				'install_update' => 1,
				'install_stable' => 1,
				'install_beta' => 1,
				'install_awards' => 1,
				'install_balance' => 1,
				'install_wizard' => 1,
				'dashboard_cache' => 1,
				'dashboard_system' => 1,
				'dashboard_options' => 1,
				'dashboard_speed' => 1,
				'dashboard_awards' => 1,
				'compress_gzip' => 1,
				'compress_image' => 1,
				'compress_cdn' => 1,
				'options_configuration' => 1,
				'options_delete' => 1
			);
/* inializa stage for chained optimization */
			$this->web_optimizer_stage =
				round(empty($this->input['web_optimizer_stage']) ? 0 :
					$this->input['web_optimizer_stage']);
/* grade URL from webo.name */
			$this->webo_grade = 'http://webo.name/check/index2.php?url=' .
				$this->compress_options['host'] .
				str_replace($this->compress_options['document_root'], '/',
					$this->compress_options['website_root']) .
				'&mode=xml&source=wo';
/* download counter */
			if (!is_file($this->basepath . 'web-optimizer-counter')) {
				$this->view->download('http://web-optimizator.googlecode.com/files/web-optimizer-counter',
					$this->basepath . 'web-optimizer-counter');
			}
		}
/* define constants for stats */
		$this->index_check = 'index.check';
		$this->index_before = 'index.before';
		$this->index_after = 'index.after';
/* initialize info about cache types */
		$this->cache_types = array(
			'js' => array('*.js', '*.js.gz'),
			'js_php' => array('*script.php', '*script.php.gz', '*script.php.df'),
			'css' => array('*.css', '*.css.gz'),
			'css_php' => array('*link.php', '*link.php.gz', '*link.php.df'),
			'res' => array('*.css.css', '*.css.css.gz', '*.php.php', '*.php.php.gz'),
			'sprites' => array('webo.*.png', 'webo.*.jpg'),
			'imgs' => array('*.png', '*.jpg', '*.gif', '*.bmp'),
			'html' => array('*.html','*.html.gz', '*.html.df'),
			'scripts' => array('*.php', '*.php.gz', '*.php.df')
		);
/* define if we can skip some info */
		$this->internal = preg_match("@wp-content|components|modules|administrator|addons|app@", $this->basepath);
/* check for database driver , WordPress */
		$this->internal_sql = strpos($this->basepath, "wp-content") !== false ||
/* Joomla! 1.5x */
			(strpos($this->basepath, "components") !== false && @is_file($this->compress_options['website_root'] . 'libraries/joomla/database/database/WeboMySql.php'));
/* fix for not supported languages */
		$this->language = empty($this->language) ? '' : $this->language;
		$this->language = in_array($this->language, array('en', 'de', 'es', 'ru', 'ua', 'fr', 'ur', 'it')) ? $this->language : 'en';
		if ($this->compress_options['active']) {
			$this->validate();
		}
/* show page */
		if (!empty($this->input) &&
			!empty($this->page_functions[$this->input['wss_page']]) &&
			method_exists($this, $this->input['wss_page']) &&
			($this->input['wss_page'] != 'install_set_password' ||
			empty($this->internal))) {
				$func = $this->input['wss_page'];
				$this->$func();
		}
	}

	/*
	* Return status of synced file
	*
	**/
	function compress_cdn () {
		$file = realpath($this->input['wss_file']);
		$size = @filesize($file);
		$error = 0;
		$success = 1;
		if (strpos($file, $this->view->paths['full']['document_root']) !== false &&
			!empty($this->compress_options['parallel']['ftp']) &&
			@function_exists('curl_init')) {
				$ch = @curl_init('ftp://' .
					preg_replace("!^([^@]+)@([^:]+):([^@]+)@!", "$1:$3@", $this->compress_options['parallel']['ftp']) .
					str_replace($this->compress_options['document_root'], "/", $file));
				$fp = @fopen($file, 'r');
				@curl_setopt($ch, CURLOPT_USERPWD, preg_replace("!(.*)@.*!", "$1", $this->compress_options['parallel']['ftp']));
				@curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, 1);
				@curl_setopt($ch, CURLOPT_UPLOAD, 1);
				@curl_setopt($ch, CURLOPT_INFILE, $fp);
				@curl_setopt($ch, CURLOPT_INFILESIZE, @filesize($file));
				@curl_exec($ch);
				$error = curl_errno($ch);
				@curl_close($ch);
				@fclose($fp);
				if ($error) {
					$success = 0;
				}
		}
		$page_variables = array(
			"file" => $file,
			"size" => $size,
			"compressed" => $size,
			"success" => $success,
			"error" => $error,
			"skip_render" => $this->skip_render
		);
		$this->view->render("compress_gzip", $page_variables);
	}

	/*
	* Return size of specific (optimized images) files
	*
	**/
	function compress_image () {
		$file = str_replace('\\', '/', realpath($this->input['wss_file']));
		$service = empty($this->input['wss_service']) ? 0 : round($this->input['wss_service']);
		$mtime = @filemtime($file);
		$size = @filesize($file);
		$backup = $file . '.backup';
		$compressed_size = $size;
		$error = 0;
		if (strpos($file, $this->view->paths['full']['document_root']) !== false) {
			if (!@is_file($backup) || $mtime != @filemtime($backup)) {
				require(dirname(__FILE__) . '/../libs/php/css.sprites.optimize.php');
				$optimizer = new css_sprites_optimize();
/* CSS Sprites uses .backup itself, so just prepare another backup */
				if (@is_file($backup)) {
					@copy($backup, $file . '.bkp');
				} else {
					@copy($file, $file . '.bkp');
				}
				$optimizer->website_root = $this->view->paths['full']['document_root'];
				switch ($service) {
					case 2:
						$optimizer->punypng($file);
						break;
					case 1:
						$optimizer->webolk($file);
						break;
					default:
						$optimizer->smushit($file);
						break;
				}
/* copy backup back */
				@copy($file . '.bkp', $backup);
				@unlink($file . '.bkp');
				$mtime2 = @filemtime($file);
/* Has file been changed? */
				$success = $mtime2 > $mtime ? 1 : 0;
				if ($success) {
					$compressed_size = @filesize($file);
					@touch($backup, $mtime2);
/* can't overwrite targeted file */
				} else {
					$error = 1;
				}
			} else {
				$success = 1;
			}
		}
		$page_variables = array(
			"file" => $file,
			"size" => $size,
			"compressed" => $compressed_size,
			"success" => $success,
			"error" => $error,
			"skip_render" => $this->skip_render
		);
		$this->view->render("compress_gzip", $page_variables);
	}

	/*
	* Return size of specific (compressed) files
	*
	**/
	function compress_gzip () {
		$file = realpath($this->input['wss_file']);
		$size = @filesize($file);
		$gzipped = $file . '.gz';
		$gzipped_size = $size;
		$success = 0;
		$error = 0;
		if (strpos($file, $this->view->paths['full']['document_root']) !== false) {
			if (!@is_file($gzipped) || !@filesize($gzipped)) {
				$raw = !function_exists('shell_exec');
				$success = 1;
				if (!$raw) {
					@shell_exec('gzip -c -n -9 ' . $file . ' > ' . $gzipped);
					if (!@is_file($gzipped) || !@filesize($gzipped)) {
						$raw = 1;
					}
				}
				if ($raw) {
					$content = @gzencode(@file_get_contents($file), 9, FORCE_GZIP);
					if (strlen($content)) {
						$success = $this->write_file($gzipped, $content);
/* can't overwrite targeted file */
						if (!$success) {
							$error = 1;
						}
					} else {
						$success = 0;
/* cam't gzip file */
						$error = 2;
					}
				}
				if ($success) {
					@touch($gzipped, @filemtime($file));
					$gzipped_size = @filesize($gzipped);
				}
			} else {
				$success = 1;
				$gzipped_size = @filesize($gzipped);
			}
		}
		$page_variables = array(
			"file" => $file,
			"size" => $size,
			"compressed" => $gzipped_size,
			"success" => $success,
			"error" => $error,
			"skip_render" => $this->skip_render
		);
		$this->view->render("compress_gzip", $page_variables);
	}

	/*
	* Returns all info about current award + retrieves them from server
	*
	**/
	function calculate_awards () {
		$evaluation1 = @file_get_contents($this->basepath . $this->index_before);
		$evaluation1 = strpos($evaluation1, '<b>Warning') ? '' : $evaluation1;
		$evaluation2 = @file_get_contents($this->basepath . $this->index_after);
		$evaluation2 = strpos($evaluation2, '<b>Warning') ? '' : $evaluation2;
/* first level - WEBO grade (YSlow + Page Speed + WEBO) */
		$grade = round(preg_replace("!.*<mark>([0-9]+)</mark>.*!", "$1", $evaluation2));
		$level1 = $grade > 50 ? $grade > 70 ? $grade > 90 ? 3 : 2 : 1 : 0;
/* second level - website home page size savings */
		$size1 = round(preg_replace("!.*</number><size>([0-9]+)</size>.*!", "$1", $evaluation1));
		$size2 = round(preg_replace("!.*</number><size>([0-9]+)</size>.*!", "$1", $evaluation2));
		$delta = ($size1 - $size2) / ($size1 + 0.01);
		$level2 = $size1 && $size2 && $delta > 0.25 ? $delta > 0.5 ? $delta > 0.75 ? 3 : 2 : 1 : 0;
/* third level - gained acceleration */
		$time1 = round(preg_replace("!.*<high>([0-9\.]+)</high>.*!", "$1", $evaluation1) * 100);
		$time2 = round(preg_replace("!.*<high>([0-9\.]+)</high>.*!", "$1", $evaluation2) * 100);
		$speedup = ($time1 - $time2) / ($time1 + 0.01);
		$speedup = $speedup < 0 || $speedup > 0.9998 ? 0 : $speedup;
		$level3 = $speedup > 0.5 ? $speedup > 0.65 ? $speedup > 0.8 ? 3 : 2 : 1 : 0;
/* fourth level - number of files on home page */
		$files = round(preg_replace("!.*<files><number>([0-9]+)</number>.*!", "$1", $evaluation2));
		$level4 = $files ? !$files || $files < 35 ? $files < 20 ? $files < 10 ? 3 : 2 : 1 : 0 : 0;
/* fifth level - WEBO Site SpeedUp options */
		$errors = $this->options_count();
/* count delta */
		$deltas = array(58, 48, 0, 0, 0, 0, 0, 0, 0, 0, 0);
		$options = $deltas[round($this->premium)];
		foreach ($errors as $key => $value) {
			$options += $value;
		}
		$level5 = $options < 50 ? $options < 25 ? $options < 5 ? 3 : 2 : 1 : 0;
		$awards = $level1 . $level2 . $level3 . $level4 . $level5;
		$host = $this->compress_options['host'];
/* check for images existence */
		if (!@is_file($this->compress_options['css_cachedir'] . 'webo-site-speedup.back.jpg')) {
			@copy($this->basepath . 'libs/css/webo-site-speedup.back.jpg',
				$this->compress_options['css_cachedir'] . 'webo-site-speedup.back.jpg');
		}
		if (!@is_file($this->compress_options['css_cachedir'] . 'webo-site-speedup.rocket.png')) {
			@copy($this->basepath . 'libs/css/rocket.main.png',
				$this->compress_options['css_cachedir'] . 'webo-site-speedup.rocket.png');
		}
		if ($this->compress_options['awards'] != $awards ||
			!@is_file($this->compress_options['css_cachedir'] . 'webo-site-speedup88.png')) {
			$sizes = array('88', '125', '161', '250');
			foreach ($sizes as $size) {
				$this->view->download("http://webo.in/rocket/?size=$size&top=$level1&middle=$level2&bottom=$level3&tail=$level4&circle=$level5",
					$this->compress_options['css_cachedir'] . 'webo-site-speedup' . $size . '.png');
				$this->view->download("http://webo.in/webonautes/?url=$host&options=" . (100 - $options),
						$this->compress_options['css_cachedir'] . 'webonautes.png');
				for ($i=1; $i<6; $i++) {
					$this->view->download("http://webo.in/webonautes/?size=$size&type=$i&url=$host&options=" . (100 - $options),
						$this->compress_options['css_cachedir'] . 'webonaut' . $i . '-' . $size . '.png');
				}
			}
			$local = @is_file($this->compress_options['css_cachedir'] . 'webo-site-speedup250.png');
			$this->save_option("['awards']", $awards);
/* save final page with awards */
			@ob_start();
			include($this->basepath . 'view/external_awards.php');
			$content = @ob_get_contents();
			@ob_end_clean();
/* add gzip / charset envelope */
			$content = '<?php header("Content-type:text/html;charset=utf-8");ob_start(\'a\');function a($b){$c=empty($_SERVER[\'HTTP_ACCEPT_ENCODING\'])?\'\':$_SERVER[\'HTTP_ACCEPT_ENCODING\'];$d=empty($_SERVER["HTTP_USER_AGENT"])?\'\':$_SERVER["HTTP_USER_AGENT"];if(!empty($b)&&(strpos($c,\'gzip\')!==\'false\'||strpos($c,\'deflate\')!==\'false\')){if(!strstr($d,"Opera")&&preg_match("/compatible; MSIE ([0-9]\.[0-9])/i",$d,$matches)){$e=floatval($matches[1]);if($e<7){$b=str_repeat(" ", 2048)."\r\n".$b;}}$g=@gzencode($b,7,strpos($c,\'gzip\')!==\'false\'?FORCE_GZIP:FORCE_DEFLATE);if(!empty($g)){header(\'Content-Encoding: gzip\');header(\'Vary: Accept-Encoding,User-Agent\');return $g;}}return $b;}?>' . $content;
			$this->write_file($this->compress_options['css_cachedir'] . 'webo-site-speedup.php', $content);
			$url = 'mhtml:http://' . $host .
				str_replace($this->compress_options['document_root'], "/",
				$this->compress_options['css_cachedir']) .
				'webo-site-speedup.css!';
			$content = '.wg-teeth{background-image:url(' .
				$url .
				'1)}.wg-footer-logo{background-image:url(' .
				$url .
				"2);display:inline;zoom:1}/*\nContent-Type:multipart/related;boundary=\"_\"" .
				"\n\n--_\nContent-Location:1\nContent-Transfer-Encoding:base64\n\n" .
				'iVBORw0KGgoAAAANSUhEUgAAAAoAAAAJCAYAAAALpr0TAAAATklEQVQYGY3BTRFAUAAGwG8EEEGNF0YVI4wwasjg7LDGifG/m5ygRZs3aDBjRpM7qDDajahyhs5VnyMULK4WlGxQY/JsQh0Mvg3xU/y0AgXf++/+7UXZAAAAAElFTkSuQmCC' .
				"\n\n--_\nContent-Location:2\nContent-Transfer-Encoding:base64\n\n" .
				'iVBORw0KGgoAAAANSUhEUgAAAFgAAAAiCAYAAADbLB6TAAAH80lEQVRo3u1aaWxVRRR+McQY4hZDVIx7lGBVQnAJIlEWURCFKCIaJQEVNFaIuCAimCo2Fdmk2uV11ecCFDAKSAlVARGUNLU2lShxqWwiKjYEm6YhDZ6v+aYcjnPve7dK+fF6k5N339yZuTPfnG2+ubGY5yotLT0vHo/PFakV+UvkiMiewsLCdUVFRY/n5eWdGgu4pF5PqTdL5HPV9jf+nyHSPZbuF4E9EiJ/CNBjdJusrKyTCGBzWFsBuqgL4Hh8XxKAHVivo35FRcXJ8n9TKm1EKrsAjsdHioZuk98KaKXcT5LfbJF6D2BZAvQHHvDhEmaiLVyG3K8V+UzuM2JdV/AlgI0XoA6FaOhuqTMgXfCAaywuLj5H5n1GLCSg1TAQ3Zmihg8UafGAuw/9pdjHs1yoNXAxAXXKRH6UBbvOLPIkBt3nTHlf1Jfyd5XrWoW5eWSXiyNs9715/qvIRulrdMDYeossFWlS898hMr28vPwUXfE1VaE6gibP8biFuyO4ofYFkr7u8zzvofpeqcq7ixxkeZPOSuR+iWtDxRmYJB68x3bzw+rJ+O4yYxsboGDtOGL8rnKdbyLJLk5Ad7o3op/fqSZQkqROo5hiN5RhEc2ijlNZzAHnplAmpjtE1d0uklBSmJ+ffynfs1jVW8Xna1RZjRpTPwPuLlgMY8xhNacNrsEBVXl6RJAaOrI4tIDlCqRPA/pf6OoIWP0J8GpfViLl16qyNy3A8r4FIfNoB7igoOAqVb6d5TtVmc6W4todyPv6YHHVO0fFzGAfiQjSBtV2fsS2bydzTcbEs2UyZyrt+Zu/0Joe0t+LCqRBHg2uRICGyGLcn5ube7oPYHn+PAP5DKWRS1BP+j1b9VfnrEpf0vdQpTjL0Ple1SgnCkicAIJBjTO3CNpfpSb1cVCEluf7nZlCAdRYJ6v7TKaVuD/gJm4AtlLsA9gjTQLsxZ4FzwqZWyPr1B8zUZhEZ6Q2RhMB8Kshgy1lvVaVhzfw2Q6nTXyO+4RRAB9ozdpakwAM+RqLZi0qRClcEK5F55kmYo7vhFx6gZlAv6C6Yma3e6L6HD6b5Xl2hw/gqD4YQEmbAtX3rcxs3EL+cEw6dnRuo1SbRAzEDbgFVYjcdODxAldAediAsiWsPnPZQwbEXngGt2TNWadt/0OQe1r508dYprOLFbBGNbdhyqW1LYpDfYwZaAvz3J5hk0egkE4nIlXyOXxPYp7wmGrvFPz1Sl/KxGfVCoTVnhjh2u2UOa1X8qGUXe1xEVvxHNt6nXZJ3zcRxAyz4HAHGy1Bhv6taucG+J+fySEMteCaNK0KZmX6fEjafclAaPttTTVrwUZEtZtmAM5Uk3rAANw/CVm1jH3MTeKDE8YKhyk/69uYrPdSujrV8che85IHPR33dc/lBecqf2XlsKU7k+z3u5F4WmP5ZPhBauMKu+VmwCljELRSAw1HvZKSksuY39o62CpPtIrjeG9q/i/OGkFwIYb56rf5Oh8rpqTeLMa9tg4GajKFppD+stOGIeNK+/jcPdAMAX6erPYlVqtwwmG5YX1JwLhenr3B7afPpOanBcDYIltWzBIcIVRdH625Scx8qjnxaG2PtCFZhIzlZnDU4nYuSFVhwIJJm0fDjrc65aKv1JPenSrl2MEceIB5X4PXZx0NpIjO9dLuC4wtqK4N2KiPPBkLi9RL/t92QgC2yboM5sZOeOcUExxvCQBqgjz/zoEqC39aihbZ7La3/P9OWB58vN3DFjXZzR1o3zOQzQ93F43qvYsC+gb30OAj5OkCanlQgOB8oWHAtmNXJ8+G813YTNXBgqQsT+6fUO/Jdnk8Lboa2Yn8zmZ/yBQSblOBPpHiITYhBYU7ZRybCYXgFj6nbadnJvpCB3Zl8KNNoAsjttW0Y1UAwD3ICWMzMdKVM61qpl/GThTXRgbWQY5RAyBM45Zj24ujHfp0WEadYshaXNoI63LkEzZQmBd2jlLnK8eZMC/HvDNlLBdxMXKQKEj9y5EQMGnIsXTl5Ij+dJtqWxhR80uVi9gWtlvkgeshaCrTyZc0AycTOp9p4lmOUsRvkItgny1wI9S6b531cjHHGvrxSZ6WVCqAGzwM2jRsQrgRadP+mN47gwv9D8f7lRHbfhSlLVwAUj0Z/D2gGrFAxuUgVcxIBWD1fpwLNiAIgrwhsYRtcHeaPDY3W5GNiLzlDgYIcK1ZMLy/HG7DCbgM64OrIph4htH+gz52KQSw/VHzYWg6viwiMFUq3wZArXAXPoBJ7i8248fRU6MDjVvuP92BKU8n2skj7FyDAHYnQ76zRZsDb4oAUMKzt58Sof1BdRw0JIQgKoMvlN+XMWH4QwaiRhLtSPs2Q8P0qYMGWNo/JWU/iQyW+xv04amjN+nL4YaGK4tpIk05WOQbBC/SmP8CWMqeAZ2A0xLm7QvbTk3wIhw64tBOfq/sIOXYzo6l+k2E1Bsh9T+BPw2p04sLuVnu38fOUB8+ogzECvpwmQbNNaGPhEh5LuTOc7oGXufV8LWaFeTCroVWixJcg35huSSRXvHgMo4ZDQ5AZ0fe5CAqe8jywx4Kcmoy+jKtL/q4FuZ/S2mm6zzfArQivfF9I4EvK8lyFXNVf6dJjugCOPzTKO0OJijzmBdCTx5JdkSfVpfn2wMrW8QPXeFpN4wEfdhXMnPSHmAm83Dw+Tw+qSblmJ1sx8YoiwC2iG6lmgF0wQkjXE7g9Q+Jw0W5TeZ2uwAAAABJRU5ErkJggg==' .
				"\n\n--_--\n*/";
			$this->write_file($this->compress_options['css_cachedir'] . 'webo-site-speedup.css', $content);
		}
/* download shorten link for twitter */
		$this->view->download('http://api.bit.ly/v3/shorten?login=wboptimizer&apiKey=R_c894fbacd544a2076da03a825a6ec2d7&uri='.
			urlencode('http://' . $this->compress_options['host'] .
				str_replace($this->compress_options['document_root'], "/",
				$this->compress_options['css_cachedir']) .
				'webo-site-speedup.php') .
			'&format=txt', $this->compress_options['css_cachedir'] . 'url');
		$short_link = @file_get_contents($this->compress_options['css_cachedir'] . 'url');
		@unlink($this->compress_options['css_cachedir'] . 'url');
		return array($level1, $level2, $level3, $level4, $level5,
			100 - $options, $grade, $files, round($size2 / 1024),
			round(100*((1 / (0.9999 - $speedup)) - 1)), $short_link);
	}
	
	/*
	* Calculate the best options for website.
	* Shows wizard page
	*
	**/	
	function install_wizard () {
		$wizard = round(isset($_GET['web_optimizer_wizard']) ? $_GET['web_optimizer_wizard'] : 0);
/* calculate options */
		if ($wizard) {
			$wizard_options = isset($_GET['web_optimizer_wizard_options']) ? $_GET['web_optimizer_wizard_options'] : '';
/*	1 - disable application, calculate .htaccess, enable client&server side caching
	2 - enable combine CSS
	3 - disable combine inline CSS
	4 - disable combine external CSS
	5 - enable minify CSS
	6 - disable minify CSS
	7 - disable combine CSS
	8 - enable gzip CSS
	9 - disable gzip CSS
	10 - enable combine JS
	11 - disable combine inline JS
	12 - move JS to head
	13 - move JS to /head, disable combine external JS
	14 - exclude JS from combine
	15 - enable minify JS with JSMin
	16 - enable minify JS with YUI Compressor
	17 - enable minify JS with Packer
	18 - disable minify JS
	19 - enable duplicates removal
	20 - disable duplicates removal
	21 - disable combine JS
	22 - enable gzip JS
	23 - disable gzip JS
	24 - enable minify HTML
	25 - disable minify HTML
	26 - enable remove HTML comments
	27 - disable remove HTML comments
	28 - enable gzip HTML
	29 - disable gzip HTML
	30 - enable plain string + no mtime
	31 - disable plain string
	32 - enable data:URI + mhtml + separation
	33 - disable separation data:URI
	34 - disable data:URI + mhtml
	35 - set JS host
	36 - set CSS host
	37 - set IMG host
	38 - enable CDN
	39 - disable CDN
	40 - enable HTML Sprites
	41 - enable HTML Sprites restriction
	42 - disable HTML Sprites
	43 - move JS to /body
	44 - move JS to /head
	45 - enable unobtrusive JavaScript
	46 - disable unobtrusive JavaScript
	47 - show selection screen (step2/step3)
	48 - show server side screen
	49 - enable or disable server side caching
	50 - show step3 screen
	51 - save settings
	100 - final check
	*/
			switch ($wizard) {
/* check htaccess, disable all options */
				case 1:
/* disable application for future tests */
					$this->save_option("['active']", 0);
/* disable all the other options */
					foreach ($this->compress_options as $group => $options) {
						if (is_array($options)) {
							foreach ($options as $key => $option) {
								if ($option === '1') {
									$this->save_option("['". $group ."']['" . $key . "']", 0);
								}
							}
						}
					}
/* detect .htaccess */
					$this->get_modules();
					$ht = count($this->apache_modules) ? 1 : 0;
/* enable .htaccess */
					$this->save_option("['htaccess']['enabled']", $ht);
					$this->save_option("['htaccess']['local']", $ht);
					$modules = array(
						'mod_deflate',
						'mod_gzip',
						'mod_expires',
						'mod_mime',
						'mod_headers',
						'mod_setenvif',
						'mod_rewrite');
					foreach ($modules as $module) {
						if ($ht && in_array($module, $this->apache_modules)) {
							$this->save_option("['htaccess']['" . $module . "']", 1);
							$this->input['wss_htaccess_' . $module] = 1;
						}
					}
					if ($ht) {
						$this->compress_options['active'] = 1;
						$this->input['wss_htaccess_enabled'] = 1;
						$this->input['wss_htaccess_local'] = 1;
						$this->input['wss_gzip_fonts'] = 1;
						$this->input['wss_far_future_expires_css'] = 1;
						$this->input['wss_far_future_expires_images'] = 1;
						$this->input['wss_far_future_expires_javascript'] = 1;
						$this->input['wss_far_future_expires_fonts'] = 1;
						$this->input['wss_far_future_expires_video'] = 1;
						$this->input['wss_far_future_expires_static'] = 1;
/* write complete test set of rules */
						$this->write_htaccess();
					}
					$test_file = $this->compress_options['html_cachedir'] . 'index.test';
					$t = time() + microtime();
					$this->view->download("http://" . $_SERVER['HTTP_HOST'] .
						str_replace($this->compress_options['document_root'], "/", $this->compress_options['website_root']),
						$test_file, 20);
/* enable HTML caching if server side expenses are more than 500ms + we have known engine */
					if (time() + microtime() - $t > 0.5 && $this->internal) {
						$this->save_option("['html_cache']['enabled']", 1);
						$this->save_option("['html_cache']['timeout']", 3600);
						$this->save_option("['sql_cache']['enabled']", 1);
						$this->save_option("['sql_cache']['timeout']", 600);
/* check for caching extensions */
						if (function_exists('xcache_set')) {
							$this->save_option("['performance']['cache_engine']", 3);
						} elseif (function_exists('apc_store')) {
							$this->save_option("['performance']['cache_engine']", 2);
						} elseif (@class_exists('Memcached') || @class_exists('Memcache')) {
							$this->save_option("['performance']['cache_engine']", 1);
						}
					}
/* some errors with .htaccess, disable all options */
					if ($ht && !@file_get_contents($test_file)) {
						foreach ($modules as $module) {
							$this->save_option("['htaccess']['" . $module . "']", 0);
							$this->input['wss_htaccess_' . $module] = 0;
						}
						$this->save_option("['htaccess']['enabled']", 0);
						$this->write_htaccess();
					}
					@unlink($test_file);
					$this->save_option("['footer']['spot']", 1);
/* enable client side caching */
					$this->save_option("['far_future_expires']['css']", 1);
					$this->save_option("['far_future_expires']['javascript']", 1);
					$this->save_option("['far_future_expires']['fonts']", 1);
					$this->save_option("['far_future_expires']['images']", 1);
					$this->save_option("['far_future_expires']['video']", 1);
					$this->save_option("['far_future_expires']['static']", 1);
					break;
/* enable combine CSS */
				case 2:
					if ($wizard_options) {
						$this->save_option("['minify']['css_body']", 1);
					}
					$this->save_option("['minify']['css']", 1);
					$this->save_option("['external_scripts']['css']", 1);
					$this->save_option("['external_scripts']['css_inline']", 1);
					break;
/* disable inline CSS */
				case 3:
					$this->save_option("['external_scripts']['css_inline']", 0);
					break;
/* disable external CSS */
				case 4:
					$this->save_option("['external_scripts']['css']", 0);
					break;
/* enable minify CSS */
				case 5:
					$this->save_option("['minify']['css_min']", 1);
					break;
/* disable minify CSS */
				case 6:
					$this->save_option("['minify']['css_min']", 0);
					break;
/* disable combine CSS */
				case 7:
					$this->save_option("['minify']['css']", 0);
					$this->save_option("['minify']['css_body']", 0);
					break;
/* enable gzip CSS */
				case 8:
					$this->save_option("['gzip']['css']", 1);
					$this->save_option("['htaccess']['enabled']", 1);
					$this->compress_options['active'] = 1;
					$this->compress_options['htaccess']['enabled'] = 1;
					$this->compress_options['gzip']['css'] = 1;
					foreach ($this->compress_options as $group => $options) {
						if (is_array($options)) {
							foreach ($options as $key => $option) {
								$this->input['wss_'. $group . '_' . $key] = $option;
							}
						}
					}
					$this->write_htaccess();
					break;
/* disable gzip CSS */
				case 9:
					$this->save_option("['gzip']['css']", 0);
					$this->save_option("['htaccess']['enabled']", 0);
					$this->input['wss_htaccess_enabled'] = 0;
					$this->write_htaccess();
					break;
/* enable combine JS */
				case 10:
					$this->save_option("['minify']['javascript']", 1);
					$this->save_option("['external_scripts']['on']", 1);
					$this->save_option("['external_scripts']['inline']", 1);
					$this->save_option("['external_scripts']['head_end']", 1);
					break;
/* disable combine inline JS */
				case 11:
					$this->save_option("['external_scripts']['inline']", 0);
					break;
/* move JS to head */
				case 12:
					$this->save_option("['external_scripts']['head_end']", 0);
					break;
/* move JS to /head, disable combine external JS */
				case 13:
					$this->save_option("['external_scripts']['head_end']", 1);
					$this->save_option("['external_scripts']['on']", 0);
					break;
/* exclude JS from combine */
				case 14:
					$this->save_option("['external_scripts']['ignore_list']", htmlspecialchars($wizard_options));
					break;
/* enable minify JS with JSMin */
				case 15:
					$this->save_option("['minify']['with_jsmin']", 1);
					break;
/* enable minify JS with YUI Compressor */
				case 16:
					$this->save_option("['minify']['with_jsmin']", 0);
					$this->save_option("['minify']['with_yui']", 1);
					break;
/* enable minify JS with Packer */
				case 17:
					$this->save_option("['minify']['with_jsmin']", 0);
					$this->save_option("['minify']['with_yui']", 0);
					$this->save_option("['minify']['with_packer']", 1);
					break;
/* disable minify JS */
				case 18:
					$this->save_option("['minify']['with_jsmin']", 0);
					$this->save_option("['minify']['with_yui']", 0);
					$this->save_option("['minify']['with_packer']", 0);
					break;
/* enable duplicates removal */
				case 19:
					$this->save_option("['external_scripts']['duplicates']", 1);
					break;
/* disable duplicates removal */
				case 20:
					$this->save_option("['external_scripts']['duplicates']", 0);
					break;
/* disable combine JS */
				case 21:
					$this->save_option("['minify']['javascript']", 0);
					break;
/* enable gzip JS */
				case 22:
					$this->save_option("['gzip']['javascript']", 1);
					$this->save_option("['htaccess']['enabled']", 1);
					$this->compress_options['active'] = 1;
					$this->compress_options['htaccess']['enabled'] = 1;
					$this->compress_options['gzip']['javascript'] = 1;
					foreach ($this->compress_options as $group => $options) {
						if (is_array($options)) {
							foreach ($options as $key => $option) {
								$this->input['wss_'. $group . '_' . $key] = $option;
							}
						}
					}
					$this->write_htaccess();
					break;
/* disable gzip JS */
				case 23:
					$this->save_option("['gzip']['javascript']", 0);
					$this->save_option("['htaccess']['enabled']", 0);
					$this->input['wss_htaccess_enabled'] = 0;
					$this->write_htaccess();
					break;
/* enable minify HTML */
				case 24:
					$this->save_option("['minify']['page']", 1);
					break;
/* disable minify HTML */
				case 25:
					$this->save_option("['minify']['page']", 0);
					break;
/* enable remove HTML comments */
				case 26:
					$this->save_option("['minify']['html_comments']", 1);
					break;
/* disable remove HTML comments */
				case 27:
					$this->save_option("['minify']['html_comments']", 0);
					break;
/* enable plain string + no mtime */
				case 28:
					$this->save_option("['performance']['mtime']", 1);
					$this->save_option("['performance']['plain_string']", 1);
					break;
/* disable plain string */
				case 29:
					$this->save_option("['performance']['plain_string']", 0);
					break;
/* enable gzip HTML */
				case 30:
					$this->save_option("['gzip']['page']", 1);
					$this->save_option("['gzip']['fonts']", 1);
					$this->save_option("['gzip']['cookie']", 1);
					$this->save_option("['gzip']['noie']", 1);
					$this->save_option("['htaccess']['enabled']", 1);
					$this->compress_options['active'] = 1;
					$this->compress_options['htaccess']['enabled'] = 1;
					$this->compress_options['gzip']['page'] = 1;
					foreach ($this->compress_options as $group => $options) {
						if (is_array($options)) {
							foreach ($options as $key => $option) {
								$this->input['wss_'. $group . '_' . $key] = $option;
							}
						}
					}
					$this->write_htaccess();
					break;
/* disable gzip HTML */
				case 31:
					$this->save_option("['gzip']['page']", 0);
					$this->save_option("['gzip']['fonts']", 0);
					$this->save_option("['gzip']['cookie']", 0);
					$this->save_option("['gzip']['noie']", 0);
					$this->save_option("['htaccess']['enabled']", 0);
					foreach ($this->compress_options as $group => $options) {
						if (is_array($options)) {
							foreach ($options as $key => $option) {
								$this->input['wss_'. $group . '_' . $key] = $option;
							}
						}
					}
					$this->input['wss_htaccess_enabled'] = 0;
					$this->write_htaccess();
					break;
/* enable data:URI + mhtml + separation */
				case 32:
					$this->save_option("['data_uris']['on']", 1);
					$this->save_option("['data_uris']['separate']", 1);
					$this->save_option("['data_uris']['domloaded']", 1);
					$this->save_option("['data_uris']['mhtml']", 1);
					break;
/* disable separation data:URI */
				case 33:
					$this->save_option("['data_uris']['separate']", 0);
					$this->save_option("['data_uris']['domloaded']", 0);
					break;
/* disable data:URI + mhtml */
				case 34:
					$this->save_option("['data_uris']['on']", 0);
					$this->save_option("['data_uris']['separate']", 0);
					$this->save_option("['data_uris']['domloaded']", 0);
					$this->save_option("['data_uris']['mhtml']", 0);
					break;
/* set JS host */
				case 35:
					$this->save_option("['minify']['javascript_host']", htmlspecialchars($wizard_options));
					break;
/* set CSS host */
				case 36:
					$this->save_option("['minify']['css_host']", htmlspecialchars($wizard_options));
					break;
/* set IMG host */
				case 37:
					$this->save_option("['parallel']['allowed_list']", htmlspecialchars($wizard_options));
					break;
/* enable CDN */
				case 38:
					$this->save_option("['parallel']['custom']", 0);
					$this->save_option("['parallel']['css']", $this->compress_options['minify']['css_host'] ? 1 : 0);
					$this->save_option("['parallel']['javascript']", $this->compress_options['minify']['javascript_host'] ? 1 : 0);
					$this->save_option("['parallel']['enabled']", $this->compress_options['parallel']['allowed_list'] ? 1 : 0);
					break;
/* disable CDN */
				case 39:
					$this->save_option("['parallel']['custom']", 0);
					$this->save_option("['parallel']['css']", 0);
					$this->save_option("['parallel']['javascript']", 0);
					$this->save_option("['parallel']['enabled']", 0);
					$this->save_option("['minify']['css_host']", '');
					$this->save_option("['minify']['javascript_host']", '');
					$this->save_option("['parallel']['allowed_list']", '');
					break;
/* enable HTML Sprites */
				case 40:
					$this->save_option("['css_sprites']['html_sprites']", 1);
					$this->save_option("['css_sprites']['html_limit']", 16);
					$this->save_option("['css_sprites']['html_page']", 1);
					break;
/* enable HTML Sprites restriction */
				case 41:
					$this->save_option("['css_sprites']['html_sprites']", 1);
					$this->save_option("['css_sprites']['html_limit']", round($wizard_options));
					$this->save_option("['css_sprites']['html_page']", 1);
					break;
/* disable HTML Sprites */
				case 42:
					$this->save_option("['css_sprites']['html_sprites']", 0);
					break;
/* move JS to /body */
				case 43:
					$this->save_option("['unobtrusive']['body']", 1);
					break;
/* move JS to /head */
				case 44:
					$this->save_option("['unobtrusive']['body']", 0);
					break;
/* enable unobtrusive JavaScript  */
				case 45:
					$wizard_options = round($wizard_options);
					$this->save_option("['unobtrusive']['informers']", ($wizard_options & 1) ? 1 : 0);
					$this->save_option("['unobtrusive']['counters']", ($wizard_options & 2) ? 1 : 0);
					$this->save_option("['unobtrusive']['ads']", ($wizard_options & 4) ? 1 : 0);
					$this->save_option("['unobtrusive']['iframes']", ($wizard_options & 8) ? 1 : 0);
					break;
/* disable unobtrusive JavaScript */
				case 46:
					$this->save_option("['unobtrusive']['informers']", 0);
					$this->save_option("['unobtrusive']['counters']", 0);
					$this->save_option("['unobtrusive']['ads']", 0);
					$this->save_option("['unobtrusive']['iframes']", 0);
					break;
				case 49:
/* enable or disable server side caching */
					$wizard_options = round($wizard_options);
					switch ($wizard_options) {
						case 1:
							$this->save_option("['html_cache']['enabled']", 0);
							$this->save_option("['sql_cache']['enabled']", 0);
							break;
						case 2:
							$this->save_option("['html_cache']['enabled']", 1);
							$this->save_option("['sql_cache']['enabled']", 1);
							break;
					}
					$this->install_clean_cache(0, 1);
					break;
/* save config */
				case 51:
					$this->save_option("['active']", 1);
					$this->compress_options['active'] = 1;
					foreach ($this->compress_options as $group => $options) {
						if (is_array($options)) {
							foreach ($options as $key => $option) {
								$this->input['wss_'. $group . '_' . $key] = $option;
							}
						}
					}
/* detect .htaccess */
					$this->get_modules();
					if (count($this->apache_modules)) {
						$this->save_option("['htaccess']['enabled']", 1);
						$this->input['wss_htaccess_enabled'] = 1;
						$this->compress_options['htaccess']['enabled'] = 1;
						$this->write_htaccess();
					}
/* create fake options */
					$this->input['wss_combine_css'] = $this->input['wss_minify_css'] * 2 + $this->input['wss_minify_css_body'];
					$this->input['wss_minify_javascript'] = $this->input['wss_minify_javascript'] * 2 + $this->input['wss_minify_javascript_body'];
					$this->input['wss_minify_js'] = $this->input['wss_minify_with_packer'] ? 4 :
						($this->input['wss_minify_with_yui'] ? 3 :
						($this->input['wss_minify_with_jsmin'] ? 2 : 1));
/* define configuration file */
					if (@is_file($this->basepath . 'config.auto.php')) {
						$i = 1;
						while (@is_file($this->basepath . 'config.auto'. ($i++) .'.php')) {}
						$this->input['wss_config'] = 'auto' . ($i - 1);
					} else {
						$this->input['wss_config'] = 'auto';
					}
					$this->save_option("['config']", $this->input['wss_config']);
					$this->input['wss_title'] = 'Auto Config';
					$this->input['wss_description'] = 'Created by WEBO Wizard on ' . date("Y-m-d");
					$this->input['wss_page'] = 'install_options';
					$this->set_options();
					break;
			}
		} else {
/* show generic page */
		}
		$page_variables = array(
			"version" => $this->version,
			"premium" => $this->premium,
			"skip_render" => $this->skip_render,
			"wizard_mode" => $wizard,
			"website_root" => str_replace($this->compress_options['document_root'], "/", $this->compress_options['website_root'])
		);
		$this->view->render("install_wizard", $page_variables);
	}

	/*
	* Renders awards page
	*
	**/
	function install_awards () {
		$info = $this->calculate_awards();
		$level_options = array(
			array(
				array('gzip', 'clientside', 'combinecss'),
				array('minify', 'combine_js', 'data_uri', 'css_sprites'),
				array('unobtrusive', 'multiple_hosts', 'performance')),
			array(
				array('gzip', 'minify', 'htaccess'),
				array('combinecss', 'combine_js'),
				array('data_uri', 'css_sprites', 'performance')),
			array(
				array('gzip', 'clientside', 'htaccess', 'combinecss'),
				array('minify', 'combine_js', 'data_uri', 'css_sprites', 'serverside'),
				array('unobtrusive', 'multiple_hosts', 'performance')),
			array(
				array('combinecss', 'combine_js'),
				array('data_uri', 'css_sprites'),
				array('multiple_hosts', 'css_sprites')),
			array(
				array('htaccess', 'gzip', 'performance', 'unobtrusive'),
				array('minify', 'clientside', 'combinecss', 'combine_js'),
				array('css_sprites', 'data_uri', 'multiple_hosts'))
		);
		$page_variables = array(
			"version" => $this->version,
			"premium" => $this->premium,
			"skip_render" => $this->skip_render,
			"level1" => $info[0],
			"level2" => $info[1],
			"level3" => $info[2],
			"level4" => $info[3],
			"level5" => $info[4],
			"local" => @is_file($this->compress_options['css_cachedir'] . 'webo-site-speedup250.png'),
			"cachedir" => str_replace($this->compress_options['document_root'], "/",
				$this->compress_options['css_cachedir']),
			"short_link" => $info[10],
			"level_options" => $level_options,
			"host" => $this->compress_options['host'],
			"options" => $info[5]
		);
		$this->view->render("install_awards", $page_variables);
	}

	/*
	* Renders account page
	*
	**/
	function install_account () {
		$allow = $this->compress_options['optimization'];
		$email = $this->compress_options['email'];
		$name = $this->compress_options['name'];
		$license = $this->compress_options['license'];
		$submit = empty($this->input['wss_Submit']) ? '' : $this->input['wss_Submit'];
		$error = array();
		if (!empty($submit)) {
			$email = $this->input['wss_email'];
			$allow = empty($this->input['wss_allow']) ? 0 : 1;
			$license = trim($this->input['wss_license']);
			$name = $this->input['wss_name'];
			if (!$this->internal && (empty($this->input['wss_password']) ||
				md5($this->input['wss_password']) !=
				$this->input['wss__password'])) {
				$error[1] = 1;
			}
			if (empty($this->input['wss_email']) ||
				!preg_match("/.+@.+\..+/", $this->input['wss_email'])) {
				$error[2] = 1;
			}
			if (!empty($this->input['wss_new']) &&
				(empty($this->input['wss_confirm']) ||
					$this->input['wss_confirm'] !=
						$this->input['wss_new']) && !$this->internal) {
				$error[3] = 1;
			}
			$this->premium = $this->view->validate_license($license);
/* save new options */
			if (!count($error)) {
				$this->save_option("['email']", htmlspecialchars($email));
				$this->save_option("['optimization']", $allow);
				$this->save_option("['license']", htmlspecialchars($license));
				$this->save_option("['name']", htmlspecialchars($name));
				if (!empty($this->input['wss_new'])) {
					$this->save_option("['password']", md5($this->input['wss_new']));
				}
			}
		}
		$expires = -1;
		if (empty($this->premium) && !empty($license)) {
			$error[4] = 1;
		} elseif (@is_file($this->basepath . 'cache/wo')) {
			$expires = @file_get_contents($this->basepath . 'cache/wo');
		}
		$page_variables = array(
			"version" => $this->version,
			"premium" => $this->premium,
			"submit" => $submit,
			"expires" => $expires,
			"allow" => $allow,
			"email" => $email,
			"name" => $name,
			"license" => $license,
			"error" => $error,
			"skip_render" => $this->skip_render,
			"internal" => $this->internal,
			"language" => $this->language,
			"root" => str_replace($this->compress_options['document_root'], '/', $this->basepath)
		);
		$this->view->render("install_account", $page_variables);
	}
	
	/*
	* Renders about page
	*
	**/
	function install_about () {
		$email = empty($this->input['wss_email']) ? '' : $this->input['wss_email'];
		$message = empty($this->input['wss_message']) ? '' : $this->input['wss_message'];
		$submit = empty($this->input['wss_Submit']) ? 0 : 1;
		$error = array();
		if ($submit) {
			if (empty($email) ||
				!preg_match("/.+@.+\..+/", $email)) {
				$error[1] = 1;
			}
			if (empty($message)) {
				$error[2] = 1;
			}
/* send a email to info@webo.name */
			if (!count($error)) {
				$this->send_message($email, $message);
			}
		}
		$page_variables = array(
			"version" => $this->version,
			"premium" => $this->premium,
			"email" => $email,
			"message" => $message,
			"error" => $error,
			"submit" => $submit,
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_about", $page_variables);
	}

	/*
	* Sends a message from given e-mail
	*
	**/
	function send_message ($email, $message, $uninstall = false) {
		$headers = 'From: ' . $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";
		$headers .= 'Content-Type: text/plain; charset=utf-8'."\r\n";
		$headers .= 'Content-Transfer-Encoding: base64';
/* general info */
		$message = "On " . date("Y-m-d") . " at " . date("H:i:s") . " " .
			$this->compress_options['name'] .
			" (" . $this->compress_options['email'] . ") send a message: " .
			($uninstall ? "(after uninstalltion) " : "") . "\r\n" . $message;
/* application info */
		$this->cms_version = $this->system_info($this->compress_options['document_root']);
		$message .= "\r\n\r\nWEBO Site SpeedUp " . $this->version . " was " .
			($this->compress_options['active'] ? "enabled" : "disabled") .
			" on " . $this->cms_version . ($this->internal ? " (plugin)" : "") .
			" and has the following warnings / errors:\r\n";
/* get basic errors / warnings */
		$page_variables = $this->dashboard_system(1);
		if (count($page_variables['errors'])) {
			foreach ($page_variables['errors'] as $key => $value) {
				if (empty($value)) {
					$message .= "* " . $key . "\r\n";
				}
			}
		}
		if (count($page_variables['warnings'])) {
			foreach ($page_variables['warnings'] as $key => $value) {
				if (empty($value)) {
					$message .= "* " . $key . "\r\n";
				}
			}
		}
		$message .= "\r\n\r\nActive configuration (" .
			$this->compress_options['config'] .
			") parameters:\r\n" .
			"License: " . $this->compress_options['license'] . "\r\n" .
			"HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\r\n" . 
			"DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\r\n" .
			"Configuration options:\r\n" .
			"document_root => " . $this->compress_options['document_root'] . "\r\n" .
			"website_root => " . $this->compress_options['website_root'] . "\r\n" .
			"javascript_cachedir => " . $this->compress_options['javascript_cachedir'] . "\r\n" .
			"css_cachedir => " . $this->compress_options['css_cachedir'] . "\r\n" .
			"html_cachedir => " . $this->compress_options['html_cachedir'] . "\r\n" .
			"restricted => " . $this->compress_options['restricted'] . "\r\n" .
			"plugins => " . $this->compress_options['plugins'] . "\r\n" .
			"host => " . $this->compress_options['host'] . "\r\n";
		foreach ($this->compress_options as $ko => $opts) {
			if (is_array($opts)) {
				foreach ($opts as $k => $v) {
					$message .= $ko . " "  . $k  . " => " . $v . "\r\n";
				}
			}
		}
		@mail('info@webo.name', "=?latin-1?B?" .
			base64_encode('New message from WEBO Site SpeedUp') . "?=",
				base64_encode($message), $headers);
	}

	/*
	* Renders cache refresh (from cache page)
	*
	**/
	function install_renew () {
		$this->write_progress(1);
		$this->install_clean_cache(0, 1);
		$this->save_option("['performance']['cache_version']", 0);
/* need to make these async requests safe somehow
		$this->save_option("['active']", 0); */
		$this->chained_load(str_replace(
			$this->compress_options['document_root'], "/",
			$this->compress_options['website_root']));
		$this->save_option("['active']", $this->compress_options['active']);
		$this->save_option("['performance']['cache_version']",
			$this->compress_options['performance']['cache_version']);
		$this->install_system();
	}

	/*
	* Renders cache refresh (from dashboard)
	*
	**/
	function install_refresh () {
		$this->write_progress(1);
		$this->install_clean_cache(0, 1);
		$this->save_option("['performance']['cache_version']", 0);
/* need to make these async requests safe somehow
		$this->save_option("['active']", 0); */
		$this->chained_load(str_replace(
			$this->compress_options['document_root'], "/" ,
			$this->compress_options['website_root']));
		$this->save_option("['active']", $this->compress_options['active']);
		$this->save_option("['performance']['cache_version']",
			$this->compress_options['performance']['cache_version']);
		$this->install_dashboard();
	}

	/*
	* Renders change of application status
	*
	**/
	function install_status () {
		if (empty($this->compress_options['active'])) {
			$this->chained_load(str_replace(
				$this->compress_options['document_root'], "/" ,
				$this->compress_options['website_root']));
			$this->compress_options['active'] = 1;
			$this->save_option("['active']", 1);
			$options = $this->get_options();
			$this->input = array();
			foreach ($options as $group) {
				if (is_array($group)) {
					foreach ($group as $key => $option) {
						if (is_array($option)) {
							$this->input['wss_' . $key] = $option['value'];
						}
					}
				}
			}
			$this->set_options();
			$this->write_htaccess();
			if (!@is_file($this->basepath . $this->index_after) && $this->premium > 1) {
				$this->view->download($this->webo_grade . '&refresh=on',
					$this->basepath . $this->index_after, 2);
			}
		} else {
			$this->input = array(
				'wss_htaccess_enabled' => $this->compress_options['htaccess']['enabled'],
				'wss_htaccess_mod_rewrite' => $this->compress_options['htaccess']['mod_rewrite'],
				'wss_far_future_expires_css' => $this->compress_options['far_future_expires']['css'],
				'wss_far_future_expires_images' => $this->compress_options['far_future_expires']['images'],
				'wss_far_future_expires_javascript' => $this->compress_options['far_future_expires']['javascript']
			);
			$this->compress_options['active'] = 0;
			$this->write_htaccess();
			$this->save_option("['active']", 0);
		}
		$this->install_dashboard();
	}

	/*
	* Return info about current optimization awards
	*
	**/
	function dashboard_awards () {
		$info = $this->calculate_awards();
		$page_variables = array(
			"version" => $this->version,
			"premium" => $this->premium,
			"skip_render" => $this->skip_render,
			"level1" => $info[0],
			"level2" => $info[1],
			"level3" => $info[2],
			"level4" => $info[3],
			"level5" => $info[4],
			"options" => $info[5],
			"grade" => $info[6],
			"files" => $info[7],
			"size" => $info[8],
			"speedup" => $info[9],
			"short_link" => $info[10],
			"local" => @is_file($this->compress_options['css_cachedir'] . 'webo-site-speedup250.png'),
			"cachedir" => str_replace($this->compress_options['document_root'], "/",
				$this->compress_options['css_cachedir'])
		);
		$this->view->render("dashboard_awards", $page_variables);
	}

	/*
	* Return size and number of specific files
	*
	**/
	function dashboard_cache_size ($mask, $number = false) {
		$return = 0;
		$files = glob($mask);
		if (is_array($files)) {
			foreach ($files as $filename) {
				$return += @filesize($filename);
				if ($number) {
					$number++;
				}
			}
		}
		return $number ? array($return, $number - 1) : $return;
	}

	/*
	* Renders block with cache information for dashboard
	*
	**/
	function dashboard_cache () {
		$res = $css = $php = $css_php = $js = $js_php = $html = $sprites = $imgs = 0;
/* get size of JS files */
		if (!empty($this->compress_options['javascript_cachedir'])) {
			@chdir($this->compress_options['javascript_cachedir']);
			foreach ($this->cache_types['js'] as $mask) {
				$js += $this->dashboard_cache_size($mask);
			}
			foreach ($this->cache_types['js_php'] as $mask) {
				$js_php += $this->dashboard_cache_size($mask);
			}
			foreach ($this->cache_types['scripts'] as $mask) {
				$php += $this->dashboard_cache_size($mask);
			}
		}
/* get size of CSS files */
		if (!empty($this->compress_options['css_cachedir'])) {
			@chdir($this->compress_options['css_cachedir']);
			foreach ($this->cache_types['css'] as $mask) {
				$css += $this->dashboard_cache_size($mask);
			}
			foreach ($this->cache_types['css_php'] as $mask) {
				$css_php += $this->dashboard_cache_size($mask);
			}
			foreach ($this->cache_types['res'] as $mask) {
				$res += $this->dashboard_cache_size($mask);
			}
/* exclude from CSS resource files */
			$css -= $res;
/* get CSS Sprites size */
			foreach ($this->cache_types['sprites'] as $mask) {
				$sprites += $this->dashboard_cache_size($mask);
			}
/* get size of images */
			foreach ($this->cache_types['imgs'] as $mask) {
				$imgs += $this->dashboard_cache_size($mask);
			}
/* Exclude Sprites from images */
			$imgs -= $sprites;
			if ($this->compress_options['css_cachedir'] !=
				$this->compress_options['javascript_cachedir']) {
				foreach ($this->cache_types['scripts'] as $mask) {
					$php += $this->dashboard_cache_size($mask);
				}
			}
		}
/* get size of HTML files */
		if (!empty($this->compress_options['html_cachedir'])) {
			@chdir($this->compress_options['html_cachedir']);
			foreach ($this->cache_types['html'] as $mask) {
				$html += $this->cache_engine->get_cache_size($mask);
			}
			if ($this->compress_options['html_cachedir'] !=
				$this->compress_options['javascript_cachedir'] &&
				$this->compress_options['html_cachedir'] !=
				$this->compress_options['css_cachedir']) {
				foreach ($this->cache_types['scripts'] as $mask) {
					$php += $this->dashboard_cache_size($mask);
				}
			}
		}
/* distribute general PHP files between CSS and JS */
		if (!empty($this->compress_options['css_cachedir']) &&
			!empty($this->compress_options['javascript_cachedir']) &&
			$this->compress_options['javascript_cachedir'] ==
			$this->compress_options['css_cachedir']) {
				$css_php += $php / 3;
				$js_php += $php * 2 / 3;
		}
		$css += $css_php;
		$js += $js_php;
		foreach ($this->cache_types as $key => $val) {
			if (!empty($$key)) {
				$$key = round($$key / 1024);
			}
		}
		$page_variables = array(
			'css' => $css,
			'js' => $js,
			'res' => $res,
			'html' => $html,
			'sprites' => $sprites,
			'imgs' => $imgs,
			'total' => $css + $js + $res + $html + $sprites + $imgs,
			"skip_render" => $this->skip_render
		);
		$this->view->render("dashboard_cache", $page_variables);
	}

	/*
	* Check Web Optimizer acceleration
	*
	**/	
	function dashboard_speed () {
		$this->check_acceleration();
		$saved_kb = $saved_s = $s_after = $s_before = $kb_after = $kb_before = 0;
		$before = @file_get_contents($this->basepath . $this->index_before);
		$before = strpos($before, '<b>Warning') ? '' : $before;
		$after = @file_get_contents($this->basepath . $this->index_after);
		$after = strpos($after, '<b>Warning') ? '' : $after;
/* parse files' content for calculated load speed */
		if (!empty($before) && !empty($after)) {
			$s_before = preg_replace("!.*<high>([0-9\.]+)</high>.*!", "$1", $before);
			$kb_before = round(preg_replace("!.*</number><size>([0-9]+)</size>.*!", "$1", $before));
			if (strpos($after, '<high>')) {
				$s_after = preg_replace("!.*<high>([0-9\.]+)</high>.*!", "$1", $after);
				$kb_after = round(preg_replace("!.*</number><size>([0-9]+)</size>.*!", "$1", $after));
			}
			if (!empty($kb_before) && !empty($kb_after)) {
				$saved_s = $s_before - $s_after;
				$saved_kb = $kb_before - $kb_after;
/* do not show negative numbers */
				if ($saved_s <= 0) {
					$s_after = 0;
				}
				if ($saved_kb <= 0) {
					$kb_after = 0;
				}
			}
		}
/* set variables */
		$page_variables = array(
			's_after' => $s_after,
			's_before' => $s_before,
			'kb_after' => $kb_after,
			'kb_before' => $kb_before,
			'premium' => $this->premium,
			"skip_render" => $this->skip_render
		);
/* Output data */
		$this->view->render("dashboard_speed", $page_variables);
	}

	/*
	* Check WEBO Site SpeedUp options
	*
	**/
	function options_count () {
/* get available Apache modules */
		$this->get_modules();
/* check if .htaccess is avaiable */
		$htaccess_available = count($this->apache_modules) ? 1 : 0;
		$apache2 = 0;
/* Apache/1 indicates (for sure) Apache 1.3.0-1.3.11. Apache 1.3.12+ has
   Prod in ServerTokens support, so can be false detected as Apache2 */
		if (function_exists('apache_get_version')) {
			$apache2 = strpos(apache_get_version(), "/1") ? 0 : 1;
		}
/* fill array with errors */
		$errors = array();
		$value = 5;
/* first priority issues */
		if (empty($this->compress_options['css_sprites']['enabled']) &&
			$this->premium > 1) {
				$errors['css_sprites_enabled'] = $value;
		}
		if (empty($this->compress_options['parallel']['enabled']) &&
			$this->premium > 1) {
				$errors['parallel_enabled'] = $value;
		}
		if (empty($this->compress_options['performance']['mtime']) &&
			$this->premium > 0) {
			$errors['performance_mtime'] = $value;
		}
		if (empty($this->compress_options['performance']['plain_string']) &&
			$this->premium > 1) {
				$errors['performance_plain_string'] = $value;
		}
		if (empty($this->compress_options['htaccess']['enabled']) ||
			!$htaccess_available) {
				$errors['htaccess_enabled'] = $value;
		}
/* second priority issues */
		$value = 4;
		if (empty($this->compress_options['unobtrusive']['ads']) &&
			$this->premium > 1) {
				$errors['unobtrusive_ads'] = $value;
		}
		if (empty($this->compress_options['unobtrusive']['informers']) &&
			$this->premium > 1) {
				$errors['unobtrusive_informers'] = $value;
		}
		if (empty($this->compress_options['gzip']['cookie']) &&
			$this->premium > 1) {
				$errors['gzip_cookie'] = $value;
		}
		if (empty($this->compress_options['unobtrusive']['body']) &&
			$this->premium > 1) {
				$errors['unobtrusive_body'] = $value;
		}
		if ((empty($this->compress_options['htaccess']['mod_deflate']) ||
			!in_array('mod_deflate', $this->apache_modules))) {
				$errors['htaccess_mod_deflate'] = $value;
		} elseif (!in_array('mod_deflate', $this->apache_modules) &&
			(empty($this->compress_options['htaccess']['mod_gzip']) ||
				!in_array('mod_gzip', $this->apache_modules)) &&
			!$apache) {
				$errors['htaccess_mod_gzip'] = $value;
		}
/* third priority issues */
		$value = 3;
		if (empty($this->compress_options['data_uris']['on']) &&
			$this->premium > 0) {
				$errors['data_uris_on'] = $value;
		}
		if (empty($this->compress_options['performance']['restore_properties']) &&
			$this->premium > 1) {
			$errors['performance_restore_properties'] = $value;
		}
		if (empty($this->compress_options['unobtrusive']['informers']) &&
			$this->premium > 1) {
				$errors['unobtrusive_informers'] = $value;
		}
		if (empty($this->compress_options['gzip']['page'])) {
			$errors['gzip_page'] = $value;
		}
		if (empty($this->compress_options['minify']['javascript'])) {
			$errors['minify_javascript'] = $value;
		}
		if (empty($this->compress_options['unobtrusive']['iframes']) &&
			$this->premium > 1) {
				$errors['unobtrusive_iframes'] = $value;
		}
/* fourth priority issues */
		$value = 2;
		if (empty($this->compress_options['data_uris']['mhtml']) &&
			$this->premium > 0) {
				$errors['data_uris_mhtml'] = $value;
		}
		if (empty($this->compress_options['css_sprites']['html_sprites']) &&
			$this->premium > 1) {
				$errors['css_sprites_html_sprites'] = $value;
		}
		if (empty($this->compress_options['minify']['css'])) {
			$errors['combine_css'] = $value;
		}
		if (empty($this->compress_options['htaccess']['mod_expires']) ||
			!in_array('mod_expires', $this->apache_modules)) {
				$errors['htaccess_mod_expires'] = $value;
		}
		if (empty($this->compress_options['gzip']['css'])) {
			$errors['gzip_css'] = $value;
		}
		if (empty($this->compress_options['gzip']['javascript'])) {
			$errors['gzip_javascript'] = $value;
		}
/* fifth priority issues */
		$value = 1;
		if (empty($this->compress_options['htaccess']['mod_rewrite']) ||
			!in_array('mod_rewrite', $this->apache_modules)) {
				$errors['htaccess_mod_rewrite'] = $value;
		}
		if (empty($this->compress_options['far_future_expires']['css'])) {
			$errors['far_future_expires_css'] = $value;
		}
		if (empty($this->compress_options['far_future_expires']['javascript'])) {
			$errors['far_future_expires_javascript'] = $value;
		}
		if (empty($this->compress_options['far_future_expires']['images'])) {
			$errors['far_future_expires_images'] = $value;
		}
		if (empty($this->compress_options['external_scripts']['on'])) {
			$errors['external_scripts_on'] = $value;
		}
		if (empty($this->compress_options['htaccess']['mod_headers']) ||
			!in_array('mod_headers', $this->apache_modules)) {
				$errors['htaccess_mod_headers'] = $value;
		}
		if (empty($this->compress_options['minify']['javascript'])) {
			$errors['minify_javascript'] = $value;
		}
		if (empty($this->compress_options['minify']['page'])) {
			$errors['minify_page'] = $value;
		}
		if (empty($this->compress_options['minify']['html_comments']) &&
			$this->premium > 1) {
			$errors['minify_html_comments'] = $value;
		}
		if (empty($this->compress_options['minify']['html_one_string']) &&
			$this->premium > 1) {
			$errors['minify_html_one_string'] = $value;
		}
		if (empty($this->compress_options['external_scripts']['css'])) {
			$errors['external_scripts_css'] = $value;
		}
		if (empty($this->compress_options['external_scripts']['inline'])) {
			$errors['external_scripts_inline'] = $value;
		}
		if (empty($this->compress_options['external_scripts']['css_inline'])) {
			$errors['external_scripts_css_inline'] = $value;
		}
		if (empty($this->compress_options['gzip']['fonts'])) {
			$errors['gzip_fonts'] = $value;
		}
		if (empty($this->compress_options['minify']['page'])) {
			$errors['minify_page'] = $value;
		}
		if (empty($this->compress_options['htaccess']['mod_setenvif']) ||
			!in_array('mod_setenvif', $this->apache_modules)) {
				$errors['htaccess_mod_setenvif'] = $value;
		}
		if (empty($this->compress_options['htaccess']['mod_mime']) ||
			!in_array('mod_mime', $this->apache_modules)) {
				$errors['htaccess_mod_mime'] = $value;
		}
		if (empty($this->compress_options['far_future_expires']['fonts'])) {
			$errors['far_future_expires_fonts'] = $value;
		}
		if (empty($this->compress_options['far_future_expires']['video'])) {
			$errors['far_future_expires_video'] = $value;
		}
		if (empty($this->compress_options['far_future_expires']['static'])) {
			$errors['far_future_expires_static'] = $value;
		}
		if (empty($this->compress_options['minify']['with_jsmin']) &&
			empty($this->compress_options['minify']['with_yui']) &&
			empty($this->compress_options['minify']['with_packer']) &&
			empty($this->compress_options['minify']['with_google'])) {
			$errors['minify_js'] = $value;
		}
		return $errors;
	}

	/*
	* Check Web Optimizer options
	*
	**/
	function dashboard_options () {
		$errors = $this->options_count();
/* count delta */
		$deltas = array(58, 48, 0, 0, 0, 0, 0, 0, 0, 0, 0);
		$delta = $deltas[round($this->premium)];
		foreach ($errors as $key => $value) {
			$delta += $value;
		}
/* overwrite delta */
		if (!empty($this->compress_options['points'])) {
			$delta = 100 - round($this->compress_options['points']);
			$delta = $delta < 0 ? 0 : ($delta > 100 ? 100 : $delta);
		}
/* set variables */
		$page_variables = array(
			'errors' => $errors,
			'delta' => $delta,
			'premium' => $this->premium,
			"skip_render" => $this->skip_render
		);
/* Output data */
		$this->view->render("dashboard_options", $page_variables);
	}

	/*
	* Check server requirements for Web Optimizer
	*
	**/
	function dashboard_system ($return = false) {
/* get available Apache modules */
		$this->get_modules();
/* get PHP extensions */
		$extensions = @get_loaded_extensions();
/* get GDlib info */
		$gd = function_exists('gd_info') ? gd_info() : array();
/* set default paths */
		$this->view->set_paths();
/* calculate directories */
		$javascript_cachedir = empty($this->compress_options['javascript_cachedir']) ? $this->view->paths['full']['current_directory'] . 'cache/' : $this->compress_options['javascript_cachedir'];
		$css_cachedir = empty($this->compress_options['css_cachedir']) ? $this->view->paths['full']['current_directory'] . 'cache/' : $this->compress_options['css_cachedir'];
		$html_cachedir = empty($this->compress_options['html_cachedir']) ? $this->view->paths['full']['current_directory'] . 'cache/' : $this->compress_options['html_cachedir'];
		$website_root = empty($this->compress_options['website_root']) ? $this->view->paths['absolute']['document_root'] : $this->compress_options['website_root'];
		$document_root = empty($this->compress_options['document_root']) ? $this->view->paths['full']['document_root'] : $this->compress_options['document_root'];
/* check for YUI */
		$YUI_available = 0;
		if ((empty($_SERVER['SERVER_SOFTWARE']) || !strpos($_SERVER['SERVER_SOFTWARE'], 'IIS')) &&
			is_file($this->basepath . 'libs/php/class.yuicompressor.php')) {
				require_once($this->basepath . 'libs/php/class.yuicompressor.php');
				$YUI = new YuiCompressor($this->compress_options['javascript_cachedir'], $this->basepath);
				$YUI_checked = $YUI->check();
		}
/* check if .htaccess is avaiable */
		$htaccess_available = count($this->apache_modules) ? 1 : 0;
/* check for multiple hosts */
		$hosts = empty($this->compress_options['parallel']['allowed_list']) ? $this->default_hosts : explode(" ", $this->compress_options['parallel']['allowed_list']);
		if (!empty($this->compress_options['parallel']['check'])) {
			$hosts = $this->check_hosts($hosts);
		}
/* try to get and increase memory limit */
		$memory_limit = @ini_get('memory_limit');
/* 64M must enough for any operations with images */
		if (round(str_replace("M", "000000", str_replace("K", "000", $memory_limit))) < 64000000) {
			@ini_set('memory_limit', '64M');
			$memory_limit = @ini_get('memory_limit');
		}
		$apache2 = $nginx = $iss = 0;
		if (function_exists('apache_get_version')) {
			$apache2 = strpos(apache_get_version(), "/1") ? 0 : 1;
		}
		if (!empty($_SERVER['SERVER_SOFTWARE'])) {
			$nginx = strpos($_SERVER['SERVER_SOFTWARE'], 'nginx/') !== false;
			$iis = strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false;
		}
/* define caching for WordPress */
		if (!empty($this->compress_options['html_cache']['enabled']) && (strpos($this->basepath, "wp-content") !== false))
		{
			$content = @file_get_contents($this->compress_options['website_root'] . 'wp-config.php');
			if(preg_match('/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*true\s*\)\s*;/', $content))
			{
				$wp_cache_enabled = true;
			}
			else
			{
				$wp_cache_enabled = false;
			}
		}
		else
		{
			$wp_cache_enabled = true;
		}
/* check CPU usage for the website */
		$tmp_file = $this->compress_options['html_cachedir'] . 'index.tmp';
		$time = time() + microtime();
		$results = $this->view->download("http://" .
			$this->compress_options['host'] .
			str_replace($this->compress_options['document_root'], "/", $this->compress_options['website_root']) .
			'?web_optimizer_disabled=1', $tmp_file);
		$standard_delay = time() + microtime() - $time;
		$time = time() + microtime();
		$this->view->download("http://" .
			$this->compress_options['host'] .
			str_replace($this->compress_options['document_root'], "/", $this->compress_options['website_root']) .
			'?web_optimizer_debug=1', $tmp_file);
		$wss_delay = time() + microtime() - $time;
/* save default encoding */
		if (empty($this->compress_options['charset']) && !empty($results[2])) {
			$headers = strtolower($results[2]);
			if (strpos($headers, 'content-type') && ($charset = trim(preg_replace("@.*content-type:[^;]*;?(\s*charset=(.*?))?\r?\n.*@is", "$2", $headers)))) {
				$this->save_option("['charset']", $charset);
				$this->compress_options['charset'] = $charset;
			}
		}
/* check activity for the website */
		$spot = strpos(@file_get_contents($tmp_file), '<!--WSS-->') || !@filesize($tmp_file);
		@unlink($tmp_file);
		$errors = array(
			'javascript_writable' => @is_writable($javascript_cachedir),
			'css_writable' => @is_writable($css_cachedir),
			'html_writable' => @is_writable($html_cachedir),
			'config_writable' => @is_writable($this->basepath . $this->options_file),
			'memory_limit' => round($memory_limit) > 16,
			'not_active' => $spot || !$this->compress_options['footer']['spot']
		);
		$warnings = array(
			'index_writable' => @is_writable($website_root . 'index.php') ||
				$this->internal,
			'curl_possibility' => in_array('curl', $extensions) &&
				function_exists('curl_init'),
			'gzip_possibility' => in_array('zlib', $extensions) &&
				function_exists('gzencode') &&
				function_exists('gzcompress') &&
				function_exists('gzdeflate'),
			'gd_possibility' => in_array('gd', $extensions) &&
				function_exists('imagecreatetruecolor'),
			'gd_full_support' => !(in_array('gd', $extensions) &&
					function_exists('imagecreatetruecolor')) ||
				(!empty($gd['GIF Read Support']) &&
					!empty($gd['GIF Create Support']) &&
					(!empty($gd['JPEG Support']) || !empty($gd['JPG Support'])) &&
					!empty($gd['PNG Support']) &&
					!empty($gd['WBMP Support'])),
			'memory_limit' => round($memory_limit) > 32 || round($memory_limit) < 15,
			'wordpress_cache_enabled' => $wp_cache_enabled
		);
		$infos = array(
			'htaccess_writable' => !$htaccess_available ||
				@is_writable($website_root) ||
				@is_writable($website_root . '.htaccess'),
			'mod_deflate' => in_array('mod_deflate', $this->apache_modules) ||
				$nginx || $iis ||
				in_array('mod_gzip', $this->apache_modules),
			'mod_gzip' => in_array('mod_gzip', $this->apache_modules) ||
				$apache2 || $nginx || $iss ||
				in_array('mod_deflate', $this->apache_modules),
			'mod_headers' => in_array('mod_headers', $this->apache_modules) || $nginx || $iis,
			'mod_expires' => in_array('mod_expires', $this->apache_modules) || $nginx || $iis,
			'mod_mime' => in_array('mod_mime', $this->apache_modules) || $nginx || $iis,
			'mod_setenvif' => in_array('mod_setenvif', $this->apache_modules) || $nginx || $iis,
			'mod_rewrite' => in_array('mod_rewrite', $this->apache_modules) || $nginx || $iis,
			'mod_symlinks' => in_array('mod_symlinks', $this->apache_modules) || $nginx || $iis,
			'yui_possibility' => !empty($YUI_checked),
			'hosts_possibility' => count($hosts) > 0 && !empty($hosts[0]),
			'protected_mode' => (isset($_SERVER['PHP_AUTH_USER']) &&
				$this->compress_options['htaccess']['access']) ||
				$this->internal,
			'cms' => $this->system_info($website_root),
			'heavy_optimization' => !$this->compress_options['active'] ||
				($this->compress_options['performance']['mtime'] &&
				!$this->compress_options['minify']['javascript_body'] &&
				!$this->compress_options['minify']['css_body'] &&
				!$this->compress_options['minify']['with_yui'] &&
				!$this->compress_options['minify']['html_one_string']),
			'heavy_optimization2' => !$this->compress_options['active'] ||
				!$this->compress_options['performance']['mtime'] ||
				$this->compress_options['minify']['javascript_body'] ||
				$this->compress_options['minify']['css_body'] ||
				$this->compress_options['minify']['with_yui'] ||
				$this->compress_options['minify']['html_one_string'] ||
				($this->compress_options['performance']['plain_string'] &&
				!$this->compress_options['unobtrusive']['all'] &&
				!$this->compress_options['unobtrusive']['informers'] &&
				!$this->compress_options['unobtrusive']['ads'] &&
				!$this->compress_options['unobtrusive']['counters'] &&
				!$this->compress_options['unobtrusive']['iframes'] &&
				!$this->compress_options['css_sprites']['enabled'] &&
				!$this->compress_options['css_sprites']['html_sprites']),
			'large_delay' => $standard_delay < 1,
			'large_wss_delay' => $wss_delay / $standard_delay < 2 || $wss_delay < 300,
		);
		$e = $w = $i = 0;
/* count acturl troubles / warnings */
		foreach ($errors as $key => $value) {
			if (empty($value)) {
				$e++;
			}
		}
		foreach ($warnings as $key => $value) {
			if (empty($value)) {
				$w++;
			}
		}
		foreach ($infos as $key => $value) {
			if (empty($value)) {
				$i++;
			}
		}
/* set variables */
		$page_variables = array(
			'errors' => $errors,
			'warnings' => $warnings,
			'infos' => $infos,
			'e' => $e,
			'w' => $w,
			'i' => $i,
			"skip_render" => $this->skip_render
		);
		if (!$return) {
/* Output data */
			$this->view->render("dashboard_system", $page_variables);
		} else {
			return $page_variables;
		}
	}

	/**
	* Outputs page with general info about system / common actions
	* 
	**/		
	function install_system ($success = 0) {
		$this->cms_version = $this->system_info($this->view->paths['absolute']['document_root']);
		$submit = empty($this->input['wss_Submit']) ? '' : $this->input['wss_Submit'];
		$this->error = array();
		if (!empty($submit)) {
			$this->compress_options['host'] = empty($this->input['wss_host']) ?
				$this->compress_options['host'] : $this->input['wss_host'];
			$this->compress_options['charset'] = empty($this->input['wss_charset']) ?
				$this->compress_options['charset'] : $this->input['wss_charset'];
			$this->compress_options['website_root'] = empty($this->input['wss_website_root']) ?
				$this->compress_options['website_root'] : $this->input['wss_website_root'];
			$this->compress_options['document_root'] = empty($this->input['wss_document_root']) ?
				$this->compress_options['document_root'] : $this->input['wss_document_root'];
			$this->compress_options['css_cachedir'] = empty($this->input['wss_css_cachedir']) ?
				$this->compress_options['css_cachedir'] : $this->input['wss_css_cachedir'];
			$this->compress_options['javascript_cachedir'] = empty($this->input['wss_javascript_cachedir']) ?
				$this->compress_options['javascript_cachedir'] : $this->input['wss_javascript_cachedir'];
			$this->compress_options['html_cachedir'] = empty($this->input['wss_html_cachedir']) ?
				$this->compress_options['html_cachedir'] : $this->input['wss_html_cachedir'];
			$this->compress_options['htaccess']['access'] = empty($this->input['wss_htaccess_access']) ?
				0 : 1;
			$this->compress_options['username'] = empty($this->input['wss_username']) ?
				'' : $this->input['wss_username'];
			$this->compress_options['external_scripts']['user'] = empty($this->input['wss_external_scripts_user']) ?
				'' : $this->input['wss_external_scripts_user'];
			$this->compress_options['external_scripts']['pass'] = empty($this->input['wss_external_scripts_pass']) ?
				'' : $this->input['wss_external_scripts_pass'];
			$this->compress_options['restricted'] = empty($this->input['wss_restricted']) ?
				'' : str_replace(array("\r\n", "\n"), array(' ', ' '), $this->input['wss_restricted']);
			if (!@is_dir($this->compress_options['website_root'])) {
				$this->error[1] = 1;
			}
			if (!@is_dir($this->compress_options['document_root'])) {
				$this->error[2] = 1;
			}
			@mkdir($this->compress_options['css_cachedir']);
			@chmod($this->compress_options['css_cachedir'], octdec("0755"));
			if (!@is_writable($this->compress_options['css_cachedir'])) {
				$this->error[3] = 1;
			}
			@mkdir($this->compress_options['javascript_cachedir']);
			@chmod($this->compress_options['javascript_cachedir'], octdec("0755"));
			if (!@is_writable($this->compress_options['javascript_cachedir'])) {
				$this->error[4] = 1;
			}
			@mkdir($this->compress_options['html_cachedir']);
			@chmod($this->compress_options['html_cachedir'], octdec("0755"));
			if (!@is_writable($this->compress_options['html_cachedir'])) {
				$this->error[5] = 1;
			}
			if (!empty($this->compress_options['htaccess']['access']) &&
				empty($this->compress_options['username'])) {
					$this->error[6] = 1;
			} else {
				$this->protect_installation();
			}
			if ((!empty($this->compress_options['external_scripts']['user']) &&
				empty($this->compress_options['external_scripts']['pass'])) ||
				(!empty($this->compress_options['external_scripts']['pass']) &&
				empty($this->compress_options['external_scripts']['user']))) {
					$this->error[7] = 1;
			}
			if (!count($this->error)) {
/* copy some files */
				$image = $this->compress_options['footer']['image'];
				if (!empty($image)) {
					@copy($this->basepath . 'images/' . $image,
						$this->compress_options['css_cachedir'] . $image);
				}
				@copy($this->basepath . 'libs/js/wo.cookie.php',
					$this->compress_options['javascript_cachedir'] . 'wo.cookie.php');
				@copy($this->basepath . 'libs/js/yass.loader.js',
					$this->compress_options['javascript_cachedir'] . 'yass.loader.js');
				@copy($this->basepath . 'libs/php/wo.static.php',
					$this->compress_options['css_cachedir'] . 'wo.static.php');
				@copy($this->basepath . 'libs/php/0.gif',
					$this->compress_options['css_cachedir'] . '0.gif');
				$this->save_option("['host']", $this->compress_options['host']);
				$this->save_option("['charset']", $this->compress_options['charset']);
				$this->save_option("['website_root']", $this->compress_options['website_root']);
				$this->save_option("['document_root']", $this->compress_options['document_root']);
				$this->save_option("['css_cachedir']", $this->compress_options['css_cachedir']);
				$this->save_option("['javascript_cachedir']", $this->compress_options['javascript_cachedir']);
				$this->save_option("['html_cachedir']", $this->compress_options['html_cachedir']);
				$this->save_option("['htaccess']['access']", $this->compress_options['htaccess']['access']);
				$this->save_option("['username']", $this->compress_options['username']);
				$this->save_option("['external_scripts']['user']", $this->compress_options['external_scripts']['user']);
				$this->save_option("['external_scripts']['pass']", $this->compress_options['external_scripts']['pass']);
				$this->save_option("['restricted']", $this->compress_options['restricted']);
				$success = 3;
			} else {
				$success = 4;
			}
		}
		$files = array(
			'CSS' => array(),
			'JS' => array(),
			'HTML' => array(),
			'SPRITES' => array(),
			'IMAGES' => array(),
			'RESOURCES' => array(),
			'SCRIPTS' => array());
		if (!empty($this->compress_options['css_cachedir'])) {
			@chdir($this->compress_options['css_cachedir']);
			foreach ($this->cache_types['css'] as $mask) {
				$files['CSS'][$mask] = $this->dashboard_cache_size($mask, 1);
			}
			foreach ($this->cache_types['res'] as $mask) {
				$files['RESOURCES'][$mask] = $this->dashboard_cache_size($mask, 1);
			}
			foreach ($this->cache_types['imgs'] as $mask) {
				$files['IMAGES'][$mask] = $this->dashboard_cache_size($mask, 1);
			}
			foreach ($this->cache_types['sprites'] as $mask) {
				$files['SPRITES'][$mask] = $this->dashboard_cache_size($mask, 1);
			}
			foreach ($this->cache_types['scripts'] as $mask) {
				$files['SCRIPTS'][$mask] = $this->dashboard_cache_size($mask, 1);
			}
		}
		if (!empty($this->compress_options['javascript_cachedir'])) {
			@chdir($this->compress_options['javascript_cachedir']);
			foreach ($this->cache_types['js'] as $mask) {
				$files['JS'][$mask] = $this->dashboard_cache_size($mask, 1);
			}
			if ($this->compress_options['javascript_cachedir'] !=
				$this->compress_options['css_cachedir']) {
				foreach ($this->cache_types['scripts'] as $mask) {
					if (is_array($files['SCRIPTS'][$mask])) {
						$files['SCRIPTS'][$mask] = array_merge($files['SCRIPTS'][$mask], $this->dashboard_cache_size($mask, 1));
					} else {
						$files['SCRIPTS'][$mask] = $this->dashboard_cache_size($mask, 1);
					}
				}
			}
		}
		if (!empty($this->compress_options['html_cachedir'])) {
			@chdir($this->compress_options['html_cachedir']);
			foreach ($this->cache_types['html'] as $mask) {
				$files['HTML'][$mask] = $this->cache_engine->get_cache_size($mask, 1);
			}
			foreach ($this->cache_types['sprites'] as $mask) {
				if (is_array($files['SPRITES'][$mask])) {
					$files['SPRITES'][$mask] = array_merge($files['SPRITES'][$mask], $this->dashboard_cache_size($mask, 1));
				} else {
					$files['SPRITES'][$mask] = $this->dashboard_cache_size($mask, 1);
				}
			}
			if ($this->compress_options['html_cachedir'] !=
				$this->compress_options['css_cachedir'] &&
				$this->compress_options['html_cachedir'] !=
				$this->compress_options['javascript_cachedir']) {
				foreach ($this->cache_types['scripts'] as $mask) {
					if (is_array($files['SCRIPTS'][$mask])) {
						$files['SCRIPTS'][$mask] = array_merge($files['SCRIPTS'][$mask], $this->dashboard_cache_size($mask, 1));
					} else {
						$files['SCRIPTS'][$mask] = $this->dashboard_cache_size($mask, 1);
					}
				}
			}
		}
		$total = $size = 0;
		foreach ($files as $group) {
			foreach ($group as $file) {
				if (count($file)) {
					$size += $file[0];
					$total += $file[1];
				}
			}
		}
/* get basic errors / warnings */
		$page_variables = $this->dashboard_system(1);
/* get stable versions */
		$this->view->download('http://web-optimizator.googlecode.com/svn/versions/versions', $this->basepath . 'versions');
/* sey all other variables */
		$page_variables['version'] = $this->version;
		$page_variables['version_new'] = $this->version_new;
		$page_variables['language'] = $this->language;
		$page_variables['premium'] = $this->premium;
		$page_variables['password'] = $this->compress_options['password'];
		$page_variables['active'] = $this->compress_options['active'];
		$page_variables['website'] = $_SERVER['HTTP_HOST'];
		$page_variables['cache_folder'] = str_replace($this->compress_options['document_root'],
			"/", $this->compress_options['javascript_cachedir']);
		$page_variables['host'] = $this->compress_options['host'];
		$page_variables['charset'] = $this->compress_options['charset'];
		$page_variables['website_root'] = $this->compress_options['website_root'];
		$page_variables['document_root'] = $this->compress_options['document_root'];
		$page_variables['css_cachedir'] = $this->compress_options['css_cachedir'];
		$page_variables['javascript_cachedir'] = $this->compress_options['javascript_cachedir'];
		$page_variables['html_cachedir'] = $this->compress_options['html_cachedir'];
		$page_variables['current_directory'] = $this->basepath;
		$page_variables['htpasswd'] = $this->compress_options['htaccess']['access'];
		$page_variables['username'] = $this->compress_options['username'];
		$page_variables['external_scripts_user'] = $this->compress_options['external_scripts']['user'];
		$page_variables['external_scripts_pass'] = $this->compress_options['external_scripts']['pass'];
		$page_variables['restricted'] = $this->compress_options['restricted'];
		$page_variables['showbeta'] = $this->compress_options['showbeta'] || strpos($this->version, 'b');
		$page_variables['files_to_change'] = $this->system_files($this->cms_version);
		$page_variables['cms_version'] = $this->cms_version;
		$page_variables['success'] = $success;
		$page_variables['error'] = $this->error;
		$page_variables['version'] = $this->version;
		$page_variables['version_new'] = $this->version_new;
		$page_variables['version_beta'] = $this->version_beta;
		$page_variables['versions'] = explode("\n", @file_get_contents($this->basepath . 'versions'));
		$page_variables['skip_render'] = $this->skip_render;
		$page_variables['internal'] = $this->internal;
		$page_variables['total'] = $total;
		$page_variables['size'] = $size;
		$page_variables['files'] = $files;
/* Output data */
		$this->view->render("install_system", $page_variables);
	}

	/**
	* Write installation progress to JavaScript file
	* 
	**/	
	function write_progress ($progress) {
		$file = (empty($this->compress_options['javascript_cachedir']) ?
			($this->view->paths['full']['current_directory'] . 'cache/') :
				$this->compress_options['javascript_cachedir']) .
					'progress.html';
		return $this->write_file($file, $progress, 1);
	}

	/**
	* Refresh results about acceleration
	* 
	**/		
	function check_acceleration () {
		$before = @filesize($this->basepath . $this->index_before);
		$a = @file_get_contents($this->basepath . $this->index_after);
		$after = strlen($a);
		if ($this->premium > 1) {
/* re-check if there was 503 error */
			if (!empty($this->compress_options['active']) && 
				strpos($a, '503 Service')) {
					$this->view->download($this->webo_grade . '&refresh=on',
						$this->basepath . $this->index_after, 1);
			} elseif (!empty($this->compress_options['active']) &&
				$before && (empty($after) || $after < 250)) {
/* Request to re-check should be done on options save */
					$this->view->download($this->webo_grade,
						$this->basepath . $this->index_after, 1);
			} elseif (empty($before) || $before < 250) {
				$this->view->download($this->webo_grade . '&first=1&email=' .
					$this->compress_options['email'],
						$this->basepath . $this->index_before, 1);
			}
		}
	}

	/**
	* The login page -- to define password
	* 
	**/	
	function install_enter_password () {
		$this->check_acceleration();
		$page_variables = array(
			"title" => _WEBO_LOGIN_TITLE,
			"page" => 'install_enter_password',
			"version" => $this->version,
			"premium" => true,
			"language" => $this->language,
			"skip_render" => $this->skip_render
		);
		$this->view->render("admin_container", $page_variables);
	}

	/**
	* Page with version comparison
	* 
	**/		
	function install_promo() {
		$page_variables = array(
			"title" => _WEBO_SPLASH2_COMPARISON,
			"version" => $this->version,
			"promo" => true,
			"premium" => true,
			"password" => $this->compress_options['password'],
			"language" => $this->language,
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_promo", $page_variables);
	}

	/**
	* Home page (dashboard), control panel
	* 
	**/		
	function install_dashboard() {
		@unlink($this->compress_options['javascript_cachedir'] . 'progress.html');
		$page_variables = array(
			"title" => _WEBO_SPLASH2_CONTROLPANEL,
			"page" => 'install_dashboard',
			"version" => $this->version,
			"version_new" => $this->version_new,
			"language" => $this->language,
			"premium" => $this->premium,
			"password" => $this->compress_options['password'],
			"active" => $this->compress_options['active'],
			"website" => $_SERVER['HTTP_HOST'] .
				str_replace($this->compress_options['document_root'],
				"", $this->compress_options['website_root']),
			"cache_folder" => str_replace($this->compress_options['document_root'],
				"/", $this->compress_options['javascript_cachedir']),
			"cookie" => empty($_COOKIE['wss_blocks']) ? '' : $_COOKIE['wss_blocks'],
			"welcome" => empty($_COOKIE['wss_welcome']) ? '' : $_COOKIE['wss_welcome'],
			"skip_render" => $this->skip_render,
			"license" => $this->compress_options['license'],
			"fee" => $this->compress_options['fee']
		);
		$this->view->render("admin_container", $page_variables);
	}

	/**
	* The very first page -- to define e-mail and password
	* 
	**/	
	function install_set_password() {
		$username = empty($this->input['wss_username']) ? '' : $this->input['wss_username'];
		$password = empty($this->input['wss_password']) ? '' : $this->input['wss_password'];
		$confirm = empty($this->input['wss_confirm']) ? '' : $this->input['wss_confirm'];
		$license = empty($this->input['wss_license']) ? '' : $this->input['wss_license'];
		$email = empty($this->input['wss_email']) ? '' : $this->input['wss_email'];
		$confirmagreement = empty($this->input['wss_confirmagreement']) ? '' : $this->input['wss_confirmagreement'];
		$submit = empty($this->input['wss_Submit']) ? '' : $this->input['wss_Submit'];
/* try to get preliminary optimization grade for the website */
		$this->check_acceleration();
		if (!empty($this->compress_options['password'])) {
			$this->install_enter_password();
		} else {
			$this->error = array();
			if (!empty($submit)) {
				if (empty($password)) {
					$this->error[1] = 1;
				}
				if (empty($password) || empty($confirm) || $password != $confirm) {
					$this->error[2] = 1;
				}
				if (empty($email) ||
					!preg_match("/.+@.+\..+/", $email)) {
						$this->error[3] = 1;
				}
				if (empty($confirmagreement)) {
					$this->error[4] = 1;
				}
			}
			if (!count($this->error) && !empty($submit)) {
				$this->install_install(1);
			}
			if (count($this->error) || empty($submit)) {
				$page_variables = array(
					"title" => _WEBO_NEW_ENTER,
					"page" => 'install_set_password',
					"version" => $this->version,
					"error" => $this->error,
					"username" => $username,
					"password" => $password,
					"confirm" => $confirm,
					"license" => $license,
					"email" => $email,
					"confirmagreement" => $confirmagreement,
					"submit" => $submit,
					"premium" => true,
					"javascript_relative_cachedir" => str_replace($this->compress_options['document_root'],
						"/", $this->compress_options['javascript_cachedir']),
					"language" => $this->language,
					"skip_render" => $this->skip_render
				);
/* Show the install page */
				$this->view->render("admin_container", $page_variables);
			} else {
					$this->save_option("['htpasswd']",
						":" . $this->encrypt_password($password));
					$this->compress_options['password'] = md5($password);
					$this->save_option("['password']", $this->compress_options['password']);
					$this->save_option("['email']", htmlspecialchars($email));
					$this->save_option("['username']", htmlspecialchars($username));
					$this->save_option("['name']", htmlspecialchars($username));
					$this->save_option("['license']", htmlspecialchars($license));
					$this->premium = $this->view->validate_license($license);
					$this->install_favicon();
					$this->install_dashboard();
			}
		}
	}

	/**
	* Detect and put favicon.ico to the website root
	* 
	**/		
	function install_favicon () {
		$file = $this->compress_options['document_root'] . 'favicon.ico';
		if (@!is_file($file)) {
/* download website index */
			$this->view->download('http://' . $_SERVER['HTTP_HOST'] .
				str_replace($this->compress_options['document_root'], '/',
				$this->compress_options['website_root']),
				$this->basepath . $this->index_check, 2);
/* calculate favicon */
			$favicon = preg_replace("@.*(<link.*rel=['\"\s](shortcut\s)?icon[^>]*>).*@is", "$1",
				@file_get_contents($this->basepath . $this->index_check));
			if (!empty($favicon) && strlen($favicon) < 1000) {
				$favicon = preg_replace("@.*href\s*=[\s'\"](.*?)[\s'\"].*@is", "$1",
					$favicon);
/* clear external favicon from current website host */
				$favicon = preg_replace("@https?://(www.)" .
					preg_replace("@www.@i", "", $_SERVER['HTTP_HOST']) . "@", "",
					$favicon);
			}
			$favicon = empty($favicon) ? $this->svn . 'favicon.ico' : $favicon;
/* absolute paths */
			if ($favicon{0} == '/') {
/* external resource */
				if ($favicon{1} == '/') {
					$this->view->download('http:' . $favicon, $file, 2);
				} else {
					@copy($this->compress_options['document_root'] . $favicon, $file);
				}
/* relative paths */
			} else {
/* external file */
				if ((substr($favicon, 0, 5) == 'http:' ||
					substr($favicon, 0, 6) == 'https:')) {
						$this->view->download($favicon, $file, 2);
				} else {
					@copy($this->compress_options['website_root'] . $favicon, $file);
				}
			}
		}
	}

	/**
	* Clean up cache
	* 
	**/	
	function install_clean_cache ($redirect = true, $ajax = false) {
/* if all directories haven't been set yet -- just success */
		$success = false || (empty($this->compress_options['css_cachedir']) &&
			empty($this->compress_options['javascript_cachedir']) &&
			empty($this->compress_options['html_cachedir']));
		$deleted_css = true;
		$deleted_js = true;
		$deleted_html = true;
		$deleted_sql = true;
		$restricted = array('.', '..', 'yass.loader.js', 'progress.html', '.svn', 'wo.cookie.php', 'web.optimizer.stamp.png', 'wo.static.php', 'wo', '0.gif', 'webo-site-speedup.php', 'webo-site-speedup88.png', 'webo-site-speedup125.png', 'webo-site-speedup161.png', 'webo-site-speedup250.png', 'webo-site-speedup.css', 'webo-site-speedup.rocket.png', 'webo-site-speedup.back.jpg', 'webonautes.png', 'webonaut1-88.png', 'webonaut1-125.png', 'webonaut1-161.png', 'webonaut1-250.png', 'webonaut2-88.png', 'webonaut2-125.png', 'webonaut2-161.png', 'webonaut2-250.png', 'webonaut3-88.png', 'webonaut3-125.png', 'webonaut3-161.png', 'webonaut3-250.png', 'webonaut4-88.png', 'webonaut4-125.png', 'webonaut4-161.png', 'webonaut4-250.png', 'webonaut5-88.png', 'webonaut5-125.png', 'webonaut5-161.png', 'webonaut5-250.png');
/* css cache */
		if ($dir = @opendir($this->compress_options['css_cachedir'])) {
			while (($file = @readdir($dir)) !== false) {
				if (!in_array($file, $restricted) &&
					@is_file($this->compress_options['css_cachedir'] . $file)) {
					if (!@unlink($this->compress_options['css_cachedir'] . $file)) {
						$deleted_css = false;
					}
				}
			}
			$success = true;
		}
/* javascript cache */
		if ($dir = @opendir($this->compress_options['javascript_cachedir'])) {
			while (($file = @readdir($dir)) !== false) {
				if (!in_array($file, $restricted) &&
					@is_file($this->compress_options['javascript_cachedir'] . $file)) {
					if (!@unlink($this->compress_options['javascript_cachedir'] . $file)) {
						$deleted_js = false;
					}
				}
			}
			$success = true;
		}
/* html cache */
/* if cache stored on filesystem we need to preserve several files */
		if (($dir = @opendir($this->compress_options['html_cachedir'])) && (@$this->compress_options['performance']['cache_engine'] == 0)) {
			while (($file = @readdir($dir)) !== false) {
				if (!in_array($file, $restricted))
				{
					if(@is_file($this->compress_options['html_cachedir'] . $file))
					{
						if (!@unlink($this->compress_options['html_cachedir'] . $file)) {
							$deleted_html = false;
						}
					}
				}
			}
			$success = true;
		}
		$this->cache_engine->delete_entries('*');
		if (!$this->cache_engine->clear_sql_cache())
		{
			$deleted_sql = false;
		}
		if ($auth = $this->compress_options['parallel']['ftp']) {
/* Rack Space Cloud */
			if ($last = strpos($auth, '@RSC')) {
				$first = strpos($auth, ':');
				$user = substr($auth, 0, $first);
				$key = substr($auth, $first + 1, $last - $first - 1);
/* perform authorization */
				$headers = $this->view->upload('https://auth.api.rackspacecloud.com/v1.0',
					'', $this->options['html_cachedir'],
					array('X-Auth-User: ' . $user, 'X-Auth-Key: ' . $key));
				if (strpos($headers, 'Error: ') === false && strpos($headers, 'HTTP/1.1 401') === false) {
					$token = preg_replace("@.*X-Auth-Token: (.*?)\r?\n.*@is", "$1", $headers);
/* remove wo container */
					$headers = $this->view->upload(preg_replace("@.*X-CDN-Management-Url: (.*?)\r?\n.*@is", "$1", $headers) . '/wo',
						'', $this->options['html_cachedir'],
						array('X-Auth-Token: ' . $token, 'X-CDN-Enabled: False'), 'HEAD');
/* create container once more */
					$this->view->upload(preg_replace("@.*X-Storage-Url: (.*?)\r?\n.*@is", "$1", $headers) . '/wo',
						'', $this->options['html_cachedir'],
						array('X-Auth-Token: ' . $token, 'X-Referrer-ACL: 259200'), 'PUT');
				}
			}
		}
	}

	/**
	* Recursive function for files' fetching
	*
	**/	
	function get_directory_files ($directory, $mask, $recursive = true, $backup = 'gz', $return = array(), $limit = 0) {
		if (@is_dir($directory) && ($dh = @opendir($directory))) {
			while (($file = @readdir($dh)) !== false) {
				if ($file !== '.' && $file !== '..') {
					$absolute_file =
						$this->view->ensure_trailing_slash($directory) . $file;
/* deeper recursion */
					if (@is_dir($absolute_file) && $recursive) {
/* prevent PHP timeout on folders parsing */
						if (!$limit || time() > $this->time + $limit) {
							$return = $this->get_directory_files($absolute_file,
								$mask, $recursive, $backup, $return, $limit);
						}
/* check for mask */
					} elseif (preg_match("@" . $mask . "@", $absolute_file)) {
/* get info about synced file from FTP */
						if ($backup == 'FTP' &&
							!empty($this->compress_options['parallel']['ftp']) &&
							@function_exists('curl_init')) {
								$dir = preg_replace("@[^/]+$@", "", $absolute_file);
/* check if we already have mtime for files inside this directory */
								if (!isset($this->synced_files[$dir . '.'])) {
									$file = $this->compress_options['html_cachedir'] . 'ftp.tmp';
									$ch = @curl_init('ftp://' .
										preg_replace("!^([^@]+)@([^:]+):([^@]+)@!", "$1:$3@", $this->compress_options['parallel']['ftp']) .
										str_replace($this->compress_options['document_root'], "/", $dir));
									$fp = @fopen($file, 'w');
									@curl_setopt($ch, CURLOPT_USERPWD, preg_replace("!(.*)@.*!", "$1", $this->compress_options['parallel']['ftp']));
									@curl_setopt($ch, CURLOPT_FILE, $fp);
									@curl_exec($ch);
									@curl_close($ch);
									@fclose($fp);
									$fp = @fopen($file, 'r');
									while ($f = @fgets($fp)) {
										$f = preg_replace("@\s+@", " ", $f);
										$metas = explode(" ", $f);
										$this->synced_files[$dir . $metas[8]] = strtotime($metas[5] . " " . $metas[6] . " " . $metas[7]);
									}
									@unlink($file);
								}
								$synced = empty($this->synced_files[$absolute_file]) ? 0 : $this->synced_files[$absolute_file];
/* or directly from file system */
						} else {
							$synced = @filemtime($absolute_file . '.' . $backup);
						}
						$return[] = array(
							$absolute_file,
							@filemtime($absolute_file),
							$synced,
							@filesize($absolute_file)
						);
					}
				}
			}
		}
		return $return;
	}


	/**
	* CDN sync page
	*
	**/	 
	function install_cdn() {
		$directory = empty($this->input['wss_directory']) ?
			(empty($this->compress_options['website_root']) ?
				$this->view->paths['absolute']['document_root'] :
					$this->compress_options['website_root']) :
			$this->input['wss_directory'];
		$recursive = empty($this->input['wss_recursive']) ? 0 : 1;
		$submit = empty($this->input['wss_Submit']) ? 0 : 1;
		$results = array();
		if ($submit) {
/* prevent PHP timeout on folders parsing */
			$limit = @ini_get("max_execution_time");
			set_time_limit($limit * 10);
			$this->time = time();
			$results = $this->get_directory_files($directory,
				'\\.(jpe?g|png|gif|tiff|bmp|flv|wmv|asf|asx|wma|wax|wmx|wm|swf|pdf|doc|rtf|xls|ppt|txt|xml|css|js|ico|ttf|otf|eot|svg)$',
				$recursive, 'FTP', array(),
				@ini_get("max_execution_time") == $limit ? $limit - 5 : 0);
		}
		$this->page_variables = array(
			"cdn_disabled" => empty($this->compress_options['parallel']['ftp']),
			"results" => $results,
			"directory" => $directory,
			"premium" => $this->premium,
			"recursive" => $recursive,
			"submit" => $submit,
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_cdn", $this->page_variables);
	}

	/**
	* Image optimization page
	*
	**/	 
	function install_image() {
		$directory = empty($this->input['wss_directory']) ?
			(empty($this->compress_options['website_root']) ?
				$this->view->paths['absolute']['document_root'] :
					$this->compress_options['website_root']) :
			$this->input['wss_directory'];
		$recursive = empty($this->input['wss_recursive']) ? 0 : 1;
		$submit = empty($this->input['wss_Submit']) ? 0 : 1;
		$results = array();
		if ($submit) {
/* prevent PHP timeout on folders parsing */
			$limit = @ini_get("max_execution_time");
			set_time_limit($limit * 10);
			$this->time = time();
			$results = $this->get_directory_files($directory,
				'\\.(png|gif|jpe?g|bmp)$',
				$recursive, 'backup', array(),
				@ini_get("max_execution_time") == $limit ? $limit - 5 : 0);
		}
		$this->page_variables = array(
			"results" => $results,
			"directory" => $directory,
			"premium" => $this->premium,
			"recursive" => $recursive,
			"submit" => $submit,
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_image", $this->page_variables);
	}

	/**
	* Static gzip page
	*
	**/	 
	function install_gzip() {
		$directory = empty($this->input['wss_directory']) ?
			(empty($this->compress_options['website_root']) ?
				$this->view->paths['absolute']['document_root'] :
					$this->compress_options['website_root']) :
			$this->input['wss_directory'];
		$recursive = empty($this->input['wss_recursive']) ? 0 : 1;
		$submit = empty($this->input['wss_Submit']) ? 0 : 1;
		$results = array();
		if ($submit) {
/* prevent PHP timeout on folders parsing */
			$limit = @ini_get("max_execution_time");
			set_time_limit($limit * 10);
			$this->time = time();
			$results = $this->get_directory_files($directory,
				'\\.(txt|xml|css|js|ico|ttf|otf|eot|svg)$',
				$recursive, 'gz', array(),
				@ini_get("max_execution_time") == $limit ? $limit - 5 : 0);
		}
		$this->page_variables = array(
			"results" => $results,
			"directory" => $directory,
			"premium" => $this->premium,
			"recursive" => $recursive,
			"submit" => $submit,
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_gzip", $this->page_variables);
	}

	/**
	* Update from System Status (beta)
	*
	**/
	function install_beta () {
		$this->install_update_generic(0);
		$this->install_system();
	}

	/**
	* Update from System Status (stable)
	*
	**/
	function install_stable () {
		$this->install_update_generic();
		$this->install_system();
	}

	/**
	* Update from dashboard
	*
	**/
	function install_update () {
		$this->install_update_generic();
		$this->install_dashboard();
	}

	/**
	* Generic update function
	*
	**/
	function install_update_generic($stable = 1) {
		@chdir($this->basepath);
		$file = 'files';
		$svn = $stable ? $this->version_stable ? $this->svn_generic . 'versions/' . $this->version_stable . '/' : $this->svn : $this->svn_beta;
		$this->view->download($svn . $file, $file);
		$i = 1;
		if (@is_file($file)) {
			$files = preg_split("/\r?\n/", @file_get_contents($file));
			$total = count($files);
			foreach ($files as $file) {
				$this->write_progress(round(100 * $i / $total) . "," . $i . "," . $total, 1);
				$tmp = $file . '.tmp';
				$this->view->download($svn . $file, $tmp);
				if (@is_file($tmp)) {
					@copy($tmp, $file);
					@unlink($tmp);
/* remove old gzipped version */
					@unlink($file . '.gz');
					if ($file == $this->options_file) {
/* save all options to the new file -- rewrite default ones  */
						foreach($this->compress_options as $key => $option) {
							if(is_array($option)) {
								foreach($option as $option_name => $option_value) {
									$this->save_option("['" .
										strtolower($key) . "']['" .
										strtolower($option_name) . "']",
										$option_value);
								}
							} else {
								$this->save_option("['" . strtolower($key) .
									"']", $option);
							}
						}
					}
				}
				$i++;
			}
		}
/* make a fix to create new user config file if older config exists */
		if (!@is_file($this->basepath . 'config.user.php')) {
			@copy($this->basepath . 'config.safe.php', $this->basepath . 'config.user.php');
			if (@is_file($this->basepath . 'config.user.php')) {
				$this->save_option("['config']", "user");
				$this->options_file = 'config.user.php';
				$this->save_option("['title']", constant('_WEBO_OPTIONS_TITLES_user'));
				$this->save_option("['description']", constant('_WEBO_OPTIONS_DESCRIPTIONS_user'));
				foreach($this->compress_options as $key => $option) {
					if (is_array($option)) {
						foreach($option as $option_name => $option_value) {
							$this->save_option("['" . strtolower($key) . "']['" . strtolower($option_name) . "']", $option_value);
						}
					} else {
						$this->save_option("['" . strtolower($key) . "']", $option);
					}
				}
				$this->save_option("['config']", "user");
			}
		}
		@unlink($this->compress_options['javascript_cachedir'] . 'progress.html');
	}

	/**
	* Uninstall page
	* 
	**/	
	function install_uninstall ($skip = false) {
		$return = !$skip;
		$this->cms_version = $this->system_info($this->view->paths['absolute']['document_root']);
/* PHP-Nuke, Bitrix, Open Slaed deletion */
		if (in_array($this->cms_version, array('PHP-Nuke', 'Bitrix', '4images', 'VaM Shop', 'osCommerce')) ||
			substr($this->cms_version, 0, 10) == 'Open Slaed' ||
			substr($this->cms_version, 0, 13) == 'Social Engine') {
			if ($this->cms_version == 'Bitrix') {
				$mainfile = $this->view->paths['absolute']['document_root'] . 'bitrix/header.php';
				$footer = $this->view->paths['absolute']['document_root'] . 'bitrix/modules/main/include/epilog_after.php';
			} elseif ($this->cms_version == 'PHP-Nuke') {
				$mainfile = $this->view->paths['absolute']['document_root'] . 'mainfile.php';
				$footer = $this->view->paths['absolute']['document_root'] . 'footer.php';
			} elseif ($this->cms_version == '4images') {
				$mainfile = $this->view->paths['absolute']['document_root'] . 'includes/page_header.php';
				$footer = $this->view->paths['absolute']['document_root'] . 'includes/page_footer.php';
			} elseif ($this->cms_version == 'VaM Shop' || $this->cms_version == 'osCommerce') {
				$mainfile = $this->view->paths['absolute']['document_root'] . 'includes/application_top.php';
				$footer = $this->view->paths['absolute']['document_root'] . 'includes/application_bottom.php';
			} elseif (substr($this->cms_version, 0, 13) == 'Social Engine') {
				$mainfile = $this->view->paths['absolute']['document_root'] . 'header.php';
				$footer = $this->view->paths['absolute']['document_root'] . 'footer.php';
			} else {
				$mainfile = $this->view->paths['absolute']['document_root'] . 'index.php';
				$footer = $this->view->paths['absolute']['document_root'] . 'function/function.php';
			}
			$this->cleanup_file($mainfile, $return);
			$this->cleanup_file($footer, $return);
		} else {
/* remove instances of Web Optimizer from index.php */
			$index = $this->view->paths['absolute']['document_root'] . 'index.php';
/* fix for phpBB and vBulletin */
			if ($this->cms_version == 'phpBB' || substr($this->cms_version, 0, 9) == 'vBulletin') {
				$index = $this->view->paths['absolute']['document_root'] . 'includes/functions.php';
			}
/* fix for IPB */
			if ($this->cms_version == 'Invision Power Board') {
				$index = $this->view->paths['absolute']['document_root'] . 'sources/classes/class_display.php';
			}
/* fix for NetCat */
			if ($this->cms_version == 'NetCat') {
				$index = $this->view->paths['absolute']['document_root'] . 'netcat/require/e404.php';
			}
/* fix for PHP Fusion */
			if ($this->cms_version == 'PHP Fusion') {
				$index = $this->view->paths['absolute']['document_root'] . 'themes/templates/footer.php';
			}
/* fix for PHP Fusion */
			if ($this->cms_version == 'X-Cart') {
				$index = $this->view->paths['absolute']['document_root'] . 'include/func/func.core.php';
			}
			$this->cleanup_file($index, $return);
/* additional change of cache plugins */
			if (substr($this->cms_version, 0, 7) == "Joomla!" || substr($this->cms_version, 0, 5) == "XOOPS") {
/* Joomla! 1.5 System-Cache plugin */
				if (preg_match("/Joomla! 1\.[56789]/", $this->cms_version)) {
					$cache_file = $this->view->paths['absolute']['document_root'] . 'plugins/system/cache.php';
/* Joomla! 1.0 PageCache component */
				} elseif (substr($this->cms_version, 0, 7) == "Joomla!") {
					$cache_file = $this->view->paths['absolute']['document_root'] . 'components/com_pagecache/pagecache.class.php';
/* XOOPS internal caching */
				} else {
					$cache_file = $this->view->paths['absolute']['document_root'] . 'class/theme.php';
				}
				$this->cleanup_file($cache_file, $return);
			}
/* Joomla! 1.0 System-Cache mambot, Joomla! 1.5 JRE change */
			if (substr($this->cms_version, 0, 7) == "Joomla!") {
/* System-Cache*/
				$cache_file = $this->view->paths['absolute']['document_root'] . 'mambots/system/cache.php';
				$this->cleanup_file($cache_file, $return);
/* JRE */
				$cache_file = $this->view->paths['absolute']['document_root'] . 'administrator/components/com_jrecache/includes/cache_handler.php';
				$this->cleanup_file($cache_file, $return);
			}
		}
/* execute plugin-specific logic */
		$plugins = explode(" ", $this->compress_options['plugins']);
		if (is_array($plugins)) {
			foreach ($plugins as $plugin) {
				$plugin_file = $this->basepath . 'plugins/' . $plugin . '.php';
				if (@is_file($plugin_file)) {
					include($plugin_file);
					$web_optimizer_plugin->onUninstall($this->view->paths['absolute']['document_root']);
				}
			}
		}
/* clean up all WEBO Site SpeedUp rules from .htaccess */
		$this->htaccess = $this->detect_htaccess();
		if (empty($this->error)) {
			if (!@is_file($this->htaccess . '.backup')) {
				$content_saved = $this->clean_htaccess();
				$this->write_file($this->htaccess, $content_saved, $return);
			} else {
				@copy($this->htaccess . '.backup', $this->htaccess);
			}
		}
		$submit = empty($this->input['wss_Submit']) ? 0 : 1;
		$message = empty($this->input['wss_message']) ? '' : $this->input['wss_message'];
		$email = empty($this->input['wss_email']) ? '' : $this->input['wss_email'];
/* remove all optimization results */
		@unlink($this->basepath . $this->index_before);
		@unlink($this->basepath . $this->index_after);
		$error = array();
		if (!$skip) {
			if ($submit) {
				if (empty($email) ||
					!preg_match("/.+@.+\..+/", $email)) {
					$error[1] = 1;
				}
				if (empty($message)) {
					$error[2] = 1;
				}
/* send a email to info@webo.name */
				if (!count($error)) {
					$this->send_message($email, $message, 1);
				}
			}
			$this->page_variables = array(
				"title" => _WEBO_SPLASH1_UNINSTALL,
				"page" => 'install_uninstall',
				"document_root" => $this->view->paths['full']['document_root'],
				"website_root" => $this->view->paths['absolute']['document_root'],
				"message" => $message,
				"email" => $email,
				"submit" => $submit,
				"error" => $error,
				"basepath" => $this->basepath,
				"version" => $this->version,
				"premium" => $this->premium,
				"language" => $this->language,
				"skip_render" => $this->skip_render
			);
			$this->view->render("install_uninstall", $this->page_variables);
		}
	}

	/**
	* Writes content to file
	**/
	function write_file ($file, $content, $return = false) {
		if (function_exists('file_put_contents')) {
			@chmod($file, octdec("0666"));
			$return = @file_put_contents($file, $content);
			@chmod($file, octdec("0644"));
		} else {
			@chmod($file, octdec("0666"));
			$fp = @fopen($file, "w");
			if ($fp) {
				@fwrite($fp, $content);
				@fclose($fp);
				$return = 1;
			} elseif ($return) {
				$return = 0;
			}
			@chmod($file, octdec("0644"));
		}
		if (!empty($return)) {
			return $return;
		}
	}

	/**
	* Delets Web Optimizer calls from a single file
	**/
	function cleanup_file ($file, $return = false) {
		if (@is_file($file)) {
/* clean content from Web Optimizer calls */
			$content = preg_replace("/(global \\\$web_optimizer|\\\$web_optimizer,|\\\$web_optimizer->finish\(\)|require\('[^\']+\/web.optimizer.php'\));?\r?\n?/", "", @file_get_contents($file));
			$this->write_file($file, $content, $return);
		}
	}

	/**
	* Simple function to check multiple hosts possibility
	* Returns lists of allowed hosts from given array
	**/
	function check_hosts ($hosts) {
		$main_host = preg_replace("/^www\./", "", $_SERVER['HTTP_HOST']);
		$allowed_hosts = "";
/* exclude local host case */
		if (strpos($main_host, ".")) {
			$etalon = @filesize("libs/css/progress.png");
			$etalon2 = @filesize("libs/css/stamps.png");
			if (is_array($hosts)) {
				foreach ($hosts as $host) {
					if (!strpos($host, ".")) {
						$host = $host . "." . $main_host;
					}
					$webo_image = "http://" . $host . preg_replace("/[^\/]+$/", "", $_SERVER['SCRIPT_NAME']) . "libs/css/a.png";
					$tmp_image = "image.tmp.png";
/* try to get webo image from this host */
					$this->view->download($webo_image, $tmp_image);
					if (@filesize($tmp_image) == $etalon) {
/* prevent 404 page with the same size */
						$webo_image2 = "http://" . $host . preg_replace("/[^\/]+$/", "", $_SERVER['SCRIPT_NAME']) . "libs/css/c.png";
						$tmp_image2 = "image.tmp2.png";
						$this->view->download($webo_image2, $tmp_image2);
						if (@filesize($tmp_image2) == $etalon2) {
							$allowed_hosts .= $host . " ";
						}
						@unlink($tmp_image2);
					}
					@unlink($tmp_image);
				}
			}
		}
		return trim($allowed_hosts);
	}

	/**
	* Get current balance / paid options
	*
	**/
	function install_balance () {
		$this->page_variables = array(
			"options" => $this->get_options(),
			"premium" => $this->premium,
			"basepath" => $this->basepath,
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_balance", $this->page_variables);
	}

	/**
	* Delete given configuration
	**/	
	function options_delete () {
		$config = $this->input['wss_config'];
		$config_file = $this->basepath . 'config.' .
			preg_replace("/[^a-z0-9]/","", $config) . '.php';
		@unlink($config_file);
		$this->error = array();
		if (@is_file($config_file)) {
			$this->error[5] = 1;
/* switch to safe config is we deleting current one */
		} else {
			if ($config == $this->compress_options['config']) {
				$this->save_option("['config']", "safe");
			}
		}
		$this->page_variables = array(
			"page" => 'options_delete',
			"submit" => 1,
			"error" => $this->error,
			"config" => $config_file
		);
		$this->view->render("install_options", $this->page_variables);
	}

	/**
	* Return all configuration as JSON-array
	**/
	function options_configuration () {
/* get all available configurations */
		$options = array();
		@chdir($this->basepath);
		foreach (glob("config.*.php") as $file) {
			if ($file != 'config.webo.php') {
				$saved = $this->compress_options;
				$key = str_replace(array("config.", ".php"), '', $file);
				$ext = strpos($key, 'user') === false ? $key : 'user';
				include($this->basepath . $file);
				$this->compress_options = $compress_options;
				$this->compress_options['external_scripts']['user'] = 
					$saved['external_scripts']['user'];
				$this->compress_options['external_scripts']['pass'] = 
					$saved['external_scripts']['pass'];
				$options[$key] = $this->get_options($ext);
				$this->compress_options = $saved;
			}
		}
		$this->page_variables = array(
			"options" => $options,
			"skip_render" => $this->skip_render
		);
		$this->view->render("options_configuration", $this->page_variables);
	}

	/**
	* Get options to render them
	*
	**/
	function install_options () {
		$options = $this->get_options();
		$submit = empty($this->input['wss_Submit']) ? 0 : 1;
		$this->error = array();
		if ($submit) {
			$this->set_options();
			$this->write_htaccess();
		}
/* get list of users configs */
		$configs = array();
		@chdir($this->basepath);
		foreach (glob("config.*.php") as $file) {
			if (!in_array($file, array( 'config.webo.php', 'config.basic.php', 'config.safe.php', 'config.optimal.php', 'config.extreme.php'))) {
				$configs[] = str_replace(array("config.", ".php"), '', $file);
			}
		}
		$this->page_variables = array(
			"options" => $options,
			"premium" => $this->premium,
			"submit" => $submit,
			"error" => $this->error,
			"basepath" => $this->basepath,
			"configs" => $configs,
			"config" => $this->compress_options['config'],
			"skip_render" => $this->skip_render
		);
		$this->view->render("install_options", $this->page_variables);
	}

	/**
	* Set options according to internal logic
	*
	**/	
	function get_options ($config = 'safe') {
/* calculate current environment restrictions */
		$this->check_options();
		$options = array(
			'title' => empty($this->compress_options['title']) ?
				'' : $this->compress_options['title'],
			'description' => empty($this->compress_options['description']) ?
				'' : $this->compress_options['description'],
			'combinecss' => array(
				'combine_css' => array(
					'value' => $this->compress_options['minify']['css'] ?
						$this->compress_options['minify']['css_body'] ? 2 : 1 : 0,
					'type' => 'radio',
					'count' => 3
				),
				'external_scripts_css_inline' => array(
					'value' => $this->compress_options['external_scripts']['css_inline'],
					'type' => 'checkbox'
				),
				'external_scripts_css' => array(
					'value' => $this->compress_options['external_scripts']['css'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_external_scripts_css'])
				),
				'minify_css_file' => array(
					'value' => $this->compress_options['minify']['css_file'],
					'type' => 'text',
					'price' => 1
				),
				'minify_css_host' => array(
					'value' => $this->compress_options['minify']['css_host'],
					'type' => 'text',
					'price' => 2
				),
				'external_scripts_additional_list' => array(
					'value' => $this->compress_options['external_scripts']['additional_list'],
					'type' => 'textarea'
				),
				'external_scripts_include_code' => array(
					'value' => $this->compress_options['external_scripts']['include_code'],
					'type' => 'textarea',
					'price' => 1
				),
				'config' => array(
					'value' => $this->compress_options['config'],
					'type' => 'text',
					'hidden' => 1
				)
			),
			'combine_js' => array(
				'minify_javascript' => array(
					'value' => $this->compress_options['minify']['javascript'] ?
						$this->compress_options['minify']['javascript_body'] ? 2 : 1 : 0,
					'type' => 'radio',
					'count' => 3
				),
				'external_scripts_inline' => array(
					'value' => $this->compress_options['external_scripts']['inline'],
					'type' => 'checkbox'
				),
				'external_scripts_on' => array(
					'value' => $this->compress_options['external_scripts']['on'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_external_scripts_on'])
				),
				'minify_javascript_file' => array(
					'value' => $this->compress_options['minify']['javascript_file'],
					'type' => 'text',
					'price' => 1
				),
				'minify_javascript_host' => array(
					'value' => $this->compress_options['minify']['javascript_host'],
					'type' => 'text',
					'price' => 2
				),
				'external_scripts_ignore_list' => array(
					'value' => $this->compress_options['external_scripts']['ignore_list'],
					'type' => 'textarea'
				),
				'external_scripts_head_end' => array(
					'value' => $this->compress_options['external_scripts']['head_end'],
					'type' => 'checkbox'
				),
				'external_scripts_include_try' => array(
					'value' => $this->compress_options['external_scripts']['include_try'],
					'type' => 'checkbox'
				),
				'external_scripts_duplicates' => array(
					'value' => $this->compress_options['external_scripts']['duplicates'],
					'type' => 'checkbox'
				)
			),
			'minify' => array(
				'minify_css_min' => array(
					'value' => $this->compress_options['minify']['css_min'],
					'type' => 'radio',
					'count' => 3
				),
				'minify_js' => array(
					'value' => $this->compress_options['minify']['with_jsmin'] ? 1 :
						($this->compress_options['minify']['with_yui'] ? 2 :
						($this->compress_options['minify']['with_packer'] ? 3 : 0)),
					'type' => 'radio',
					'count' => 4,
					'disabled' => array(
						0,
						!empty($this->restrictions['wss_minify_js1']),
						!empty($this->restrictions['wss_minify_js2']),
						!empty($this->restrictions['wss_minify_js3']),
						!empty($this->restrictions['wss_minify_js4'])
					)
						
				),
				'external_scripts_minify_exclude' => array(
					'value' => $this->compress_options['external_scripts']['minify_exclude'],
					'type' => 'textarea',
					'price' => 1
				),
				'minify_page' => array(
					'value' => $this->compress_options['minify']['page'],
					'type' => 'checkbox'
				),
				'minify_html_one_string' => array(
					'value' => $this->compress_options['minify']['html_one_string'],
					'type' => 'checkbox',
					'price' => 2
				),
				'minify_html_comments' => array(
					'value' => $this->compress_options['minify']['html_comments'],
					'type' => 'checkbox',
					'price' => 2
				)
			),
			'gzip' => array(
				'gzip_css' => array(
					'value' => $this->compress_options['gzip']['css'],
					'type' => 'checkbox'
				),
				'gzip_javascript' => array(
					'value' => $this->compress_options['gzip']['javascript'],
					'type' => 'checkbox'
				),
				'gzip_fonts' => array(
					'value' => $this->compress_options['gzip']['fonts'],
					'type' => 'checkbox'
				),
				'gzip_page' => array(
					'value' => $this->compress_options['gzip']['page'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_gzip_page'])
				),
				'gzip_cookie' => array(
					'value' => $this->compress_options['gzip']['cookie'],
					'type' => 'checkbox',
					'price' => 3
				),
				'gzip_noie' => array(
					'value' => $this->compress_options['gzip']['noie'],
					'type' => 'checkbox',
					'price' => 1
				)
			),
			'clientside' => array(
				'far_future_expires_css' => array(
					'value' => $this->compress_options['far_future_expires']['css'],
					'type' => 'checkbox'
				),
				'far_future_expires_javascript' => array(
					'value' => $this->compress_options['far_future_expires']['javascript'],
					'type' => 'checkbox'
				),
				'far_future_expires_images' => array(
					'value' => $this->compress_options['far_future_expires']['images'],
					'type' => 'checkbox'
				),
				'far_future_expires_fonts' => array(
					'value' => $this->compress_options['far_future_expires']['fonts'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_expires']) &&
						!empty($this->restrictions['wss_htaccess_mod_rewrite'])
				),
				'far_future_expires_video' => array(
					'value' => $this->compress_options['far_future_expires']['video'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_expires']) &&
						!empty($this->restrictions['wss_htaccess_mod_rewrite'])
				),
				'far_future_expires_static' => array(
					'value' => $this->compress_options['far_future_expires']['static'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_expires']) &&
						!empty($this->restrictions['wss_htaccess_mod_rewrite'])
				),
				'far_future_expires_html' => array(
					'value' => $this->compress_options['far_future_expires']['html'],
					'type' => 'checkbox',
					'price' => 2
				),
				'far_future_expires_html_timeout' => array(
					'value' => $this->compress_options['far_future_expires']['html_timeout'],
					'type' => 'smalltext',
				),
				'far_future_expires_external' => array(
					'value' => $this->compress_options['far_future_expires']['external'],
					'type' => 'checkbox',
					'price' => 3
				)
			),
			'htaccess' => array(
				'htaccess_enabled' => array(
					'value' => $this->compress_options['htaccess']['enabled'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_enabled'])
				),
				'htaccess_local' => array(
					'value' => $this->compress_options['htaccess']['local'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_enabled'])
				),
				'htaccess_mod_deflate' => array(
					'value' => $this->compress_options['htaccess']['mod_deflate'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_deflate']) ||
						empty($this->restrictions['wss_htaccess_mod_gzip'])
				),
				'htaccess_mod_gzip' => array(
					'value' => $this->compress_options['htaccess']['mod_gzip'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_gzip']) ||
						empty($this->restrictions['wss_htaccess_mod_deflate'])
				),
				'htaccess_mod_expires' => array(
					'value' => $this->compress_options['htaccess']['mod_expires'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_expires'])
				),
				'htaccess_mod_headers' => array(
					'value' => $this->compress_options['htaccess']['mod_headers'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_headers'])
				),
				'htaccess_mod_setenvif' => array(
					'value' => $this->compress_options['htaccess']['mod_setenvif'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_setenvif'])
				),
				'htaccess_mod_rewrite' => array(
					'value' => $this->compress_options['htaccess']['mod_rewrite'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_rewrite'])
				),
				'htaccess_mod_mime' => array(
					'value' => $this->compress_options['htaccess']['mod_mime'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_mime'])
				)
			),
			'backlink' => array(
				'footer_text' => array(
					'hidden' => $this->premium < 1 ? 1 : 0,
					'value' => $this->compress_options['footer']['text'],
					'type' => 'checkbox'
				),
				'footer_image' => array(
					'value' => $this->compress_options['footer']['image'],
					'type' => 'textarea'
				),
				'footer_link' => array(
					'value' => $this->compress_options['footer']['link'],
					'type' => 'textarea'
				),
				'footer_css_code' => array(
					'value' => $this->compress_options['footer']['css_code'],
					'type' => 'textarea'
				),
				'footer_spot' => array(
					'hidden' => $this->premium < 1 ? 1 : 0,
					'value' => $this->compress_options['footer']['spot'],
					'type' => 'checkbox'
				),
				'footer_counter' => array(
					'value' => $this->compress_options['footer']['counter'],
					'type' => 'checkbox'
				)
			),
			'performance' => array(
				'premium' => 0,
				'performance_mtime' => array(
					'value' => $this->compress_options['performance']['mtime'],
					'type' => 'checkbox',
					'price' => 12
				),
				'performance_check_files' => array(
					'value' => $this->compress_options['performance']['cache_version'] ? 1 : 0,
					'type' => 'checkbox',
					'price' => 1
				),
				'performance_cache_version' => array(
					'value' => $this->compress_options['performance']['cache_version'],
					'type' => 'smalltext',
					'price' => 2
				),
				'performance_plain_string' => array(
					'value' => $this->compress_options['performance']['plain_string'],
					'type' => 'checkbox',
					'price' => 2
				),
				'performance_uniform_cache' => array(
					'value' => $this->compress_options['performance']['uniform_cache'],
					'type' => 'checkbox',
					'price' => 3
				),
				'performance_restore_properties' => array(
					'value' => $this->compress_options['performance']['restore_properties'],
					'type' => 'checkbox',
					'hidden' => $this->premium < 2 ? 1 : 0,
					'price' => 1
				),
				'performance_delete_old' => array(
					'value' => $this->compress_options['performance']['delete_old'],
					'type' => 'smalltext',
					'price' => 2
				),
				'performance_cache_engine' => array(
					'value' => $this->compress_options['performance']['cache_engine'],
					'type' => 'radio',
					'count' => 4,
					'price' => array(0, 5, 5, 5, 5, 5, 5),
					'disabled' => array(
						0,
						!empty($this->restrictions['wss_performance_cache_engine1']),
						!empty($this->restrictions['wss_performance_cache_engine2']),
						!empty($this->restrictions['wss_performance_cache_engine3']),
						!empty($this->restrictions['wss_performance_cache_engine4']),
						!empty($this->restrictions['wss_performance_cache_engine5']),
					)
				),
				'performance_cache_engine_options' => array(
					'value' => $this->compress_options['performance']['cache_engine_options'],
					'type' => 'text',
					'hidden' => 1
				),
			),
			'data_uri' => array(
				'premium' => 0,
				'data_uris_on' => array(
					'value' => $this->compress_options['data_uris']['on'],
					'type' => 'checkbox',
					'price' => 5
				),
				'data_uris_mhtml' => array(
					'value' => $this->compress_options['data_uris']['mhtml'],
					'type' => 'checkbox',
					'disabled' => !empty($this->compress_options['performance']['uniform_cache']),
					'price' => 5
				),
				'data_uris_size' => array(
					'value' => $this->compress_options['data_uris']['size'],
					'type' => 'smalltext',
				),
				'data_uris_mhtml_size' => array(
					'value' => $this->compress_options['data_uris']['mhtml_size'],
					'type' => 'smalltext',
					'disabled' => !empty($this->compress_options['performance']['uniform_cache'])
				),
				'data_uris_ignore_list' => array(
					'value' => $this->compress_options['data_uris']['ignore_list'],
					'type' => 'textarea'
				),
				'data_uris_additional_list' => array(
					'value' => $this->compress_options['data_uris']['additional_list'],
					'type' => 'textarea',
					'disabled' => !empty($this->compress_options['performance']['uniform_cache'])
				),
				'data_uris_separate' => array(
					'value' => $this->compress_options['data_uris']['separate'],
					'type' => 'checkbox',
					'price' => 2
				),
				'data_uris_domloaded' => array(
					'value' => $this->compress_options['data_uris']['domloaded'],
					'type' => 'checkbox',
					'price' => 2
				)
			),
			'css_sprites' => array(
				'premium' => 0,
				'css_sprites_enabled' => array(
					'value' => $this->compress_options['css_sprites']['enabled'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']),
					'price' => 5
				),
				'css_sprites_aggressive' => array(
					'value' => $this->compress_options['css_sprites']['aggressive'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']),
					'price' => 2
				),
				'css_sprites_no_ie6' => array(
					'value' => $this->compress_options['css_sprites']['no_ie6'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']),
					'price' => 2
				),
				'css_sprites_dimensions_limited' => array(
					'value' => $this->compress_options['css_sprites']['dimensions_limited'],
					'type' => 'smalltext',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled'])
				),
				'css_sprites_html_sprites' => array(
					'value' => $this->compress_options['css_sprites']['html_sprites'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']),
					'price' => 4
				),
				'css_sprites_html_limit' => array(
					'value' => $this->compress_options['css_sprites']['html_limit'],
					'type' => 'smalltext',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled'])
				),
				'css_sprites_html_page' => array(
					'value' => $this->compress_options['css_sprites']['html_page'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']),
					'price' => 2
				),
				'css_sprites_ignore' => array(
					'value' => $this->compress_options['css_sprites']['ignore'],
					'type' => 'radio',
					'count' => 2,
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled'])
				),
				'css_sprites_ignore_list' => array(
					'value' => $this->compress_options['css_sprites']['ignore_list'],
					'type' => 'textarea',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled'])
				),
				'css_sprites_extra_space' => array(
					'value' => $this->compress_options['css_sprites']['extra_space'],
					'type' => 'checkbox',
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']),
					'price' => 1
				),
				'css_sprites_truecolor_in_jpeg' => array(
					'value' => $this->compress_options['css_sprites']['truecolor_in_jpeg'],
					'type' => 'radio',
					'count' => 2,
					'disabled' => !empty($this->restrictions['wss_css_sprites_enabled']) ? 100 : 0,
					'price' => array(0, 1)
				),
			),
			'serverside' => array(
				'premium' => 0,
				'html_cache_enabled' => array(
					'value' => $this->compress_options['html_cache']['enabled'],
					'type' => 'checkbox',
					'price' => 25
				),
				'html_cache_timeout' => array(
					'value' => $this->compress_options['html_cache']['timeout'],
					'type' => 'smalltext'
				),
				'html_cache_flush_only' => array(
					'value' => $this->compress_options['html_cache']['flush_only'],
					'type' => 'checkbox',
					'price' => 3
				),
				'html_cache_flush_size' => array(
					'value' => $this->compress_options['html_cache']['flush_size'],
					'type' => 'smalltext'
				),
				'html_cache_ignore_list' => array(
					'value' => $this->compress_options['html_cache']['ignore_list'],
					'type' => 'textarea',
					'price' => 2
				),
				'html_cache_allowed_list' => array(
					'value' => $this->compress_options['html_cache']['allowed_list'],
					'type' => 'textarea'
				),
				'html_cache_additional_list' => array(
					'value' => $this->compress_options['html_cache']['additional_list'],
					'type' => 'textarea',
					'price' => 3
				),
				'html_cache_params' => array(
					'value' => $this->compress_options['html_cache']['params'],
					'type' => 'textarea',
					'price' => 2
				),
				'html_cache_enhanced' => array(
					'value' => $this->compress_options['html_cache']['enhanced'],
					'type' => 'checkbox',
					'price' => 3,
					'disabled' => !empty($this->restrictions['wss_htaccess_mod_rewrite'])
				)
			),
			'sqlcache' => array(
				'premium' => 0,
				'sql_cache_enabled' => array(
					'value' => $this->compress_options['sql_cache']['enabled'],
					'type' => 'checkbox',
					'price' => 12,
					'disabled' => empty($this->internal_sql)
				),
				'sql_cache_time' => array(
					'value' => $this->compress_options['sql_cache']['time'],
					'type' => 'smalltext',
					'disabled' => empty($this->internal_sql)
				),
				'sql_cache_timeout' => array(
					'value' => $this->compress_options['sql_cache']['timeout'],
					'type' => 'smalltext',
					'disabled' => empty($this->internal_sql)
				),
				'sql_cache_tables_exclude' => array(
					'value' => $this->compress_options['sql_cache']['tables_exclude'],
					'type' => 'textarea',
					'disabled' => empty($this->internal_sql)
				)
			),
			'unobtrusive' => array(
				'premium' => 0,
				'unobtrusive_on' => array(
					'value' => $this->compress_options['unobtrusive']['on'],
					'type' => 'radio',
					'count' => $this->premium < 2 ? 0 : 3,
					'price' => array(0,5,5)
				),
				'unobtrusive_body' => array(
					'value' => $this->compress_options['unobtrusive']['body'],
					'type' => 'checkbox',
					'price' => 2
				),
				'unobtrusive_all' => array(
					'value' => $this->compress_options['unobtrusive']['all'],
					'type' => 'checkbox',
					'price' => 2
				),
				'unobtrusive_informers' => array(
					'value' => $this->compress_options['unobtrusive']['informers'],
					'type' => 'checkbox',
					'hidden' => $this->premium < 2 ? 1 : 0,
					'price' => 3
				),
				'unobtrusive_counters' => array(
					'value' => $this->compress_options['unobtrusive']['counters'],
					'type' => 'checkbox',
					'hidden' => $this->premium < 2 ? 1 : 0,
					'price' => 2
				),
				'unobtrusive_ads' => array(
					'value' => $this->compress_options['unobtrusive']['ads'],
					'type' => 'checkbox',
					'hidden' => $this->premium < 2 ? 1 : 0,
					'price' => 3
				),
				'unobtrusive_iframes' => array(
					'value' => $this->compress_options['unobtrusive']['iframes'],
					'type' => 'checkbox',
					'hidden' => $this->premium < 2 ? 1 : 0,
					'price' => 3
				)
			),
			'multiple_hosts' => array(
				'premium' => 0,
				'parallel_enabled' => array(
					'value' => $this->compress_options['parallel']['enabled'],
					'type' => 'checkbox',
					'price' => 3
				),
				'parallel_check' => array(
					'value' => $this->compress_options['parallel']['check'],
					'type' => 'checkbox'
				),
				'parallel_allowed_list' => array(
					'value' => $this->compress_options['parallel']['allowed_list'],
					'type' => 'text'
				),
				'parallel_css' => array(
					'value' => $this->compress_options['parallel']['css'],
					'type' => 'checkbox',
					'price' => 2
				),
				'parallel_javascript' => array(
					'value' => $this->compress_options['parallel']['javascript'],
					'type' => 'checkbox',
					'price' => 2,
					'onclick' => '_.u(this)',
				),
				'parallel_additional' => array(
					'value' => $this->compress_options['parallel']['additional'],
					'type' => 'textarea',
					'hidden' => 1
				),
				'parallel_additional_list' => array(
					'value' => $this->compress_options['parallel']['additional_list'],
					'type' => 'textarea',
					'hidden' => 1
				),
				'parallel_ignore_list' => array(
					'value' => $this->compress_options['parallel']['ignore_list'],
					'type' => 'textarea',
					'price' => 1
				),
				'parallel_custom' => array(
					'value' => $this->compress_options['parallel']['custom'],
					'type' => 'radio',
					'count' => $this->premium == 10 ? 4 : 3,
					'price' => array(0,0,0,35)
					
				),
				'parallel_ftp' => array(
					'value' => $this->compress_options['parallel']['ftp'],
					'type' => 'text',
					'price' => 2
				),
				'parallel_https' => array(
					'value' => $this->compress_options['parallel']['https'],
					'type' => 'text',
					'hidden' => $this->premium < 2 ? 1 : 0,
					'price' => 5
				),
			)
		);
		if (empty($options['title'])) {
			$options['title'] = constant('_WEBO_OPTIONS_TITLES_' . $config);
		}
		if (empty($options['description'])) {
			$options['description'] = constant('_WEBO_OPTIONS_DESCRIPTIONS_' . $config);
		}
		$fee = 0;
/* calculate current options' fee */
		foreach ($options as $key => $group) {
			if (is_array($group)) {
				foreach ($group as $option) {
					if (!empty($option['price']) && $option['value']) {
						$fee += is_array($option['price']) ?
							$option['price'][$option['value']] : $option['price'];
					}
				}
			}
		}
		$options['fee'] = $fee;
		return $options;
	}

	/**
	* Check for possible restrictions for options
	*
	**/
	function check_options() {
		if (!empty($this->restrictions)) {
			return $this->restrictions;
		}
		$this->restrictions = array();
/* normalize all the other options */
		$this->get_modules();
/* disable .htaccess if not Apache */
		if (empty($this->apache_modules)) {
			$this->restrictions['wss_htaccess_enabled'] = 1;
			$this->restrictions['wss_htaccess_mod_deflate'] = 1;
			$this->restrictions['wss_htaccess_mod_gzip'] = 1;
			$this->restrictions['wss_htaccess_mod_expires'] = 1;
			$this->restrictions['wss_htaccess_mod_mime'] = 1;
			$this->restrictions['wss_htaccess_mod_headers'] = 1;
			$this->restrictions['wss_htaccess_mod_setenvif'] = 1;
			$this->restrictions['wss_htaccess_mod_rewrite'] = 1;
		} else {
			foreach (array(
				'mod_deflate',
				'mod_gzip',
				'mod_expires',
				'mod_mime',
				'mod_headers',
				'mod_setenvif',
				'mod_rewrite') as $module) {
					if (!in_array($module, $this->apache_modules)) {
						$this->restrictions['wss_htaccess_' . $module] = 1;
					}
			}
		}
		$loaded_modules = @get_loaded_extensions();
/* fix CSS Sprites options in case of GD lib failure */
		$gd = function_exists('gd_info') ? gd_info() : array();
		if (!(in_array('gd', $loaded_modules) &&
			function_exists('imagecreatetruecolor') &&
			!empty($gd['GIF Read Support']) &&
			!empty($gd['GIF Create Support']) &&
			(!empty($gd['JPEG Support']) || !empty($gd['JPG Support'])) &&
			!empty($gd['PNG Support']) &&
			!empty($gd['WBMP Support']))) {
				$this->restrictions['wss_css_sprites_enabled'] = 1;
		}
/* try to set some libs executable */
		@chmod($this->basepath . 'libs/yuicompressor/yuicompressor.jar', octdec("0755"));
/* check for YUI availability */
		$YUI_checked = 0;
		if ((empty($_SERVER['SERVER_SOFTWARE']) || !strpos($_SERVER['SERVER_SOFTWARE'], 'IIS')) &&
			(is_file($this->basepath . 'libs/php/class.yuicompressor4.php') || is_file($this->basepath . 'libs/php/class.yuicompressor.php'))) {
			if (substr(phpversion(), 0, 1) == 4) {
				require_once($this->basepath . 'libs/php/class.yuicompressor4.php');
			} else {
				require_once($this->basepath . 'libs/php/class.yuicompressor.php');
			}
			$YUI = new YuiCompressor($this->compress_options['javascript_cachedir'], $this->basepath);
			$YUI_checked = $YUI->check();
		}
		if (!$YUI_checked) {
			$this->restrictions['wss_minify_js2'] = 1;
		}
/* check for curl existence */
		if (empty($loaded_modules) ||
			!in_array('curl', $loaded_modules) ||
			!function_exists('curl_init')) {
				$this->restrictions['wss_external_scripts_on'] = 1;
				$this->restrictions['wss_external_scripts_css'] = 1;
		}
/* check for gzip for HTML possibility */
		if ((!function_exists('gzencode') ||
			!function_exists('gzcompress') ||
			!function_exists('gzdeflate')) &&
			!(!empty($this->input['wss_htaccess_enabled']) &&
			(!empty($this->input['wss_htaccess_mod_deflate']) ||
			!empty($this->input['wss_htaccess_mod_gzip'])))) {
				$this->restrictions['wss_gzip_page'] = 1;
		}
/* check for gzip for fonts possibility */
		if (!(!empty($this->input['wss_htaccess_enabled']) &&
			(!empty($this->input['wss_htaccess_mod_deflate']) ||
			!empty($this->input['wss_htaccess_mod_gzip']) ||
			!empty($this->input['wss_htaccess_mod_rewrite'])))) {
				$this->restrictions['wss_gzip_fonts'] = 1;
		}
/* check for caching extensions */
		if (!@class_exists('Memcached') && !@class_exists('Memcache')) {
			$this->restrictions['wss_performance_cache_engine1'] = 1;
		}
		if (!function_exists('apc_store')) {
			$this->restrictions['wss_performance_cache_engine2'] = 1;
		}
		if (!function_exists('xcache_set')) {
			$this->restrictions['wss_performance_cache_engine3'] = 1;
		}
		if (!function_exists('zend_shm_cache_store')) {
			$this->restrictions['wss_performance_cache_engine4'] = 1;
		}
		if (!function_exists('sem_get')) {
			$this->restrictions['wss_performance_cache_engine5'] = 1;
		}
	}

	/**
	* Save / sanitize all options
	*
	**/
	function set_options() {
/* fix multiple lines in textarea */
		foreach (array(
			'wss_minify_css_file',
			'wss_minify_css_host',
			'wss_minify_javascript_file',
			'wss_minify_javascript_host',
			'wss_external_scripts_include_code',
			'wss_external_scripts_ignore_list',
			'wss_external_scripts_additional_list',
			'wss_external_scripts_minify_exclude',
			'wss_performance_cache_engine_options',
			'wss_footer_image',
			'wss_footer_link',
			'wss_footer_css_code',
			'wss_data_uris_ignore_list',
			'wss_data_uris_additional_list',
			'wss_css_sprites_ignore_list',
			'wss_html_cache_ignore_list',
			'wss_html_cache_allowed_list',
			'wss_html_cache_additional_list',
			'wss_html_cache_params',
			'wss_sql_cache_tables_exclude',
			'wss_parallel_allowed_list',
			'wss_parallel_additional',
			'wss_parallel_additional_list',
			'wss_parallel_ignore_list',
			'wss_parallel_ftp',
			'wss_parallel_https',
			'wss_description',
			'wss_title',
			'wss_config') as $val) {
				$this->input[$val] = trim(str_replace(array("\r\n", "\n", '/"', '"'), array(" ", " ", "&quot;", "&quot;"),
					empty($this->input[$val]) ? '' : $this->input[$val]));
		}
/* make numeric options save */
		foreach (array(
			'wss_combine_css',
			'wss_minify_css_min',
			'wss_minify_javascript',
			'wss_minift_js',
			'wss_unobtrusive_on',
			'wss_performance_cache_version',
			'wss_performance_delete_old',
			'wss_performance_cache_engine',
			'wss_far_future_expires_html_timeout',
			'wss_html_cache_timeout',
			'wss_html_cache_flush_size',
			'wss_sql_cache_time',
			'wss_sql_cache_timeout',
			'wss_data_uris_size',
			'wss_data_uris_mhtml_size',
			'wss_css_sprites_dimensions_limited',
			'wss_css_sprites_truecolor_in_jpeg',
			'wss_css_sprites_ignore',
			'wss_css_sprites_html_limit',
			'wss_parallel_custom',
			'wss_fee') as $val) {
				$this->input[$val] = empty($this->input[$val]) ? 0 : round($this->input[$val]);
		}
/* normalize values for radio buttons */
		foreach (array(
			'wss_performance_cache_engine',
			'wss_css_sprites_truecolor_in_jpeg',
			'wss_css_sprites_ignore',
			'wss_parallel_custom',
			'wss_unobtrusive_on',
			'wss_minify_css_min') as $val) {
				if ($this->input[$val]) {
					$this->input[$val]--;
				}
		}
/* disable don't check files in cache */
		$this->input['wss_performance_cache_version'] =
			empty($this->input['wss_performance_check_files']) ? 0 :
			$this->input['wss_performance_cache_version'];
		foreach (array(
			'wss_unobtrusive_body',
			'wss_unobtrusive_all',
			'wss_unobtrusive_informers',
			'wss_unobtrusive_ads',
			'wss_unobtrusive_counters',
			'wss_unobtrusive_iframes',
			'wss_external_scripts_on',
			'wss_external_scripts_inline',
			'wss_external_scripts_head_end',
			'wss_external_scripts_css',
			'wss_external_scripts_css_inline',
			'wss_external_scripts_include_try',
			'wss_external_scripts_duplicates',
			'wss_performance_mtime',
			'wss_performance_plain_string',
			'wss_performance_cache_version',
			'wss_performance_uniform_cache',
			'wss_performance_restore_properties',
			'wss_minify_page',
			'wss_minify_html_comments',
			'wss_minify_html_one_string',
			'wss_gzip_javascript',
			'wss_gzip_page',
			'wss_gzip_css',
			'wss_gzip_fonts',
			'wss_gzip_cookie',
			'wss_gzip_noie',
			'wss_far_future_expires_javascript',
			'wss_far_future_expires_css',
			'wss_far_future_expires_images',
			'wss_far_future_expires_fonts',
			'wss_far_future_expires_video',
			'wss_far_future_expires_static',
			'wss_far_future_expires_html',
			'wss_far_future_expires_external',
			'wss_html_cache_enabled',
			'wss_html_cache_flush_only',
			'wss_html_cache_enhanced',
			'wss_sql_cache_enabled',
			'wss_footer_text',
			'wss_footer_spot',
			'wss_footer_counter',
			'wss_data_uris_on',
			'wss_data_uris_separate',
			'wss_data_uris_domloaded',
			'wss_data_uris_mhtml',
			'wss_css_sprites_enabled',
			'wss_css_sprites_aggressive',
			'wss_css_sprites_extra_space',
			'wss_css_sprites_no_ie6',
			'wss_css_sprites_html_sprites',
			'wss_css_sprites_html_page',
			'wss_parallel_enabled',
			'wss_parallel_check',
			'wss_parallel_css',
			'wss_parallel_javascript',
			'wss_htaccess_enabled',
			'wss_htaccess_mod_deflate',
			'wss_htaccess_mod_gzip',
			'wss_htaccess_mod_expires',
			'wss_htaccess_mod_headers',
			'wss_htaccess_mod_setenvif',
			'wss_htaccess_mod_rewrite',
			'wss_htaccess_mod_mime',
			'wss_htaccess_local') as $val) {
				$this->input[$val] = empty($this->input[$val]) ? 0 : 1;
		}
		$this->check_options();
		foreach ($this->restrictions as $key => $restriction) {
			$this->input[$key] = 0;
		}
/* make specific fake option for Apache envs. */
		$this->input['wss_htaccess_mod_symlinks'] = in_array('mod_symlinks', $this->apache_modules);
/* correct multiple hosts list */
		if (!empty($this->input['wss_parallel_check'])) {
			$hosts = explode(" ", $this->input['wss_parallel_allowed_list']);
			$this->input['wss_parallel_allowed_list'] = $this->check_hosts($hosts);
		}
/* map CSS merge options to real one */
		switch ($this->input['wss_combine_css']) {
			case 3:
				$this->input['wss_minify_css'] = 1;
				$this->input['wss_minify_css_body'] = 1;
				break;
			case 2:
				$this->input['wss_minify_css'] = 1;
				$this->input['wss_minify_css_body'] = 0;
				break;
			default:
				$this->input['wss_minify_css'] = 0;
				$this->input['wss_minify_css_body'] = 0;
				break;
		}
/* map JavaScript merge options to real one */
		switch ($this->input['wss_minify_javascript']) {
			case 3:
				$this->input['wss_minify_javascript'] = 1;
				$this->input['wss_minify_javascript_body'] = 1;
				break;
			case 2:
				$this->input['wss_minify_javascript'] = 1;
				$this->input['wss_minify_javascript_body'] = 0;
				break;
			default:
				$this->input['wss_minify_javascript'] = 0;
				$this->input['wss_minify_javascript_body'] = 0;
				break;
		}
/* map JavaScript minify options to real one */
		switch ($this->input['wss_minify_js']) {
			case 4:
				$this->input['wss_minify_with_jsmin'] = 0;
				$this->input['wss_minify_with_yui'] = 0;
				$this->input['wss_minify_with_packer'] = 1;
				break;
			case 3:
				$this->input['wss_minify_with_jsmin'] = 0;
				$this->input['wss_minify_with_yui'] = 1;
				$this->input['wss_minify_with_packer'] = 0;
				break;
			case 2:
				$this->input['wss_minify_with_jsmin'] = 1;
				$this->input['wss_minify_with_yui'] = 0;
				$this->input['wss_minify_with_packer'] = 0;
				break;
			default:
				$this->input['wss_minify_with_jsmin'] = 0;
				$this->input['wss_minify_with_yui'] = 0;
				$this->input['wss_minify_with_packer'] = 0;
				break;
		}
		$image = $this->input['wss_footer_image'];
		if (!empty($image)) {
			@copy($this->basepath . 'images/' . $image,
				$this->compress_options['css_cachedir'] . $image);
		}
		if (!empty($this->input['wss_parallel_ftp'])) {
			$this->check_cdn();
		}
		if (!empty($this->input['wss_page']) && $this->input['wss_page'] == 'install_options') {
/* Try to re-define configuration name from predefined set */
			if (empty($this->input['wss_apply'])) {
				if (in_array($this->input['wss_config'], array('safe', 'optimal', 'extreme', 'basic'))) {
					if (@is_file($this->basepath . 'config.user.php')) {
						$i = 1;
						while (@is_file($this->basepath . 'config.user'. ($i++) .'.php')) {}
						$this->input['wss_config'] = 'user' . ($i - 1);
					} else {
						$this->input['wss_config'] = 'user';
					}
				}
			}
/* Apply options or just save them? */
			if (!empty($this->input['wss_apply']) || 
				($this->input['wss_config'] == $this->compress_options['config'])) {
/* Save the options	to work config */
				foreach($this->compress_options as $key => $option) {
					if (is_array($option)) {
						foreach($option as $option_name => $option_value) {
							if (isset($this->input['wss_' . strtolower($key) . '_' . strtolower($option_name)])) {
								$this->save_option("['" .
									strtolower($key) . "']['" .
									strtolower($option_name) . "']",
									$this->input['wss_' .
									strtolower($key) . '_' .
									strtolower($option_name)]);
							}
						}
					} else {
						if (isset($this->input['wss_' . strtolower($key)])) {
							$this->save_option("['" . strtolower($key)
								. "']", $this->input['wss_' . strtolower($key)]);
						}
					}
				}
/* re-check grade if application is active */
				if (!empty($this->compress_options['active']) && $this->premium > 1) {
					@unlink($this->basepath . $this->index_after);
					$this->view->download($this->webo_grade . '&refresh=on',
						$this->basepath . $this->index_after, 1);
				}
			}
/* Save the options to backup config */
			if (!empty($this->input['wss_config']) &&
				(strpos($this->input['wss_config'], 'user') !== false ||
				strpos($this->input['wss_config'], 'auto') !== false)) {
					$this->options_file = 'config.' .
						preg_replace("/[^a-zA-Z0-9]*/", "",
						$this->input['wss_config']) . '.php';
					if (!@is_file($this->basepath . $this->options_file)) {
						@copy($this->basepath . 'config.safe.php',
							$this->basepath . $this->options_file);
						@chmod($this->basepath . $this->options_file,
							octdec("0644"));
					}
					$this->save_option("['title']",
						$this->input['wss_title']);
					$this->save_option("['description']",
						$this->input['wss_description']);
					foreach($this->compress_options as $key => $option) {
						if (is_array($option)) {
							foreach($option as $option_name => $option_value) {
								if (isset($this->input['wss_' . strtolower($key) .
									'_' . strtolower($option_name)])) {
									$this->save_option("['" . strtolower($key) .
										"']['" . strtolower($option_name) . "']",
										$this->input['wss_' .
										strtolower($key) . '_' .
										strtolower($option_name)]);
								}
							}
						} else {
							if (isset($this->input['wss_' . strtolower($key)])) {
								$this->save_option("['" . strtolower($key) . "']",
									$this->input['wss_' . strtolower($key)]);
							}
						}
					}
/* can't create new config file */
					if (!empty($this->error[0])) {
						$this->save_option("['config']", 'safe');
					}
			}
		}
	}
	
	/**
	* Checks FTP / API access to remote CDN host
	**/
	function check_cdn () {
		$this->error = $this->error ? $this->error : array();
		$auth = $this->input['wss_parallel_ftp'];
/* Rack Space Cloud */
		if ($last = strpos($auth, '@RSC')) {
			$first = strpos($auth, ':');
			$user = substr($auth, 0, $first);
			$key = substr($auth, $first + 1, $last - $first - 1);
/* perform authorization */
			$headers = $this->view->upload('https://auth.api.rackspacecloud.com/v1.0',
				'', $this->options['html_cachedir'],
				array('X-Auth-User: ' . $user, 'X-Auth-Key: ' . $key));
			if (strpos($headers, 'Error: ') === false && strpos($headers, 'HTTP/1.1 401') === false) {
				$token = preg_replace("@.*X-Auth-Token: (.*?)\r?\n.*@is", "$1", $headers);
/* create wo container */
				$this->view->upload(preg_replace("@.*X-Storage-Url: (.*?)\r?\n.*@is", "$1", $headers) . '/wo',
					'', $this->options['html_cachedir'],
					array('X-Auth-Token: ' . $token, 'X-Referrer-ACL: 259200'), 'PUT');
/* remember current CDN URL */
				$headers = $this->view->upload(preg_replace("@.*X-CDN-Management-Url: (.*?)\r?\n.*@is", "$1", $headers) . '/wo',
					'', $this->options['html_cachedir'],
					array('X-Auth-Token: ' . $token, 'X-Referrer-ACL: 259200'), 'HEAD');
				$cdn = preg_replace("@.*X-CDN-URI: https?://(.*?)\r?\n.*@is", "$1", $headers);
				if (!$this->input['wss_minify_css_host']) {
					$this->input['wss_minify_css_host'] = $cdn;
				}
				if (!$this->input['wss_minify_javascript_host']) {
					$this->input['wss_minify_javascript_host'] = $cdn;
				}
			} else {
				$this->error[11] = 1;
			}
/* common FTP */
		} else {
			$file = @is_file($this->options['document_root'] . 'favicon.ico') ?
				$this->options['document_root'] . 'favicon.ico' :
				$this->basepath . 'favicon.ico';
			$headers = $this->view->upload('ftp://' .
				preg_replace("!^([^@]+)@([^:]+):([^@]+)@!", "$1:$3@", $auth),
				str_replace($this->options['document_root'], "/", $file),
				$file, $this->options['html_cachedir'], array(), 
				preg_replace("!(.*)@.*!", "$1", $auth));
			if (strpos($headers, 'Error: ') !== false) {
				$this->error[11] = 1;
			}
		}
	}

	/**
	* Write Disallow instructions to robots.txt
	**/
	function write_robots ($root, $html, $css, $javascript) {
		$robots = $root . '/robots.txt';
/* create backup */
		if (!@is_file($robots . '.backup')) {
			@copy($robots, $robots . '.backup');
		}
		$content_saved = @file_get_contents($robots);
		$content_saved = $this->clean_htaccess($content_saved);
		$directories = array_unique(array(
			str_replace($root, '/', $html),
			str_replace($root, '/', $css),
			str_replace($root, '/', $javascript)));
		$content_saved .= '
# Web Optimizer options
User-Agent: *
';
		foreach ($directories as $dir) {
			$content_saved .= 'Disallow: ' . $dir . '
';
		}
		$content_saved .= '# Web Optimizer end
';
		$this->write_file($robots, $content_saved);
	}

	/**
	* Returns actual .htaccess file name
	**/
	function detect_htaccess () {
		if (empty($this->compress_options['htaccess']['local'])) {
			$htaccess = $this->compress_options['document_root'] . '.htaccess';
		} else {
			$htaccess = $this->compress_options['website_root'] . '.htaccess';
		}
		return $htaccess;
	}

	/**
	* Cleans all previous rules from .htaccess file content
	**/
	function clean_htaccess ($content_saved = '') {
		if (empty($content_saved)) {
			$content_saved = @file_get_contents($this->htaccess);
		}
		$content_saved = preg_replace("@\r?\n?# Web Optimizer (options|path).*?# Web Optimizer (path )?end\r?\n?@is", "", $content_saved);
		return $content_saved;
	}
	
	/**
	* Checks and writes all optimized rules to .htaccess file
	**/
	function write_htaccess ($base = '/') {
		$this->view->set_paths($this->compress_options['document_root']);
/* re-check base */
		if ($base == '/' && !empty($this->compress_options['htaccess']['local'])) {
			$base = str_replace($this->compress_options['document_root'], '/',
				$this->compress_options['website_root']);
		}
/* write robotx.txt exclusion for cache files */
		$this->write_robots($this->compress_options['website_root'],
			$this->compress_options['html_cachedir'],
			$this->compress_options['css_cachedir'],
			$this->compress_options['javascript_cachedir']);
/* delete previous Web Optimizer rules */
		$this->htaccess = $this->detect_htaccess();
		$content_saved = $this->clean_htaccess();
		if (!@is_writable($this->htaccess) && @is_file($this->htaccess)) {
			$this->error = $this->error ? $this->error : array();
			$this->error[10] = 1;
		}
/* create backup */
		if (!@is_file($this->htaccess . '.backup')) {
			@copy($this->htaccess, $this->htaccess . '.backup');
		}
		$content = '# Web Optimizer options';
		$content2 = '';
		if (!empty($this->input['wss_htaccess_enabled']) && $this->compress_options['active']) {
			$content_enhanced = '';
			if (!empty($this->input['wss_html_cache_enabled']) && !empty($this->input['wss_html_cache_enhanced'])) {
/* create rules for enhanced HTML caching mode */
				$content_enhanced = "
	" . (empty($this->compress_options['charset']) ? '' : 'AddDefaultCharset ' . $this->compress_options['charset']);
				$cookie = array();
/* WordPress-related cookie to skip server side caching */
				if (strstr($this->basepath, 'wp-content')) {
					$cookie[] = 'wordpress';
					$cookie[] = 'wp-postpass_';
				}
/* generic cookies to skip server side caching */
				if (!empty($this->input['wss_html_cache_additional_list'])) {
					$cookies = explode($this->input['wss_html_cache_additional_list'], ' ');
					foreach ($cookies as $c) {
						$cookie[] = $c;
					}
				}
				if (count($cookie)) {
					$cookie = '!^.*(' . implode($cookie, '|') . ').*$';
				}
				if (!empty($this->input['wss_htaccess_mod_setenvif'])) {
					if ($cookie) {
						$content_enhanced .= "
	RewriteCond %{HTTP:Cookie} " . $cookie;
					}
					$content_enhanced .= "
	RewriteCond %{REQUEST_METHOD} !=POST
	RewriteCond \"" . $this->compress_options['html_cachedir'] . "%{HTTP_HOST}/%{REQUEST_URI}%{QUERY_STRING}/index%{ENV:WSSBR}.html%{ENV:WSSENC}\" -f
	RewriteRule (.*) " . str_replace($this->compress_options['document_root'], "/", $this->compress_options['html_cachedir']) . "%{HTTP_HOST}/$1/index%{ENV:WSSBR}.html%{ENV:WSSENC} [L]";
				} else {
					$browsers = empty($this->input['wss_performance_uniform_cache']) ?
						array(
							'MSIE 6' => '.ie6',
							'MSIE 7' => '.ie7',
							'MSIE 8' => '.ie8',
							'Android|BlackBerry|HTC|iPhone|iPod|LG|MOT|Mobile|NetFront|Nokia|Opera Mini|Palm|PPC|SAMSUNG|Smartphone|SonyEricsson|Symbian|UP.Browser|webOS' => '.ma') : array();
					$browsers[] = '';
					$encodings = (empty($this->input['wss_gzip_page']) ||
						!empty($this->input['wss_htaccess_mod_gzip']) ||
						!empty($this->input['wss_htaccess_mod_deflate'])) &&
						!empty($this->input['wss_htaccess_mod_mime']) ?
						array() : array('gzip' => '.gz', 'deflate' => '.df');
					$encodings[] = '';
					foreach ($encodings as $enc => $encoding) {
						foreach ($browsers as $br => $browser) {
							$content_enhanced .= "
	RewriteCond %{REQUEST_METHOD} !=POST";
							if ($br) {
								$content_enhanced .= "
	RewriteCond %{HTTP_USER_AGENT} \"" . $br . "\"";
							}
							if ($enc) {
								$content_enhanced .= "
	RewriteCond %{HTTP:Accept-Encoding} ". $enc;
							}
							if ($cookie) {
								$content_enhanced .= "
	RewriteCond %{HTTP:Cookie} " . $cookie;
							}
							$content_enhanced .= "
	RewriteCond \"" . $this->compress_options['html_cachedir'] . "%{HTTP_HOST}/%{REQUEST_URI}%{QUERY_STRING}/index". $browser .".html". $encoding ."\" -f
	RewriteRule (.*) " . str_replace($this->compress_options['document_root'], "", $this->compress_options['html_cachedir']) . "%{HTTP_HOST}/$1/index". $browser .".html". $encoding ." [L]";
						}
					}
				}
			}
/* rules for gzip via mod_gzip */
			if (!empty($this->input['wss_htaccess_mod_gzip'])) {
				$content .= "
<IfModule mod_gzip.c>
	mod_gzip_on Yes
	mod_gzip_can_negotiate Yes
	mod_gzip_static_suffix .gz
	mod_gzip_update_static No
	mod_gzip_keep_workfiles No
	mod_gzip_minimum_file_size 500
	mod_gzip_maximum_file_size 5000000
	mod_gzip_maximum_inmem_size 60000
	mod_gzip_min_http 1000
	mod_gzip_handle_methods GET POST
	mod_gzip_item_exclude reqheader \"User-agent: Mozilla/4.0[678]\"
	mod_gzip_dechunk No";
				if (!empty($this->input['wss_gzip_page'])) {
					$content .= "
	mod_gzip_item_include mime ^text/plain$
	mod_gzip_item_include mime ^text/html$
	mod_gzip_item_include mime ^text/xml$
	mod_gzip_item_include mime ^application/xhtml+xml$
	mod_gzip_item_include mime ^image/x-icon$
	mod_gzip_item_include mime ^httpd/unix-directory$";
				}
				if (!empty($this->input['wss_gzip_css'])) {
					$content .= "
	mod_gzip_item_include mime ^text/css$";
				}
				if (!empty($this->input['wss_gzip_javascript'])) {
					$content .= "
	mod_gzip_item_include mime ^text/javascript$
	mod_gzip_item_include mime ^application/javascript$
	mod_gzip_item_include mime ^application/x-javascript$
	mod_gzip_item_include mime ^text/x-js$
	mod_gzip_item_include mime ^text/ecmascript$
	mod_gzip_item_include mime ^application/ecmascript$
	mod_gzip_item_include mime ^text/vbscript$
	mod_gzip_item_include mime ^text/fluffscript$";
				}
				if (!empty($this->input['wss_gzip_fonts'])) {
					$content .= "
	mod_gzip_item_include mime ^image/svg+xml$
	mod_gzip_item_include mime ^application/x-font$
	mod_gzip_item_include mime ^application/x-font-ttf$
	mod_gzip_item_include mime ^font/opentype$
	mod_gzip_item_include mime ^font/otf$
	mod_gzip_item_include mime ^font/ttf$
	mod_gzip_item_include mime ^application/x-font-opentype$
	mod_gzip_item_include mime ^application/x-font-truetype$
	mod_gzip_item_include mime ^application/vnd.ms-fontobject
	mod_gzip_item_include mime ^application/vnd.oasis.opendocument.formula-template$";
				}
				$content .= "
</IfModule>";
			}
			if (!empty($this->input['wss_htaccess_mod_setenvif'])) {
				$content .= "
<IfModule mod_setenvif.c>
	BrowserMatch ^Mozilla/4 gzip-only-text/html
	BrowserMatch ^Mozilla/4\.0[678] no-gzip
	BrowserMatch SV1; !no_gzip
	BrowserMatch \bMSIE !no-gzip !gzip-only-text/html";
				if (!empty($this->input['wss_html_cache_enabled']) &&
					!empty($this->input['wss_html_cache_enhanced']) &&
					!empty($this->input['wss_gzip_page']) &&
					empty($this->input['wss_htaccess_mod_gzip']) &&
					empty($this->input['wss_htaccess_mod_deflate'])) {
						$content .= "
	SetEnvIfNoCase accept-encoding deflate WSSENC=.df
	SetEnvIfNoCase accept-encoding gzip WSSENC=.gz";
				}
				if (empty($this->input['wss_performance_uniform_cache']) &&
					!empty($this->input['wss_html_cache_enabled']) &&
					!empty($this->input['wss_html_cache_enhanced'])) {
						$content .="
	BrowserMatch \"MSIE 6\" WSSBR=.ie6
	BrowserMatch \"MSIE 7\" WSSBR=.ie7
	BrowserMatch \"MSIE 8\" WSSBR=.ie8
	BrowserMatch \"Android|BlackBerry|HTC|iPhone|iPod|LG|MOT|Mobile|NetFront|Nokia|Opera Mini|Palm|PPC|SAMSUNG|Smartphone|SonyEricsson|Symbian|UP.Browser|webOS\" WSSBR=.ma";
				}
				$content .= "
</IfModule>";
			}
			if (!empty($this->input['wss_htaccess_mod_deflate'])) {
				$content .= "
<IfModule mod_deflate.c>";
				if (!empty($this->input['wss_gzip_page'])) {
					$content .= "
	AddOutputFilterByType DEFLATE text/plain text/html text/xml application/xhtml+xml image/x-icon";
				}
				if (!empty($this->input['wss_gzip_css'])) {
					$content .= "
	AddOutputFilterByType DEFLATE text/css";
				}
				if (!empty($this->input['wss_gzip_javascript'])) {
					$content .= "
	AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript text/x-js text/ecmascript application/ecmascript text/vbscript text/fluffscript";
				}
/* add gzip for fonts
http://www.phpied.com/gzip-your-font-face-files/ */
				if (!empty($this->input['wss_gzip_fonts'])) {
					$content .= "
	AddOutputFilterByType DEFLATE image/svg+xml application/x-font-ttf application/x-font font/opentype font/otf font/ttf application/x-font-truetype application/x-font-opentype application/vnd.ms-fontobject application/vnd.oasis.opendocument.formula-template";
				}
				$content .= "
</IfModule>";
			}
/* prevent 403 error due to no FollowSymLinks
http://www.elharo.com/blog/software-development/web-development/2006/01/02/two-tips-for-fixing-apache-problems/
http://code.google.com/p/web-optimizator/issues/detail?id=156 */
			if (!empty($this->input['wss_htaccess_mod_symlinks'])) {
				$content .= "
Options +FollowSymLinks";
			}
/* try to add static gzip */
			if (!empty($this->input['wss_htaccess_mod_mime'])) {
				$content .= "
<IfModule mod_mime.c>
	AddEncoding gzip .gz
	AddEncoding deflate .df
	<FilesMatch \.html\.(gz|df)$>
		ForceType text/html
	</FilesMatch>
	<FilesMatch \.xml\.gz$>
		ForceType text/xml
	</FilesMatch>
	<FilesMatch \.txt\.gz$>
		ForceType text/plain
	</FilesMatch>
	<FilesMatch \.ico\.gz$>
		ForceType image/x-icon
	</FilesMatch>
	<FilesMatch \.css\.gz$>
		ForceType text/css
	</FilesMatch>
	<FilesMatch \.js\.gz$>
		ForceType application/x-javascript
	</FilesMatch>
	<FilesMatch \.svg\.gz$>
		ForceType image/svg+xml
	</FilesMatch>
	<FilesMatch \.ttf\.gz$>
		ForceType font/ttf
	</FilesMatch>
	<FilesMatch \.otf\.gz$>
		ForceType font/otf
	</FilesMatch>
	<FilesMatch \.eot\.gz$>
		ForceType application/vnd.ms-fontobject
	</FilesMatch>
	AddType video/ogg ogg ogv
	AddType video/mp4 mp4
	AddType video/webm webm
	AddType image/svg+xml svg svgz
	AddType application/vnd.ms-fontobject eot
	AddType font/ttf ttf
	AddType font/otf otf
	AddType font/x-woff woff
	AddType application/vnd.ms-fontobject eot
	AddType text/cache-manifest manifest
</IfModule>";
				if (!empty($this->input['wss_htaccess_mod_rewrite'])) {
					$content .= "
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase $base";
					if (!empty($this->input['wss_far_future_expires_css'])) {
						$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(css|php)$ $1.$2";
					}
					if (!empty($this->input['wss_far_future_expires_javascript'])) {
						$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(js|php)$ $1.$2";
					}
					if (!empty($this->input['wss_far_future_expires_images'])) {
						$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(jpe?g|png)$ $1.$2";
					}
					if (!empty($this->input['wss_gzip_page'])) {
						$content .= "
	RewriteCond %{HTTP:Accept-encoding} gzip
	RewriteCond %{HTTP_USER_AGENT} !Konqueror
	RewriteCond %{REQUEST_FILENAME}.gz -f
	RewriteRule ^(.*)\.ico$ $1.ico.gz [QSA,L]
	RewriteCond %{HTTP:Accept-encoding} gzip
	RewriteCond %{HTTP_USER_AGENT} !Konqueror
	RewriteCond %{REQUEST_FILENAME}.gz -f
	RewriteRule ^(.*)\.xml$ $1.xml.gz [QSA,L]
	RewriteCond %{HTTP:Accept-encoding} gzip
	RewriteCond %{HTTP_USER_AGENT} !Konqueror
	RewriteCond %{REQUEST_FILENAME}.gz -f
	RewriteRule ^(.*)\.txt$ $1.txt.gz [QSA,L]";
				}
					if (!empty($this->input['wss_gzip_css'])) {
						$content .= "
	RewriteCond %{HTTP:Accept-encoding} gzip
	RewriteCond %{HTTP_USER_AGENT} !Konqueror
	RewriteCond %{REQUEST_FILENAME}.gz -f
	RewriteRule ^(.*)\.css$ $1.css.gz [QSA,L]";
					}
					if (!empty($this->input['wss_gzip_javascript'])) {
						$content .= "
	RewriteCond %{HTTP:Accept-encoding} gzip
	RewriteCond %{HTTP_USER_AGENT} !Konqueror
	RewriteCond %{REQUEST_FILENAME}.gz -f
	RewriteRule ^(.*)\.js$ $1.js.gz [QSA,L]";
					}
					if (!empty($this->input['wss_gzip_fonts'])) {
						$content .= "
	RewriteCond %{HTTP:Accept-encoding} gzip
	RewriteCond %{HTTP_USER_AGENT} !Konqueror
	RewriteCond %{REQUEST_FILENAME}.gz -f
	RewriteRule ^(.*)\.(ttf|otf|eot|svg)$ $1.$2.gz [QSA,L]";
					}
					$content .= $content_enhanced;
/* there is gzip enabled, mod_expires exists, but no mod_deflate/mod_gzip */
					if (!empty($this->input['wss_htaccess_mod_expires']) &&
						empty($this->input['wss_htaccess_mod_gzip']) &&
						empty($this->input['wss_htaccess_mod_deflate']) &&
						@is_file($this->compress_options['css_cachedir'] . 'wo.static.php') &&
						!empty($this->premium)) {
							$cachedir = str_replace($this->compress_options['document_root'],
								"/", $this->compress_options['css_cachedir']);
							if (!empty($this->input['wss_gzip_css'])) {
							$content .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.css$ " . $cachedir . "wo.static.php?" . $base . "$1.css [L]";
							}
							if (!empty($this->input['wss_gzip_javascript'])) {
							$content .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.js$ " . $cachedir . "wo.static.php?" . $base . "$1.js [L]";
							}
					}
					$content .= "
</IfModule>";
				}
			} elseif (!empty($this->input['wss_htaccess_mod_rewrite'])) {
				$content .= "
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase $base";
				if (!empty($this->input['wss_far_future_expires_css'])) {
					$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(css|php)$ $1.$2";
				}
				if (!empty($this->input['wss_far_future_expires_javascript'])) {
					$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(js|php)$ $1.$2";
				}
				if (!empty($this->input['wss_far_future_expires_images'])) {
					$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(jpe?g|png)$ $1.$2";
				}
				$content .= $content_enhanced;
				$content .= "
</IfModule>";
			}
			if (!empty($this->input['wss_htaccess_mod_expires']) && !empty($this->premium)) {
				$content2 .= "
# Web Optimizer options
<IfModule mod_expires.c>
	ExpiresActive On
	<FilesMatch \.manifest$>
		ExpiresDefault A0
	</FilesMatch>
	ExpiresByType text/cache-manifest A0";
				if (!empty($this->input['wss_far_future_expires_html'])) {
					$content2 .= "
	<FilesMatch \.(html|xhtml|xml|shtml|phtml|php)$>
		ExpiresDefault \"access plus " . $this->input['wss_far_future_expires_html_timeout'] . " seconds\"
	</FilesMatch>
	ExpiresByType text/html A" . $this->input['wss_far_future_expires_html_timeout'] . "
	ExpiresByType text/xml A" . $this->input['wss_far_future_expires_html_timeout'] . "
	ExpiresByType application/xhtml+xml A" . $this->input['wss_far_future_expires_html_timeout'] . "
	ExpiresByType text/plain A" . $this->input['wss_far_future_expires_html_timeout'] . "
	ExpiresByType application/rss+xml A" . $this->input['wss_far_future_expires_html_timeout'];
				}
				if (!empty($this->input['wss_far_future_expires_css'])) {
					$content2 .= "
	<FilesMatch \.css$>
		ExpiresDefault \"access plus 10 years\"
	</FilesMatch>
	ExpiresByType text/css A315360000";
				}
				if (!empty($this->input['wss_far_future_expires_javascript'])) {
					$content2 .= "
	<FilesMatch \.js$>
		ExpiresDefault \"access plus 10 years\"
	</FilesMatch>
	ExpiresByType text/javascript A315360000
	ExpiresByType application/javascript A315360000
	ExpiresByType application/x-javascript A315360000
	ExpiresByType text/x-js A315360000
	ExpiresByType text/ecmascript A315360000
	ExpiresByType application/ecmascript A315360000
	ExpiresByType text/vbscript A315360000
	ExpiresByType text/fluffscript A315360000";
				}
				if (!empty($this->input['wss_far_future_expires_images'])) {
					$content2 .= "
	<FilesMatch \.(bmp|png|gif|jpe?g|ico)$>
		ExpiresDefault \"access plus 10 years\"
	</FilesMatch>
	ExpiresByType image/gif A315360000
	ExpiresByType image/png A315360000
	ExpiresByType image/jpg A315360000
	ExpiresByType image/jpeg A315360000
	ExpiresByType image/x-icon A315360000
	ExpiresByType image/bmp A315360000";
				}
				if (!empty($this->input['wss_far_future_expires_fonts'])) {
					$content2 .= "
	<FilesMatch \.(eot|ttf|otf|svg|woff)$>
		ExpiresDefault \"access plus 10 years\"
	</FilesMatch>
	ExpiresByType image/svg+xml A315360000
	ExpiresByType application/x-font-opentype A315360000
	ExpiresByType application/x-font-truetype A315360000
	ExpiresByType application/x-font-ttf A315360000
	ExpiresByType application/x-font A315360000
	ExpiresByType application/vnd.oasis.opendocument.formula-template A315360000
	ExpiresByType application/vnd.ms-fontobject A315360000
	ExpiresByType font/ttf A315360000
	ExpiresByType font/opentype A315360000
	ExpiresByType font/otf A315360000
	ExpiresByType font/woff A315360000";
				}
				if (!empty($this->input['wss_far_future_expires_video'])) {
					$content2 .= "
	<FilesMatch \.(flv|wmv|asf|asx|wma|wax|wmx|wm|ogg|mp4|mp3|mid|wav)$>
		ExpiresDefault \"access plus 10 years\"
	</FilesMatch>
	ExpiresByType audio/mpeg A315360000
	ExpiresByType audio/ogg A315360000
	ExpiresByType audio/mid A315360000
	ExpiresByType audio/x-wav A315360000
	ExpiresByType video/x-flv A315360000
	ExpiresByType video/x-ms-wmv A315360000
	ExpiresByType video/x-ms-asf A315360000
	ExpiresByType video/x-ms-asx A315360000
	ExpiresByType video/x-ms-wma A315360000
	ExpiresByType video/x-ms-wax A315360000
	ExpiresByType video/x-ms-wmx A315360000
	ExpiresByType video/x-ms-wm A315360000
	ExpiresByType video/ogg A315360000
	ExpiresByType video/mp4 A315360000";
				}
				if (!empty($this->input['wss_far_future_expires_static'])) {
					$content2 .= "
	<FilesMatch \.(swf|pdf|doc|rtf|xls|ppt)$>
		ExpiresDefault \"access plus 10 years\"
	</FilesMatch>
	ExpiresByType application/x-shockwave-flash A315360000
	ExpiresByType application/pdf A315360000
	ExpiresByType application/msword A315360000
	ExpiresByType application/rtf A315360000
	ExpiresByType application/vnd.ms-excel A315360000
	ExpiresByType application/vnd.ms-powerpoint A315360000";
				}
				$content2 .= "
</IfModule>
# Web Optimizer end";
/* add Expires headers via PHP script if we don't have mod_expires */
			} elseif (!empty($this->input['wss_htaccess_mod_rewrite']) &&
				@is_file($this->compress_options['css_cachedir'] . 'wo.static.php')) {
					$cachedir = str_replace($this->compress_options['document_root'],
						"/", $this->compress_options['css_cachedir']);
					$content2 .= "
# Web Optimizer options
<IfModule mod_rewrite.c>";
					if (!empty($this->input['wss_far_future_expires_css'])) {
						$content2 .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.css$ " . $cachedir . "wo.static.php?" . $base . "$1.css [L]";
					}
					if (!empty($this->input['wss_far_future_expires_javascript'])) {
						$content2 .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.js$ " . $cachedir . "wo.static.php?" . $base . "$1.js [L]";
					}
				if (!empty($this->input['wss_far_future_expires_images'])) {
						$content2 .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.(bmp|gif|png|jpe?g|ico)$ " . $cachedir . "wo.static.php?" . $base . "$1.$2 [L]";
					}
					if (!empty($this->input['wss_far_future_expires_video'])) {
						$content2 .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.(flv|wmv|asf|asx|wma|wax|wmx|wm)$ " . $cachedir . "wo.static.php?" . $base . "$1.$2 [L]";
					}
					if (!empty($this->input['wss_far_future_expires_static'])) {
						$content2 .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.(swf|pdf|doc|rtf|xls|ppt)$ " . $cachedir . "wo.static.php?" . $base . "$1.$2 [L]";
					}
					if (!empty($this->input['wss_far_future_expires_fonts'])) {
						$content2 .= "
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteRule ^(.*)\.(eot|ttf|otf|svg)$ " . $cachedir . "wo.static.php?" . $base . "$1.$2 [L]";
					}
					$content2 .= "
</IfModule>
# Web Optimizer end";
			}
			if (!empty($this->input['wss_htaccess_mod_headers']) && !empty($this->premium)) {
				$content .= "
<IfModule mod_headers.c>";
				if (!empty($this->input['wss_htaccess_mod_deflate']) ||
					!empty($this->input['wss_htaccess_mod_gzip'])) {
						$content .= "
	<FilesMatch \.(css|js)$>
		Header append Vary User-Agent
		Header append Vary Accept-Encoding
		Header append Cache-Control private
	</FilesMatch>";
				}
				if (!empty($this->input['wss_htaccess_mod_expires'])) {
					$content .= "
	<FilesMatch \.(bmp|png|gif|jpe?g|ico|flv|wmv|asf|asx|wma|wax|wmx|wm|ogg|mp4|mp3|wav|mid|swf|pdf|doc|rtf|xls|ppt|eot|ttf|otf|svg|woff)$>
		Header append Cache-Control public
	</FilesMatch>
	<FilesMatch \.(js|css|bmp|png|gif|jpe?g|ico|flv|wmv|asf|asx|wma|wax|wmx|wm|ogg|mp4|mp3|wav|mid|swf|pdf|doc|rtf|xls|ppt|woff)$>
		Header unset Last-Modified
		FileETag MTime
	</FilesMatch>";
				}
				$content .= "
</IfModule>";
			}
		} elseif (!empty($this->input['wss_htaccess_mod_rewrite']) && !empty($this->input['wss_htaccess_enabled'])) {
			$content .= "
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase $base";
			if (!empty($this->input['wss_far_future_expires_css'])) {
				$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(css|php)$ $1.$2";
			}
			if (!empty($this->input['wss_far_future_expires_javascript'])) {
				$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(js|php)$ $1.$2";
			}
			if (!empty($this->input['wss_far_future_expires_images'])) {
				$content .= "
	RewriteRule ^(.*)\.wo[0-9]+\.(jpe?g|png)$ $1.$2";
			}
			$content .= "
</IfModule>";
		}
		$content .= "\n# Web Optimizer end";
/* define CMS */
		$this->cms_version = $this->system_info($this->view->paths['absolute']['document_root']);
		$cms_frameworks = array('Zend Framework', 'Symfony', 'CodeIgniter', 'Kohana', 'Yii', 'CakePHP');
/* prevent rewrite to admin access on frameworks */
		if (in_array($this->cms_version, $cms_frameworks)) {
			$content_saved = preg_replace("/((#\s*)?RewriteRule \.\* index.php\r?\n)/",
				"# Web Optimizer path\nRewriteCond %{REQUEST_FILENAME} ^(".
				$this->view->paths['relative']['current_directory'] .
				")\n# Web Optimizer path end\n$1", $content_saved);
		}
/* try to remove current .htaccess with a new one */
		if (!@is_writable($this->htaccess)) {
			@unlink($this->htaccess);
		}
/* check if .htaccess isn't required */
		$not_apache = 0;
		if (!empty($_SERVER['SERVER_SOFTWARE'])) {
			$not_apache = strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') + strpos($_SERVER['SERVER_SOFTWARE'], 'IIS');
		}
		if (!$not_apache) {
			$this->write_file($this->htaccess, $content . "\n" . $content_saved . $content2, 1);
		}
	}

	/**
	* Final stage
	* 
	**/	
	function install_install ($skip = false) {
		$auto_rewrite = 0;
/* sve initial options */
		$this->compress_options['document_root'] = empty($this->compress_options['document_root']) ?
			$this->view->paths['full']['document_root'] : $this->compress_options['document_root'];
		$this->compress_options['website_root'] = empty($this->compress_options['website_root']) ?
			$this->view->paths['absolute']['document_root'] : $this->compress_options['website_root'];
		$this->compress_options['css_cachedir'] = empty($this->compress_options['css_cachedir']) ?
			$this->basepath . 'cache/' : $this->compress_options['css_cachedir'];
		$this->compress_options['javascript_cachedir'] = empty($this->compress_options['javascript_cachedir']) ?
			$this->basepath . 'cache/' : $this->compress_options['javascript_cachedir'];
		$this->compress_options['html_cachedir'] = empty($this->compress_options['html_cachedir']) ?
			$this->basepath . 'cache/' : $this->compress_options['html_cachedir'];
		$this->compress_options['host'] = empty($this->compress_options['host']) ?
			(empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST']) :
				$this->compress_options['host'];
		$this->compress_options['minify']['css_host'] = empty($this->compress_options['minify']['css_host']) ?
			(empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST']) :
				$this->compress_options['minify']['css_host'];
		$this->compress_options['minify']['javascript_host'] = empty($this->compress_options['minify']['javascript_host']) ?
			(empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST']) :
				$this->compress_options['minify']['javascript_host'];
		foreach (array(
			'document_root',
			'website_root',
			'css_cachedir',
			'javascript_cachedir',
			'html_cachedir',
			'host') as $val) {
				$this->save_option("['" . $val . "']",
					$this->compress_options[$val]);
		}
		foreach (array(
			'css_host',
			'javascript_host') as $val) {
				$this->save_option("['minify']['" . $val . "']",
					$this->compress_options['minify'][$val]);
		}
/* create backup for options file */
		if (!@is_file($this->basepath . '.config.webo.php')) {
			@copy($this->basepath . $this->options_file, $this->basepath . '.config.webo.php');
		}
/* clean previous changes */
		$this->install_uninstall(1);
/* define CMS */
		$this->cms_version = $this->system_info($this->compress_options['website_root']);
/* copy some files */
		@copy($this->basepath . 'images/web.optimizer.stamp.png',
			$this->compress_options['css_cachedir'] . 'web.optimizer.stamp.png');
		@copy($this->basepath . 'libs/js/wo.cookie.php',
			$this->compress_options['javascript_cachedir'] . 'wo.cookie.php');
		@copy($this->basepath . 'libs/js/yass.loader.js',
			$this->compress_options['javascript_cachedir'] . 'yass.loader.js');
		@copy($this->basepath . 'libs/php/wo.static.php',
			$this->compress_options['css_cachedir'] . 'wo.static.php');
		@copy($this->basepath . 'libs/php/0.gif',
			$this->compress_options['css_cachedir'] . '0.gif');
/* dirty hack for PHP-Nuke */
		if ($this->cms_version == 'PHP-Nuke') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'mainfile.php';
			$footer = $this->view->paths['absolute']['document_root'] . 'footer.php';
			$mainfile_content = @file_get_contents($mainfile);
			$footer_content = @file_get_contents($footer);
			if (!empty($mainfile_content) && !empty($footer_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* update main PHP-Nuke file */
				$return1 = $this->write_file($mainfile,
					preg_replace("/(if\s+\(!ini_get\('register_globals)/",
					'require(\'' . $this->basepath .
					'web.optimizer.php\');' . "\n$1",
					preg_replace("/require\('[^\']+\/web.optimizer.php'\);\r?\n?/", "", $mainfile_content)), 1);
/* create backup */
				@copy($footer, $footer . '.backup');
/* update footer */
				$return2 = $this->write_file($footer, preg_replace("/global /", 'global \$web_optimizer,', preg_replace("/(\s*ob_end_flush\(\);)/", '\$web_optimizer->finish();' . "\n$1", preg_replace("/(\\\$web_optimizer,|\\\$web_optimizer->finish\(\);\r?\n?)/", "", $footer_content))), 1);
				if (!empty($return1) && !empty($return2)) {
					$auto_rewrite = 1;
				}
			}
/* another dirty hack for phpBB */
		} elseif ($this->cms_version == 'phpBB') {
			$mainfile = $this->view->paths['absolute']['document_root'] .
				'includes/functions.php';
			$mainfile_content = @file_get_contents($mainfile);
			if (!empty($mainfile_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* remove any old strings regarding Web Optimizer */
				$mainfile_content =
					preg_replace("/\\\$web_optimizer->finish\(\);\r?\n?/", "",
					preg_replace("/require\('[^\']+\/web.optimizer.php'\);\r?\n?/", "",
					$mainfile_content));
/* add class declaration */
				$mainfile_content =
					preg_replace("/(function\s*page_footer\s*\([^\)]+\)[\r\n\s]*\{)/",
					"$1\n" . 'require(\'' . $this->basepath . 'web.optimizer.php\');',
					$mainfile_content);
/* add finish */
				$mainfile_content =
					preg_replace("/(\\\$template->display\(['\"]body['\"]\);\r?\n?)/",
					"$1" . '\$web_optimizer->finish();' . "\n",
					$mainfile_content);
				$return = $this->write_file($mainfile, $mainfile_content, 1);
				if (!empty($return)) {
					$auto_rewrite = 1;
				}
			}
/* one more dirty hack for ipb */
		} elseif ($this->cms_version == 'Invision Power Board') {
			$mainfile = $this->view->paths['absolute']['document_root'] .
				'sources/classes/class_display.php';
			$mainfile_content = @file_get_contents($mainfile);
			if (!empty($mainfile_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* add class declaration */
				$mainfile_content =
					preg_replace("/(print \\\$this->ipsclass->skin\['_wrapper'\];\r?\n?)/",
					'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n$1",
					$mainfile_content);
/* add finish */
				$mainfile_content =
					preg_replace("/(print \\\$this->ipsclass->skin\['_wrapper'\];\r?\n?)/", "$1" . '\$web_optimizer->finish();' . "\n", $mainfile_content);
				$return = $this->write_file($mainfile, $mainfile_content, 1);
				if (!empty($return)) {
					$auto_rewrite = 1;
				}
			}
/* and for Bitrix */
		} elseif ($this->cms_version == 'Bitrix') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'bitrix/header.php';
			$footer = $this->view->paths['absolute']['document_root'] . 'bitrix/modules/main/include/epilog_after.php';
			$mainfile_content = @file_get_contents($mainfile);
			$footer_content = @file_get_contents($footer);
			if (!empty($mainfile_content) && !empty($footer_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* update header */
				$return1 = $this->write_file($mainfile, preg_replace("/<\?/", '<? require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $mainfile_content), 1);
/* create backup */
				@copy($footer, $footer . '.backup');
/* update footer */
				$return2 = $this->write_file($footer, preg_replace("/(echo\s*\\\$r;\r?\n?)/", "$1\n" . '\$web_optimizer->finish();' . "\n", preg_replace("/\\\$web_optimizer->finish\(\);\r?\n?/", "", $footer_content)), 1);
				if (!empty($return1) && !empty($return2)) {
					$auto_rewrite = 1;
				}
			}
/* and for Open Slaed */
		} elseif (substr($this->cms_version, 0, 10) == 'Open Slaed') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'index.php';
			$footer = $this->view->paths['absolute']['document_root'] . 'function/function.php';
			$mainfile_content = @file_get_contents($mainfile);
			$footer_content = @file_get_contents($footer);
			if (!empty($mainfile_content) && !empty($footer_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* update mainfile */
				$return1 = $this->write_file($mainfile, preg_replace("/(<\?(php)?)/", "$1" . ' require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $mainfile_content), 1);
/* create backup */
				@copy($footer, $footer . '.backup');
				$footer_content = preg_replace('!(readfile\(\$cacheurl\);)!', "$1\n" . 'global $web_optimizer;$web_optimizer->finish();', $footer_content);
				$footer_content = preg_replace('!(ob_end_flush\(\);)!', 'global $web_optimizer;$web_optimizer->finish();' . "\n$1", $footer_content);
/* update footer */
				$return2 = $this->write_file($footer, $footer_content, 1);
				if (!empty($return1) && !empty($return2)) {
					$auto_rewrite = 1;
				}
			}
/* and for 4images */
		} elseif ($this->cms_version == '4images') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'includes/page_header.php';
			$footer = $this->view->paths['absolute']['document_root'] . 'includes/page_footer.php';
			$mainfile_content = @file_get_contents($mainfile);
			$footer_content = @file_get_contents($footer);
			if (!empty($mainfile_content) && !empty($footer_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* update mainfile */
				$return1 = $this->write_file($mainfile, preg_replace("/(<\?(php)?)/", "$1" . ' require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $mainfile_content), 1);
/* create backup */
				@copy($footer, $footer . '.backup');
				$footer_content = preg_replace('!(exit;)!', 'global $web_optimizer;$web_optimizer->finish();' . "$1", $footer_content);
/* update footer */
				$return2 = $this->write_file($footer, $footer_content, 1);
				if (!empty($return1) && !empty($return2)) {
					$auto_rewrite = 1;
				}
			}
/* and for VaM Shop, osCommerce */
		} elseif ($this->cms_version == 'VaM Shop' || $this->cms_version == 'osCommerce') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'includes/application_top.php';
			$footer = $this->view->paths['absolute']['document_root'] . 'includes/application_bottom.php';
			$mainfile_content = @file_get_contents($mainfile);
			$footer_content = @file_get_contents($footer);
			if (!empty($mainfile_content) && !empty($footer_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* update mainfile */
				$return1 = $this->write_file($mainfile, preg_replace("/(<\?(php)?)/", "$1" . ' require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $mainfile_content), 1);
/* create backup */
				@copy($footer, $footer . '.backup');
				$footer_content = preg_replace('!(\?>)!s', '$web_optimizer->finish();' . "$1", $footer_content);
/* update footer */
				$return2 = $this->write_file($footer, $footer_content, 1);
				if (!empty($return1) && !empty($return2)) {
					$auto_rewrite = 1;
				}
			}
/* and for Social Engine */
		} elseif (substr($this->cms_version, 0, 13) == 'Social Engine') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'header.php';
			$footer = $this->view->paths['absolute']['document_root'] . 'footer.php';
			$mainfile_content = @file_get_contents($mainfile);
			$footer_content = @file_get_contents($footer);
			if (!empty($mainfile_content) && !empty($footer_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
/* update mainfile */
				$return1 = $this->write_file($mainfile, preg_replace("/(<\?(php)?)/", "$1" . ' require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $mainfile_content), 1);
/* create backup */
				@copy($footer, $footer . '.backup');
				$footer_content = preg_replace('!(exit\(\);\r?\n\?>)!s', '$web_optimizer->finish();' . "$1", $footer_content);
/* update footer */
				$return2 = $this->write_file($footer, $footer_content, 1);
				if (!empty($return1) && !empty($return2)) {
					$auto_rewrite = 1;
				}
			}
/* and for X-Cart */
		} elseif ($this->cms_version == 'X-Cart') {
			$mainfile = $this->view->paths['absolute']['document_root'] . 'include/func/func.core.php';
			$mainfile_content = @file_get_contents($mainfile);
			if (!empty($mainfile_content)) {
/* create backup */
				@copy($mainfile, $mainfile . '.backup');
				$mainfile_content = preg_replace("!(\\\$templater->assign\(\"is_https_zone\",\s\\\$HTTPS\);\r?\n?)!", "$1" . ' require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $mainfile_content);
				$mainfile_content = preg_replace("!(\\\$templater->display\(\\\$tpl\);\r?\n?)!s", "$1" . '$web_optimizer->finish();' . "\n", $mainfile_content);
/* update mainfile */
				$return = $this->write_file($mainfile, $mainfile_content, 1);
				if (!empty($return)) {
					$auto_rewrite = 1;
				}
			}
		} else {
			$index = $this->view->paths['absolute']['document_root'] . 'index.php';
			if (substr($this->cms_version, 0, 9) == 'vBulletin') {
				$index = $this->view->paths['absolute']['document_root'] . 'includes/functions.php';
			} elseif ($this->cms_version == 'NetCat') {
				$index = $this->view->paths['absolute']['document_root'] . 'netcat/require/e404.php';
			} elseif ($this->cms_version == 'PHP Fusion') {
				$index = $this->view->paths['absolute']['document_root'] . 'themes/templates/footer.php';
			}
			$fp = @fopen($index, "r");
			if ($fp) {
				$content_saved = '';
				while ($index_string = fgets($fp)) {
					$content_saved .= preg_replace("/(require\('[^\']+\/web.optimizer.php'\)|\\\$web_optimizer->finish\(\));\r?\n?/i", "", $index_string);
				}
				@fclose($fp);
/* fix for Joomla 1.0 */
				if (preg_match("/Joomla! 1\.0/", $this->cms_version)) {
					$content_saved = preg_replace("/(initGzip\(\);\r?\n)/i", 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n$1", $content_saved);
/* fix for Joomla 1.5+ */
				} elseif (preg_match("/Joomla! 1\.[56789]/", $this->cms_version)) {
					$content_saved = preg_replace("/(\\\$mainframe\s*=&\s*JFactory::getApplication\(['\"]site['\"]\);\r?\n)/i",  "$1" . 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $content_saved);
/* fix for Joostina */
				} elseif (preg_match("/Joostina/", $this->cms_version)) {
					$content_saved = preg_replace("/(require_once\s*\([^\)]+frontend\.php)/i", 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n$1", $content_saved);
/* fix for vBulletin */
				} elseif (substr($this->cms_version, 0, 9) == 'vBulletin') {
					$content_saved = preg_replace("/(\(\\\$hook\s*=\s*vBulletinHook::fetch_hook\('global_complete'\)\))/i", 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n$1", $content_saved);
/* fix for CMS Made Simple */							
				} elseif (substr($this->cms_version, 0, 15) == 'CMS Made Simple') {
					$content_saved = preg_replace("/(echo\s*\\\$html;)/", 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n$1", $content_saved);
/* fix for UMI.CMS */							
				} elseif (substr($this->cms_version, 0, 7) == 'UMI.CMS') {
					$content_saved = preg_replace("/(sha1.*)\r?\n([\s\t]*echo\s*\\\$res;)/", "$1\n" . 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n$2", $content_saved);
/* fix for PHP Fusion */							
				} elseif ($this->cms_version == 'PHP Fusion') {
					$content_saved = preg_replace("/(require_once INCLUDES.\"footer_includes.php\";\r?\n)/", "$1" . 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $content_saved);
				} elseif (substr($content_saved, 0, 2) == '<?') {
/* add require block */
					$content_saved = preg_replace("/^<\?(php)?( |\r?\n)/i", '<?$1$2require(\'' . $this->basepath . 'web.optimizer.php\');' . "\n", $content_saved);
				} else {
					$content_saved = "<?php require('" . $this->basepath . "web.optimizer.php'); ?>" . $content_saved;
				}
/* fix for DataLife Engine */
				if (substr($this->cms_version, 0, 15) == 'DataLife Engine') {
					$content_saved = preg_replace("/(GzipOut\s*\(\);)/", '$web_optimizer->finish();' . "\n$1", $content_saved);
/* fix for vBulletin */
				} elseif (substr($this->cms_version, 0, 9) == 'vBulletin') {
					$content_saved = preg_replace("/(flush\s*\(\);[\r\n\s\t]*\})/", "$1\n" . '$web_optimizer->finish();', $content_saved);
/* fix for Joomla! 1.0 */
				} elseif (preg_match("/Joomla! 1\.0/", $this->cms_version)) {
					$content_saved = preg_replace("/(doGzip\(\);\r?\n)/i", '$web_optimizer->finish();' . "\n$1" , $content_saved);
/* fix for CMS Made Simple */
				} elseif (substr($this->cms_version, 0, 15) == 'CMS Made Simple') {
					$content_saved = preg_replace("/(echo\s*\\\$html;)/", "$1\n" . '$web_optimizer->finish();', $content_saved);
/* fix for UMI.CMS */
				} elseif (substr($this->cms_version, 0, 7) == 'UMI.CMS') {
					$content_saved = preg_replace("/(web\.optimizer.*\r?\n[\s\t]*echo\s*\\\$res;)/", "$1\n" . '$web_optimizer->finish();', $content_saved);
/* fix for MaxDev */
				} elseif (substr($this->cms_version, 0, 6) == 'MaxDev') {
					$content_saved = preg_replace("/(\\\$output->PrintPage\(\);)/", "$1" . '$web_optimizer->finish();', $content_saved);
					$content_saved = preg_replace("/(\}[\r\n\t\s]+)(exit;)/", "$1" . '$web_optimizer->finish();' . "$2", $content_saved);
/* fix for PHP Fusion */							
				} elseif ($this->cms_version == 'PHP Fusion') {
					$content_saved = preg_replace("/(echo handle_output\(\\\$output\);\r?\n)/", "$1" . '$web_optimizer->finish();', $content_saved);
				} elseif (preg_match("/\?>[\r\n\s]*$/", $content_saved)) {
/* small fix for Joostina */
					if (substr($this->cms_version, 0, 8) == 'Joostina') {
						$content_saved = preg_replace("/(exit\s*\(\);\r?\n\?>)[\r\n\s]*$/", '$web_optimizer->finish();' . "\n$1", $content_saved);
					} else {
/* add finish block */
						$content_saved = preg_replace("/ ?\?>[\r\n\s]*$/", '\$web_optimizer->finish(); ?>', $content_saved);
					}
				} else {
/* fix for Drupal / Joomla / others on not-closed ?> */
					$content_saved .= '$web_optimizer->finish();';
				}
/* restrict changes in binary files, i.e. Zend Optimized ones */
				if (substr($content_saved, 0, 12) != '<?php @Zend;') {
/* create backup */
					@copy($index, $index . '.backup');
					$return = $this->write_file($index, $content_saved, 1);
					if (!empty($return)) {
						$auto_rewrite = 1;
					}
				} else {
					$auto_rewrite = 3;
				}
/* additional change of cache plugins */
				if (preg_match("/Joomla! 1\.[56789]/", $this->cms_version)) {
/* System-Cache plugin */
					$cache_file = $this->view->paths['absolute']['document_root'] . 'plugins/system/cache.php';
					if (@is_file($cache_file)) {
						@copy($cache_file, $cache_file . '.backup');
						$content = preg_replace("/(\\\$mainframe->close)/", 'global \$web_optimizer;\$web_optimizer->finish();' . "$1", @file_get_contents($cache_file));
						$this->write_file($cache_file, $content);
					}
/* JRE component */
					$cache_file = $this->view->paths['absolute']['document_root'] . 'administrator/components/com_jrecache/includes/cache_handler.php';
					if (@is_file($cache_file)) {
						@copy($cache_file, $cache_file . '.backup');
						$content = preg_replace("/(echo \\\$output;)/", 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "$1" . '\$web_optimizer->finish();', @file_get_contents($cache_file));
						$this->write_file($cache_file, $content);
					}
				}
				if (preg_match("/Joomla! 1\.0/", $this->cms_version)) {
/* PageCache component */
					$cache_file = $this->view->paths['absolute']['document_root'] . 'components/com_pagecache/pagecache.class.php';
					if (@is_file($cache_file)) {
						@copy($cache_file, $cache_file . '.backup');
						$content = preg_replace("/(echo \\\$data;)/", "$1" . 'global \$web_optimizer;\$web_optimizer->finish();', @file_get_contents($cache_file));
						$this->write_file($cache_file, $content);
					}
/* System-Cache mambot */
					$cache_file = $this->view->paths['absolute']['document_root'] . 'mambots/system/cache.php';
					if (@is_file($cache_file)) {
						@copy($cache_file, $cache_file . '.backup');
						$content = preg_replace("/(echo \\\$content;)/", 'require(\'' . $this->basepath . 'web.optimizer.php\');' . "$1" . '\$web_optimizer->finish();', @file_get_contents($cache_file));
						$this->write_file($cache_file, $content);
					}
				}
				if (substr($this->cms_version, 0, 5) == 'XOOPS') {
					$cache_file = $this->view->paths['absolute']['document_root'] . 'class/theme.php';
					if (@is_file($cache_file)) {
						@copy($cache_file, $cache_file . '.backup');
						$content = preg_replace("/(\\\$this->render\([^\(]+\);)/", "$1" . 'global \$web_optimizer;\$web_optimizer->finish();', @file_get_contents($cache_file));
						$this->write_file($cache_file, $content);
					}
				}
			}
		}

/* execute plugin-specific logic */
		$plugins = explode(" ", $this->compress_options['plugins']);
		if (is_array($plugins)) {
			foreach ($plugins as $plugin) {
				$plugin_file = $this->basepath . 'plugins/' . $plugin . '.php';
				if (@is_file($plugin_file)) {
					include($plugin_file);
					$web_optimizer_plugin->onInstall($this->view->paths['absolute']['document_root']);
				}
			}
		}
/* write .htaccess */
		$options = $this->get_options();
		$this->input = array();
		foreach ($options as $group) {
			if (is_array($group)) {
				foreach ($group as $key => $option) {
					if (is_array($option)) {
						$this->input['wss_' . $key] = $option['value'];
					}
				}
			}
		}
		$this->set_options();
		$this->write_htaccess();
		if (!$skip) {
			$this->install_system(2 - $auto_rewrite);
		}
	}

	/**
	* Get all loaded Apache modules and do some magic
	*
	**/
	function get_modules () {
/* check for Apache installation, only if curl is disabled */
		if (function_exists('apache_get_modules')) {
			$apache_modules = apache_get_modules();
		} else {
/* if PHP installed as CGI module -- we don't need .htaccess */	
			$apache_modules = array();
		}
		$this->apache_modules = array();
		if (in_array('mod_expires', $apache_modules)) {
			$this->apache_modules[] = 'mod_expires';
		}
		if (in_array('mod_gzip', $apache_modules)) {
			$this->apache_modules[] = 'mod_gzip';
		}
		if (in_array('mod_deflate', $apache_modules) && in_array('mod_filter', $apache_modules)) {
			$this->apache_modules[] = 'mod_deflate';
		}
		if (in_array('mod_headers', $apache_modules)) {
			$this->apache_modules[] = 'mod_headers';
		}
		if (in_array('mod_setenvif', $apache_modules)) {
			$this->apache_modules[] = 'mod_setenvif';
		}
		if (in_array('mod_mime', $apache_modules)) {
			$this->apache_modules[] = 'mod_mime';
		}
		if (in_array('mod_rewrite', $apache_modules)) {
			$this->apache_modules[] = 'mod_rewrite';
		}
		$cachedir = empty($this->compress_options['javascript_cachedir']) ?
			$this->view->paths['full']['current_directory'] . 'cache/' :
			$this->compress_options['javascript_cachedir'];
		$root = empty($this->compress_options['document_root']) ?
			$this->view->paths['full']['document_root'] :
			$this->compress_options['document_root'];
/* don't check modules more than 9 seconds */
		$timer = time();
/* detect if hosting is compatible with SynLinks rule (included in core) */
		if (@function_exists('curl_init') &&
			$this->check_apache_module('Options +FollowSymLinks', $root, $cachedir, 'mod_symlinks')) {
				$this->apache_modules[] = 'mod_symlinks';
		}
/* download restricted file, if sizes are equal =? file isn't restricted => htaccess won't work */
/* can't get this code (htaccess detection) on both Denwer with curl, non-curl,
   CGI with curl and Apache with curl environments. See
   http://code.google.com/p/web-optimizator/issues/detail?id=369
		$this->view->download(str_replace($root, "http://" . $_SERVER['HTTP_HOST'] . "/", $this->basepath) . 'libs/php/css.sprites.php', $cachedir . 'htaccess.test');
		if (@is_file($cachedir . 'htaccess.test') && !@filesize($cachedir . 'htaccess.test')) {
			$this->apache_modules = array(); */
		if (count($this->apache_modules) < 2) {
			$modules = array(
				'mod_deflate' => 'AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript text/x-js text/ecmascript application/ecmascript text/vbscript text/fluffscript',
				'mod_headers' => 'Header append Cache-Control public',
				'mod_expires' => 'ExpiresActive On',
				'mod_setenvif' => 'BrowserMatch SV1; !no_gzip',
				'mod_mime' => 'AddEncoding gzip .gz',
				'mod_rewrite' => "RewriteEngine On
RewriteRule wo\.cookie\.php$ " .
str_replace($this->compress_options['document_root'], "/", str_replace("\\", "/", dirname(__FILE__))) .
"/../libs/js/yass.loader.js"
			);
			if (@function_exists('curl_init')) {
/* detect modules one by one, it can be CGI environment */
				foreach ($modules as $key => $value) {
					if (!in_array('mod_deflate', $this->apache_modules) || $key != 'mod_gzip') {
						if ($this->check_apache_module($value, $root, $cachedir, $key)) {
							$this->apache_modules[] = $key;
						}
					}
				}
/* just fill all Apache modules - we can't check their existence */
			} else {
				foreach ($modules as $key => $value) {
					$this->apache_modules[] = $key;
				}
			}
		}
		@unlink($cachedir . 'htaccess.test');
	}

	/**
	* Checks exitence of current Apache module
	*
	**/
	function check_apache_module ($rule, $root, $cachedir, $module) {
		$gzip = strpos($rule, 'DEFLATE') || strpos($rule, 'mod_gzip');
		if ($module == 'mod_symlinks') {
			$testfile = 'libs/js/wo.cookie.php';
			$curlfile = 'libs/js/wo.cookie.php';
			$size = 131;
		} else {
			$testfile = $curlfile = 'libs/js/yass.loader.js';
			$size = @filesize($this->basepath . $testfile);
			if ($module == 'mod_rewrite') {
				$curlfile = 'libs/js/wo.cookie.php';
			}
		}
		$return = false;
		$this->write_file($this->basepath . 'libs/js/.htaccess', $rule);
		$recursive = 0;
		while (!($filesize = @filesize($cachedir . 'module.test')) &&
			$recursive++ < 10) {
				$curl = $this->view->download(str_replace(realpath($root),
					'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['HTTP_HOST'],
					realpath($this->basepath)) . '/' .
					$curlfile, $cachedir . 'module.test', 1, 0,
					$this->compress_options['external_scripts']['user'],
					$this->compress_options['external_scripts']['pass']);
				if (round($curl[1]) == 301) {
/* switch from PHP for JS, if recursion w/o result */
					if ($module == 'mod_symlinks') {
						$testfile = $curlfile = 'libs/js/yass.loader.js';
/* or just disable module */
					} else {
						$recursive = 10;
					}
				}
		}
/* it it's possible to get file => module works */
		if ($filesize == $size) {
			$return = true;
/* fix for LiteSpeed bug on .htaccess rights + mod_rewrite
   + one more LiteSpeed bug with delay with .htaccess application
 */
		} elseif (($curl[1] == 400 || $filesize == 131) &&
			$module == 'mod_rewrite' &&
			!empty($_SERVER["SERVER_SOFTWARE"]) &&
			$_SERVER["SERVER_SOFTWARE"] == 'LiteSpeed') {
			$return = true;
		}
/* check for gzip / deflate support */
		if ($gzip && !$curl[0]) {
			$return = false;
		}
		@unlink($cachedir . 'module.test');
		@unlink($this->basepath . 'libs/js/.htaccess');
		return $return;
	}

	/**
	* Consequenty emulate different stages of optimization process
	* To prevent initial delay for optimized website and PHP timeout
	*
	**/
	function chained_load ($index = false) {
/* force cache reload via index.php */
		if ($index) {
			$this->write_progress(8);
			$test_file = $this->compress_options['html_cachedir'] . 'optimizing.php';
/* load home page */
			$this->view->download('http://' . $_SERVER['HTTP_HOST'] . $index . '?web_optimizer_disabled=1', $test_file);
			$contents = @file_get_contents($test_file);
			$this->write_file($test_file, "<?php require('" .
				$this->basepath . "web.optimizer.php'); ?>" .
				preg_replace("/<\?xml[^>]+\?>/", "", $contents) .
				'<?php $web_optimizer->finish(); ?>', 1);
			$this->write_progress(9);
/* then iterate through its local copy */
			$this->view->download('http://' . $_SERVER['HTTP_HOST'] . '/' .
				str_replace($this->compress_options['document_root'], '',
					$this->compress_options['html_cachedir']) .
				'optimizing.php?web_optimizer_stage=10&web_optimizer_debug=1',
				$this->compress_options['html_cachedir'] . 'chained.load', 25);
			@unlink($this->compress_options['javascript_cachedir'] . 'progress.html');
			@unlink($this->compress_options['html_cachedir'] . 'chained.load');
			@unlink($this->compress_options['html_cachedir'] . 'optimizing.php');
/* or via cached HTML */
		} else {
			$test_file = $this->basepath . 'cache/optimizing.php';
			$this->write_progress(8);
/* try to download main file */
			$this->view->download('http://' . $_SERVER['HTTP_HOST'] . '/?web_optimizer_disabled=1', $test_file);
			$this->write_progress(9);
			$contents = @file_get_contents($test_file);
			if (!empty($contents)) {
				$return = $this->write_file($test_file, "<?php require('" .
						$this->basepath .
					"web.optimizer.php'); ?>" .
						preg_replace("/<\?xml[^>]+\?>/", "", $contents) .
					'<?php $web_optimizer->finish(); ?>', 1);
				if (!empty($return)) {
					$this->write_progress(10);
					$this->input['user']['auto_rewrite'] =
						empty($this->input['user']['auto_rewrite']) ? array() :
							$this->input['user']['auto_rewrite'];
					$this->input['user']['auto_rewrite']['enabled'] =
						empty($this->input['user']['auto_rewrite']['enabled']) ? 0 : 1;
					header('Location: cache/optimizing.php?web_optimizer_stage=10&password=' .
							$this->input['user']['password'] .
						'&username=' .
							$this->input['user']['username'] .
						'&auto_rewrite=' .
							$this->input['user']['auto_rewrite']['enabled'] .
						'&cache_version=' .
							$this->cache_version .
						'&web_optimizer_debug=1');
					die();
				}
			}
		}
	}

	/**
	* Saves an admin option
	* 
	**/
	function save_option ($option_name, $option_value) {
/* make password salt safe */
		if ($option_name == "['htpasswd']") {
			$option_value = str_replace('$', '#', $option_value);
/* make paths uniform (Windows-Linux). Thx to dmiFedorenko */
		} else {
			$option_value = str_replace('\\\\\\', '', str_replace('//', '/', str_replace('\\', '/', $option_value)));
		}
/* See if file exists */
		$option_file = $this->basepath . $this->options_file;
		if (!@is_file($option_file)) {
			@copy($this->basepath . 'config.safe.php', $option_file);
			@chmod($option_file, octdec("0644"));
		}
		$content = @file_get_contents($option_file);
		if ($content) {
			$content = preg_replace("@(" . preg_quote($option_name) . ")\s*=\s*\"(.*?)\"@is","$1 = \"" . $option_value . "\"", $content);
			if (!$this->write_file($option_file, $content, 1)) {
				$this->error[0] = 1;
			}
		} else {
			$this->error[0] = 1;
		}
	}

	/**
	* Check password
	* 
	**/		
	function check_password ($rewrite = 0) {
/* If passing a username and pass, don't md5 encode */
		if ((!empty($this->input['wss_password']) &&
			($this->compress_options['password'] ==
			md5($this->input['wss_password'])) ||
			(!empty($this->input['wss__password']) &&
			$this->compress_options['password'] ==
			$this->input['wss__password'])) ||
/* if we use .htaccess */
			(isset($_SERVER['PHP_AUTH_USER']) &&
			$this->compress_options['username'] ==
			$_SERVER['PHP_AUTH_USER'] &&
			$this->compress_options['htaccess']['access'])) {
				$this->access = 1;
		} else {
				$this->access = 0;
		}
	}

	/**
	* Protects Web Optimizer folder via htpasswd
	* 
	**/
	function protect_installation() {
		$htaccess = $this->basepath . '.htaccess';
		$htaccess_content = @file_get_contents($htaccess);
/* clean current content */
		$htaccess_content = preg_replace("!\r?\n# Web Optimizer protection(\r?\n.*)*Web Optimizer protection end!", "", $htaccess_content);
		$htaccess_content .= '
# Web Optimizer protection';
		$this->error = $this->error ? $this->error : array();
		if (!empty($this->compress_options['htaccess']['access'])) {
			$htpasswd = $this->basepath . '.htpasswd';
			$this->write_file($htpasswd,
				$this->compress_options['username'] .
				str_replace("#", "$", $this->compress_options['htpasswd']));
			if (@is_file($htpasswd)) {
/* add secure protection via htpasswd */
				$htaccess_content .= '
AuthType Basic
AuthName "WEBO Site SpeedUp Installation"
AuthUserFile ' . $htpasswd . '
require valid-user';
			} else {
				$this->error[8] = 1;
			}
		}
		$htaccess_content .= '
<Files .htpasswd>
	Deny from all
</Files>
# Web Optimizer protection end';
/* create backup */
		@copy($htaccess, $htaccess . '.backup');
		if (!$this->write_file($htaccess, $htaccess_content) &&
			!empty($this->compress_options['htaccess']['access'])) {
				$this->error[9] = 1;
		}
	}
	/**
	* Creates password hash for htpasswd file
	* thx to mikey_nich (at) hotmail . com
	* 
	**/
	function encrypt_password($plainpasswd) {
		$salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
		$len = strlen($plainpasswd);
		$text = $plainpasswd . '$apr1$' . $salt;
		$bin = pack("H32", md5($plainpasswd . $salt . $plainpasswd));
		$tmp = '';
		for($i = $len; $i > 0; $i -= 16) {
			$text .= substr($bin, 0, min(16, $i));
		}
		for($i = $len; $i > 0; $i >>= 1) {
			$text .= ($i & 1) ? chr(0) : $plainpasswd{0};
		}
		$bin = pack("H32", md5($text));
		for($i = 0; $i < 1000; $i++) {
			$new = ($i & 1) ? $plainpasswd : $bin;
			if ($i % 3) {
				$new .= $salt;
			}
			if ($i % 7) {
				$new .= $plainpasswd;
			}
			$new .= ($i & 1) ? $bin : $plainpasswd;
			$bin = pack("H32", md5($new));
		}
		for ($i = 0; $i < 5; $i++) {
			$k = $i + 6;
			$j = $i + 12;
			if ($j == 16) {
				$j = 5;
			}
			$tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
		}
		$tmp = chr(0).chr(0).$bin[11].$tmp;
		$tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
		"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
		"./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
		return "$" . "apr1" . "$" . $salt . "$" . $tmp;
	}

	/**
	* Get current PHP system info
	* 
	**/
	function system_info($root) {
		if (!empty($this->cms_version)) {
			return $this->cms_version;
		}
/* Wordpress */
		if (@is_file($root . 'wp-includes/version.php')) {
			$wp_version = '1.0.0';
			require($root . 'wp-includes/version.php');
			return 'Wordpress ' . $wp_version;
		} elseif (@is_file($root . 'modules/system/system.info')) {
/* Drupal */
			$drupal_version = '1.0.0';
			$fp  = @fopen($root . 'modules/system/system.info', "r");
			if ($fp) {
				while ($str = fgets($fp)) {
					if (strstr($str, 'version = "')) {
						$drupal_version = preg_replace('/version\s+=\s+"([0-9.]*?)"/', "$1", $str);
					}
				}
			}
			return 'Drupal ' . trim($drupal_version);
/* Joomla 1.5 */
		} elseif (@is_file($root . 'libraries/joomla/version.php')) {
			return 'Joomla! 1.5';
		} elseif (@is_dir($root . 'includes')) {
/* for PHP-Nuke 8.0 */
			if (@is_file($root . 'modules/Journal/copyright.php') && @is_file($root . 'footer.php') && @is_file($root . 'mainfile.php')) {
				return 'PHP-Nuke';
/* vBulletin */
			} elseif (@is_file($root . 'includes/class_core.php')) {
				require($root . 'includes/class_core.php');
				$vbulletin_version = '';
				if (defined('FILE_VERSION')) {
					$vbulletin_version = ' ' . FILE_VERSION;
				}
				return 'vBulletin' . $vbulletin_version;
/* phpBB (3.0) */
			} elseif (@is_file($root . 'includes/functions_privmsgs.php')) {
				return 'phpBB';
/* osCommerce (2.2) */
			} elseif (@is_file($root . 'includes/tld.txt')) {
				return 'osCommerce';
/* Joomla 1.0, Joostina */
			} elseif (@is_file($root . 'includes/version.php')) {
				define('_VALID_MOS', 1);
				$joomla_version = '1.0';
				$joomla_title = 'Joomla!';
				if (substr(phpversion(), 0, 1) == 4) {
					if (!class_exists('joomlaVersion')) {
						require($root . 'includes/version.php');
					} else {
						$_VERSION = new joomlaVersion();
					}
				} else {
					if (!class_exists('joomlaVersion', false)) {
						require($root . 'includes/version.php');
					} else {
						$_VERSION = new joomlaVersion();
					}
				}
				$joomla_version = empty($_VERSION->CMS_ver) ? ($_VERSION->RELEASE . '.' . $_VERSION->DEV_LEVEL) : $_VERSION->CMS_ver;
				$joomla_title = empty($_VERSION->CMS) ? $_VERSION->PRODUCT : $_VERSION->CMS;
				return $joomla_title . ' ' . $joomla_version;
/* 4images */
			} elseif (@is_file($root . 'postcards.php')) {
				return '4images';
/* VaM Shop */
			} elseif (@is_file($root . 'includes/application_top.php')) {
				return 'VaM Shop';
/* MaxDev Pro */
			} elseif (@is_file($root . 'includes/mdHTML.php')) {
				return 'MaxDev Pro';
/* PHP Fusion */
			} elseif (@is_dir($root . 'infusions')) {
				return 'PHP Fusion';
/* Magento */
			} elseif (@is_file($root . '/app/Mage.php')) {
				return 'Magento';
			}
/* Typo 3 */
		} elseif (@is_dir($root . 'typo3conf')) {
			$TYPO3_CONF_VARS = array('SYS' => array('compat_version' => '4.2'));
			if (@is_file($root . 'typo3conf/localconf.php')) {
				require($root . 'typo3conf/localconf.php');
			}
			return 'Typo3 ' . $TYPO3_CONF_VARS['SYS']['compat_version'];
/* Simpla */
		} elseif (@is_file($root . 'Storefront.class.php')) {
			return 'Simpla';
/* Etomate 1.0, MODx */
		} elseif (@is_file($root . 'manager/includes/version.inc.php')) {
			require($root . 'manager/includes/version.inc.php');
			if (empty($full_appname)) {
				return 'Etomite ' . $release;
			} else {
/* MODx case */
				return $full_appname;
			}
/* LiveStreet */
		} elseif (@is_file($root . 'classes/engine/Router.class.php') || @is_file($root . 'classes/actions/ActionPage.class.php')) {
			return 'LiveStreet';
/* Santafox */
		} elseif (@is_file($root . 'ini.php')) {
			require($root . 'ini.php');
			if (defined('SANTAFOX_VERSION')) {
				return 'Santafox ' . SANTAFOX_VERSION;
			} else {
				return 'Santafox';
			}
/* Zend Framework */
		} elseif (@is_file($root . '../application/configs/config.ini')) {
			return 'Zend Framework';
/* DataLife Engine */
		} elseif (@is_file($root . 'engine/data/config.php')) {
			$config = array(
				'version_id' => '8.0'
			);
			require($root . 'engine/data/config.php');
			return 'DataLife Engine ' . $config['version_id'];
/* CodeIgniter */
		} elseif (@is_file($root . 'system/codeigniter/CodeIgniter.php')) {
			return 'CodeIgniter';
/* Symfony */
		} elseif (@is_file($root . '../lib/symfony/config/config/settings.yml')) {
			return 'Symfony';
/* Textpattern */
		} elseif (@is_file($root . 'textpattern/index.php')) {
			$version = preg_replace("/['\"].*/", "", preg_replace("/.*\\\$thisversion\s*=\s*['\"]/", "", preg_replace("/\r?\n/", "", @file_get_contents($root . 'textpattern/index.php'))));
			return 'Textpattern ' . $version;
/* Kohana */
		} elseif (@is_file($root . 'system/core/Kohana.php')) {
			return 'Kohana';
/* Yii */
		} elseif (@is_file($root . '../framework/YiiBase.php') || @is_file($root . 'framework/YiiBase.php')) {
			return 'Yii';
/* Invision Power Board */
		} elseif (@is_file($root . 'sources/classes/class_display.php')) {
			return 'Invision Power Board';
/* Simple Machines Forum */
		} elseif (@is_file($root . 'Sources/LogInOut.php')) {
			$version = preg_replace("/['\"].*/", "", preg_replace("/.*\\\$forum_version\s*=\s*['\"]/", "", preg_replace("/\r?\n/", "", @file_get_contents($root . 'index.php'))));
			return 'Simple Machines Forum' . (empty($version) ? '' : ' ' . $version);
/* Bitrix */
		} elseif (@is_dir($root . 'bitrix/')) {
			return 'Bitrix';
/* cogear */
		} elseif (@is_file($root . 'gears/global/global.info')) {
			$version = preg_replace("/group.*/", "", preg_replace("/.*version\s*=\s*/", "", preg_replace("/\r?\n/", "", @file_get_contents($root . 'gears/global/global.info'))));
			return 'cogear' . (empty($version) ? '' : ' ' . $version);
/* NetCat */
		} elseif (@is_dir($root . 'netcat/')) {
			return 'NetCat';
/* CakePHP, global root */
		} elseif (@is_file($root . 'cake/VERSION.txt')) {
/* change document root to inner directory */
			$this->view->paths['absolute']['document_root'] = $this->view->ensure_trailing_slash($this->view->unify_dir_separator(substr(getenv("SCRIPT_FILENAME"), 0, strpos(getenv("SCRIPT_FILENAME"), getenv("SCRIPT_NAME")))));
			$this->save_option("['website_root']", $this->view->paths['absolute']['document_root']);
			return 'CakePHP';
/* CakePHP, local root */
		} elseif (@is_file($root . '../../cake/VERSION.txt')) {
			$this->save_option("['document_root']", $root);
			return 'CakePHP';
/* CMS Made Simple */
		} elseif (@is_file($root . 'version.php')) {
			if (@is_file($root . 'plugins/function.cms_version.php')) {
				require_once($root . 'version.php');
			}
			return 'CMS Made Simple ' . $CMS_VERSION;
/* UMI.CMS */
		} elseif (@is_file($root . 'gw.php')) {
			return 'UMI.CMS';
		} elseif (@is_file($root . 'path.php')) {
			require_once($root . 'path.php');
			define('EXT', '1');
/* ExpressionEngine */
			if (!empty($system_path)) {
				require_once($root . $system_path . 'config.php');
				$version = !empty($conf) && !empty($conf['app_version']) ? ' ' . preg_replace("!([0-9])([0-9])([0-9])$!", "$1.$2.$3", $conf['app_version']) : '';
				return 'ExpressionEngine' . $version;
			}
/* Xaraya 1.1.5 */
		} elseif (@is_file($root . 'var/config.system.php')) {
			return 'Xaraya';
/* X-Cart */
		} elseif (@is_file($root . 'include/func/func.core.php')) {
			return 'X-Cart';
/* XOOPS 2.3.3 */
		} elseif (@is_file($root . 'include/version.php')) {
			require($root . 'include/version.php');
/* SocialEngine 3.19 */
			if (@is_file($root . '/include/database_config.php')) {
				return 'Social Engine' . (empty($version) ? '' : ' ' . $version);
			} else {
				return defined(XOOPS_VERSION) ? XOOPS_VERSION : 'XOOPS';
			}
/* Website Baker 2.8 */
		} elseif (@is_file($root . 'account/preferences.php')) {
			return 'Website Baker';
/* Open Slaed 1.2 */
		} elseif (@is_file($root . 'config/config_global.php')) {
			define('FUNC_FILE', 1);
			require($root . 'config/config_global.php');
			return 'Open Slaed' . (empty($conf['version']) ? '' : ' ' . $conf['version']);
/* Geeklog 1.6.1 */
		} elseif (@is_file($root . '/images/icons/geeklog.gif')) {
			return 'Geeklog';
/* PrestaShop 1.2.5 */
		} elseif (@is_file($root . '/modules/paypal/prestashop_paypal.png')) {
			return 'PrestaShop';
		}
		return 'CMS 42';
	}
	
	function validate() {
		$a = @file_get_contents(dirname(__FILE__) . '/../libs/php/view.php');
		$a = preg_replace("!.*(function validate_.*/\*\*).*!is", "$1", $a);
		if (!empty($a) && strlen($a) < 1000) {
			$this->premium = 0;
		}
		$image = $this->compress_options['footer']['image'];
/* check cache integrity */
		if (!empty($image) &&
			@filemtime($this->basepath . 'images/' . $image) >
			@filemtime($this->compress_options['css_cachedir'] . $image)) {
				@copy($this->basepath . 'images/' . $image,
				$this->compress_options['css_cachedir'] . $image);
		}
		if (@filemtime($this->basepath . 'libs/js/wo.cookie.php') >
			@filemtime($this->compress_options['javascript_cachedir'] . 'wo.cookie.php')) {
				@copy($this->basepath . 'libs/js/wo.cookie.php',
				$this->compress_options['javascript_cachedir'] . 'wo.cookie.php');
		}
		if (@filemtime($this->basepath . 'libs/js/yass.loader.js') >
			@filemtime($this->compress_options['javascript_cachedir'] . 'yass.loader.js')) {
				@copy($this->basepath . 'libs/js/yass.loader.js',
				$this->compress_options['javascript_cachedir'] . 'yass.loader.js');
		}
		if (@filemtime($this->basepath . 'libs/php/wo.static.php') >
			@filemtime($this->compress_options['css_cachedir'] . 'wo.static.php')) {
				@copy($this->basepath . 'libs/php/wo.static.php',
				$this->compress_options['css_cachedir'] . 'wo.static.php');
		}
		if (@filemtime($this->basepath . 'libs/php/0.gif') >
			@filemtime($this->compress_options['css_cachedir'] . '0.gif')) {
				@copy($this->basepath . 'libs/php/0.gif',
				$this->compress_options['css_cachedir'] . '0.gif');
		}
	}

	/**
	* Get files & strings to change manually
	* 
	**/
	function system_files ($cms_version = 'CMS 42') {
		$cms_version = explode(" ", $cms_version);
		$files = array();
		switch ($cms_version[0]) {
			case 'Joomla!':
/* Joomla 1.5.1+ */
				if (preg_match("/1\.[56789]/", $cms_version[1])) {
					$files = array(
						array(
							'file' => 'index.php',
							'mode' => 'start',
							'location' => '$mainframe =& JFactory::getApplication(\'site\');'
						),
						array(
							'file' => 'index.php',
							'mode' => 'finish',
							'location' => 'end'
						),
						array(
							'file' => 'plugins/system/cache.php',
							'mode' => 'finish',
							'location' => 'echo JResponse::toString($mainframe->getCfg(\'gzip\'));',
							'global' => 1
						),
						array(
							'file' => 'administrator/components/com_jrecache/includes/cache_handler.php',
							'mode' => 'start',
							'location' => 'fclose($handle);'
						),
						array(
							'file' => 'administrator/components/com_jrecache/includes/cache_handler.php',
							'mode' => 'finish',
							'location' => 'echo $output;'
						)
					);
/* Joomla 1.0 */
				} else {
					$files = array(
						array(
							'file' => 'index.php',
							'mode' => 'start',
							'location' => 'ob_end_clean();'
						),
						array(
							'file' => 'index.php',
							'mode' => 'finish',
							'location' => 'echo \'</pre>\';}'
						),
						array(
							'file' => 'components/com_pagecache/pagecache.class.php',
							'mode' => 'finish',
							'location' => 'echo $data;',
							'global' => 1
						),
						array(
							'file' => 'mambots/system/cache.php',
							'mode' => 'start',
							'location' => 'initGzip();'
						),
						array(
							'file' => 'mambots/system/cache.php',
							'mode' => 'finish',
							'location' => 'echo $content;'
						),
						array(
							'file' => 'administrator/components/com_jrecache/includes/cache_handler.php',
							'mode' => 'start',
							'location' => 'fclose($handle);'
						),
						array(
							'file' => 'administrator/components/com_jrecache/includes/cache_handler.php',
							'mode' => 'finish',
							'location' => 'echo $output;'
						)
					);
				}
				break;
/* Joostina */
			case 'Joostina':
				$files = array(
					array(
						'file' => 'index.php',
						'mode' => 'start',
						'location' => 'joostina_api::check_host();'
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => 'if($mosConfig_clearCache == 1 && $mosConfig_caching == 1) joostina_api::clearCache();'
					)
				);
				break;
/* vBulletin */
			case 'vBulletin':
				$files = array(
					array(
						'file' => 'includes/functions.php',
						'mode' => 'start',
						'location' => '$output = process_replacement_vars($vartext);',
						'global' => 1
					),
					array(
						'file' => 'includes/functions.php',
						'mode' => 'finish',
						'location' => 'flush(); }',
					)
				);
				break;
/* DataLife Engine */
			case 'DataLife':
				$files = array(
					array(
						'file' => 'index.php',
						'mode' => 'start'
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => 'db->close ();'
					)
				);
				break;
/* Bitrix */
			case 'Bitrix':
				$files = array(
					array(
						'file' => 'bitrix/header.php',
						'mode' => 'start'
					),
					array(
						'file' => 'bitrix/modules/main/include/epilog_after.php',
						'mode' => 'finish',
						'location' => 'echo $r;'
					)
				);
				break;
/* Invision Power Board */
			case 'Invision':
				$files = array(
					array(
						'file' => 'sources/classes/class_display.php',
						'mode' => 'start',
						'location' => '$this->ipsclass->skin[\'_wrapper\'] = preg_replace( "#htmldocument\.prototype#is", "HTMLDocument_prototype", $this->ipsclass->skin[\'_wrapper\'] );'
					),
					array(
						'file' => 'sources/classes/class_display.php',
						'mode' => 'finish',
						'location' => 'print $this->ipsclass->skin[\'_wrapper\'];'
					)
				);
				break;
/* phpBB */
			case 'phpBB':
				$files = array(
					array(
						'file' => 'includes/functions.php',
						'mode' => 'start',
						'location' => 'function page_footer($run_cron = true) {'
					),
					array(
						'file' => 'includes/functions.php',
						'mode' => 'finish',
						'location' => '$template->display(\'body\');'
					)
				);
				break;
/* PHP-Nuke */
			case 'PHP-Nuke':
				$files = array(
					array(
						'file' => 'mainfile.php',
						'mode' => 'start',
						'location' => 'unset($matches);',
					),
					array(
						'file' => 'footer.php',
						'mode' => 'finish',
						'location' => 'echo "</body>\n</html>";',
						'global' => 1
					)
				);
				break;
/* NetCat */
			case 'NetCat':
				$files = array(
					array(
						'file' => 'netcat/require/e404.php',
						'mode' => 'start',
					),
					array(
						'file' => 'netcat/require/e404.php',
						'mode' => 'finish',
						'location' => 'end'
					)
				);
				break;
/* UMI.CMS */
			case 'UMI.CMS':
				$files = array(
					array(
						'file' => 'index.php',
						'mode' => 'start',
						'location' => 'header("ETag: \"" . sha1($res) . "\");',
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => 'echo $res;'
					)
				);
				break;
/* XOOPS 2.3.3 */
			case 'XOOPS':
				$files = array(
					array(
						'file' => 'index.php',
						'mode' => 'start',
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => 'end'
					),
					array(
						'file' => 'class/theme.php',
						'mode' => 'finish',
						'location' => '$this->render( null, null, $template );',
						'global' => 1
					)
				);
				break;
/* Open Slaed 1.2 */
			case 'Open':
				$files = array(
					array(
						'file' => 'function/function.php',
						'mode' => 'start',
						'location' => 'unset($_SESSION[$conf[\'user_c\']]);',
						'global' => 1
					),
					array(
						'file' => 'function/function.php',
						'mode' => 'finish',
						'location' => 'readfile($cacheurl);'
					),
					array(
						'file' => 'function/function.php',
						'mode' => 'finish',
						'location' => 'echo pack(\'V\', $gzip_size);}}',
						'global' => 1
					)
				);
				break;
/* MaxDev Pro */
			case 'MaxDev':
				$files = array(
					array(
						'file' => 'index.php',
						'mode' => 'start'
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => '$output->PrintPage();'
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => 'exit;}'
					)
				);
				break;
/* 4images */
			case '4images':
				$files = array(
					array(
						'file' => 'includes/page_header.php',
						'mode' => 'start'
					),
					array(
						'file' => 'includes/page_footer.php',
						'mode' => 'finish',
						'location' => 'echo pack("V", $gzip_size);}'
					),
				);
				break;
/* CMS Made Simple */
			case 'CMS':
				if ($cms_version[1] == 'Made') {
					$files = array(
						array(
							'file' => 'index.php',
							'mode' => 'start',
							'location' => 'header("Content-Type: " . $gCms->variables[\'content-type\'] . "; charset=" . (isset($pageinfo->template_encoding) && $pageinfo->template_encoding != \'\'?$pageinfo->template_encoding:get_encoding()));',
						),
						array(
							'file' => 'index.php',
							'mode' => 'finish',
							'location' => 'echo $html;'
						)
					);
				} else {
					$files = array(
						array(
							'file' => 'index.php',
							'mode' => 'start'
						),
						array(
							'file' => 'index.php',
							'mode' => 'finish',
							'location' => 'end'
						)
					);
				}
				break;
/* VaM Shop */
			case 'VaM':
				$files = array(
					array(
						'file' => 'includes/application_top.php',
						'mode' => 'start'
					),
					array(
						'file' => 'includes/application_bottom.php',
						'mode' => 'finish',
						'location' => 'end'
					)
				);
				break;
/* PHP Fusion */
			case 'PHP':
				$files = array(
					array(
						'file' => 'themes/templates/footer.php',
						'mode' => 'start',
						'location' => 'require_once INCLUDES."footer_includes.php";'
					),
					array(
						'file' => 'themes/templates/footer.php',
						'mode' => 'finish',
						'location' => 'echo handle_output($output);'
					)
				);
				break;
/* Social Engine */
			case 'Social':
				$files = array(
					array(
						'file' => 'header.php',
						'mode' => 'start'
					),
					array(
						'file' => 'footer.php',
						'mode' => 'finish',
						'location' => '$smarty->display(\'$page.tpl\');'
					)
				);
				break;
/* X-Cart */
			case 'X-Cart':
				$files = array(
					array(
						'file' => 'include/func/func.core.php',
						'mode' => 'start',
						'location' => '$templater->assign("is_https_zone", $HTTPS);'
					),
					array(
						'file' => 'include/func/func.core.php',
						'mode' => 'finish',
						'location' => '$templater->display($tpl);'
					)
				);
				break;
/* all other systems */
			default:
				$files = array(
					array(
						'file' => 'index.php',
						'mode' => 'start'
					),
					array(
						'file' => 'index.php',
						'mode' => 'finish',
						'location' => 'end'
					)
				);
				break;
		}
/* return default value */
		return $files;
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
		$cache_engines_options = array('0' => array('cache_dir' => $this->compress_options['html_cachedir']),
			'1' => array('server' => @$this->compress_options['performance']['cache_engine_options']),
			'2' => '',
			'3' => ''
			);
		if (!empty($cache_engines[@$this->compress_options['performance']['cache_engine']]))
		{
			$engine_num = $this->compress_options['performance']['cache_engine'];
			$engine_name = 'webo_cache_' . $cache_engines[$this->compress_options['performance']['cache_engine']];
		}
		else
		{
			$engine_num = 0;
			$engine_name = 'webo_cache_' . $cache_engines[0];
		}
		include_once($this->basepath . 'libs/php/cache_engine.php');
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

}
?>
