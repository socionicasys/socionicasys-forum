<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Parses array of images to dmensions/pathnames. Stores array of CSS rules
 * License agreement: http://www.webogroup.com/about/EULA.txt
 *
 **/
class html_sprites {

	/**
	* Constructor
	* Sets the options and converts
	**/
	function html_sprites ($imgs, $options, $main) {
		$this->options = $options;
		$this->main = $main;
		if (!class_exists('css_sprites_optimize', false)) {
			require($this->options['css']['installdir'] . 'libs/php/css.sprites.optimize.php');
		}
/* create CSS Sprites combiner */
		$this->optimizer = new css_sprites_optimize(array(
			'root_dir' => $this->options['css']['installdir'],
			'current_dir' => $this->options['page']['cachedir'],
			'html_cache' => $this->options['page']['cachedir'],
			'website_root' => $this->options['document_root'],
			'truecolor_in_jpeg' => $this->options['css']['truecolor_in_jpeg'],
			'aggressive' => 0,
			'no_ie6' => 0,
			'ignore' => $this->options['css']['css_sprites_ignore'],
			'ignore_list' => $this->options['css']['css_sprites_exclude'],
			'partly' => 0,
			'extra_space' => $this->options['css']['css_sprites_extra_space'],
			'expires_rewrite' => $this->options['css']['css_sprites_expires_rewrite'],
			'cache_images' => $this->options['page']['cache_images'],
			'cache_images_rewrite' => $this->options['page']['far_future_expires_rewrite'],
			'data_uris' => 0,
			'data_uris_separate' => 0,
			'data_uris_size' => 0,
			'data_uris_ignore_list' => '',
			'mhtml' => 0,
			'mhtml_size' => 0,
			'mhtml_ignore_list' => '',
			'css_url' => '',
			'dimensions_limited' => $this->options['page']['dimensions_limited'] ? $this->options['page']['dimensions_limited'] : 10000,
			'no_css_sprites' => 0,
			'multiple_hosts' => empty($this->options['page']['parallel']) ?
				array() : explode(" ", $this->options['page']['parallel_hosts']),
			'user_agent' => $this->main->ua_mod,
			'punypng' => $this->options['css']['punypng'],
			'restore_properties' => 0,
			'ftp_access' => $this->options['page']['parallel_ftp'],
			'http_host' => $this->options['page']['host'],
			'https_host' => $this->options['page']['parallel_https'],
			'uniform_cache' => $this->options['uniform_cache']
		));
/* calculate all dimensions for images */
		$this->images = $this->get_images_dimensions($imgs);
		ksort($this->images);
		$this->css = array(42 => array());
		$this->css_images = array();
	}

	/**
	/* Main function to process with images
	/*
	**/
	function process ($content) {
		$str = '';
		$equal = 1;
		$exclude_list = explode(" ", $this->options['css']['css_sprites_exclude']);
/* calculate styles */
		foreach ($this->images as $url => $image) {
			$width = $image[0];
			$height = $image[1];
			$class = $image[2];
			$active = empty($image[3]) ? 0 : $image[3];
			$filename = $this->options['document_root'] . $url;
			$name = preg_replace("@.*/@", "", $url);
/* skip big images */
			if ($width <= $this->options['page']['dimensions_limited'] &&
				$height <= $this->options['page']['dimensions_limited'] &&
				$width && $height && !empty($class) && !empty($active) &&
				((empty($this->options['css']['css_sprites_ignore']) && !in_array($name, $exclude_list)) ||
				(!empty($this->options['css']['css_sprites_ignore']) && in_array($name, $exclude_list)))) {
					$this->css_images[$url] = array($filename,
						$width, $height, 0, 0, 0, 0, 42, '.' . $class);
					$this->css[42]['.' . $class] = array(
						'width' => $width . 'px',
						'height' => $height . 'px',
						'padding' => 0,
						'background-image' => 'url(' . $url . ')',
						'background-repeat' => 'no-repeat'
					);
					$str .= $url . "_" . $width . "_" . $height;
/* check if all images are equal - this makes Sprite calculation easier */
					$w = empty($w) ? $width : $w;
					$h = empty($h) ? $height : $h;
					$equal = $equal && $w == $width && $h == $height;
			}
		}
/* skip creating if there is only 1 image */
		if (count($this->css_images) > 1) {
			$https = empty($_SERVER['HTTPS']) ? '' : 's';
			$this->sprite = 'webo.' . md5($str) . '.png';
			if (!empty($this->images[$this->sprite . $https])) {
				$styles = $this->images[$this->sprite . $https][2];
			} else {
				$dir = @getcwd();
				@chdir($this->options['page']['cachedir']);
				$this->optimizer->css_images = array(
					$this->sprite => array('images' => $this->css_images)
				);
				$this->optimizer->css = $this;
				$this->optimizer->merge_sprites(4, $this->sprite, $equal && !empty($w) && !empty($h) ? 2 : 1);
				$created = 0;
/* check if we have created sprite */
				foreach ($this->optimizer->css->css[42] as $class => $rules) {
					foreach ($rules as $k => $v) {
						if ($k == 'background-image') {
							$url = substr($v, 4, strlen($v) - 5);
/* leave this image */
							if (empty($this->images[$url])) {
								$created = 1;
/* or remove from generated array */
							} else {
								unset($this->css_images[$url]);
							}
						}
					}
				}
				@chdir($dir);
				if ($created) {
					$styles = $this->calculate_styles($this->optimizer->css->css[42]);
				} else {
					$styles = '';
				}
/* cache styles to file */
				$this->images[$this->sprite . $https] = array(0, 0, $styles);
				$str = '<?php';
				foreach ($this->images as $k => $i) {
					$str .= "\n" . '$images[\'' . $k .
						"'] = array(" . $i[0] . "," . $i[1] . ",'" . $i[2] . "');";
				}
				$str .= "\n?>";
				$this->main->write_file($this->options['page']['cachedir'] . 'wo.img.cache.php', $str);
			}
			$content = $this->add_styles($content, $styles);
		} else {
			unset($this->css_images);
		}
		return $content;
	}

	/**
	* Get dimensions for give array of HTML images
	*
	**/
	function get_images_dimensions ($imgs) {
		$images = array();
/* load cached images' dimensions */
		@include($this->options['page']['cachedir'] . 'wo.img.cache.php');
/* calculate all dimensions for new images */
		if (!empty($imgs)) {
			foreach ($imgs as $key => $image) {
				if (!empty($this->options['page']['html_tidy']) && ($pos=strpos($image[0], 'src="'))) {
					$old_src = substr($image[0], $pos+5, strpos(substr($image[0], $pos+5), '"'));
				} elseif (!empty($this->options['page']['html_tidy']) && ($pos=strpos($image[0], "src='"))) {
					$old_src = substr($image[0], $pos+5, strpos(substr($image[0], $pos+5), "'"));
				} else {
					$old_src = preg_replace("!^['\"\s]*(.*?)['\"\s]*$!is", "$1", preg_replace("!.*\ssrc\s*=\s*(\"[^\"]+\"|'[^']+'|[\S]+).*!is", "$1", $image[0]));
				}
/* strip GET parameter */
				$old_src = ($old_src_param_pos = strpos($old_src, '?')) ? substr($old_src, 0, $old_src_param_pos) : $old_src;
				$absolute_src = $this->main->convert_path_to_absolute($old_src,
					array('file' => $_SERVER['REQUEST_URI']));
				$filename = array_pop(split("/", $absolute_src));
/* fetch only non-cached images */
				if (!empty($absolute_src) && (!$this->optimizer->ignore || in_array($filename, $this->optimizer->ignore_list))) {
					if (empty($images[$absolute_src]))  {
						$need_refresh = 1;
						$width = $height = 0;
						$class = '';
						if (strpos($image[0], 'nosprites') === false) {
							list($width, $height) = $this->optimizer->get_image(0, '', $absolute_src);
							$width = empty($width) ? 0 : $width;
							$height = empty($height) ? 0 : $height;
/* skip dymanic images, need to download the last... */
							$class = preg_match("@\.(ico|gif|jpe?g|bmp|png)$@", $old_src) &&
								$width && $height ? 'wo' . md5($absolute_src) : '';
						}
						$images[$absolute_src] = array($width, $height, $class);
					}
					$images[$absolute_src][3] =
						!empty($this->options['page']['per_page']) ? 1 : 0;
				}
/* remember src for calculated images */
				$imgs[$key] = $absolute_src;
			}
		}
		if (!empty($need_refresh)) {
/* cache images' dimensions to file */
			$str = '<?php';
			foreach ($images as $k => $i) {
				$str .= "\n" . '$images[\'' . str_replace('//', '/', $k) .
					"'] = array(" . round($i[0]) . "," . round($i[1]) . ",'" . $i[2] . "');";
				if (empty($this->options['page']['per_page'])) {
					$images[$k][3] = 1;
				}
			}
			$str .= "\n?>";
			$this->main->write_file($this->options['page']['cachedir'] . 'wo.img.cache.php', $str);
/* or just mark all images as active */
		} elseif (empty($this->options['page']['per_page'])) {
			foreach ($images as $k => $i) {
				$images[$k][3] = 1;
			}
		}
		return $images;
	}

	/**
	* Return HTML Sprites styles
	*
	**/
	function calculate_styles ($css) {
		$styles = '';
		if (!empty($this->options['page']['parallel'])) {
			$hosts = explode(" ", trim($this->options['page']['parallel_hosts']));
			if (count($hosts)) {
				$host = $hosts[0];
			}
			if (!empty($_SERVER['HTTPS']) && !empty($this->options['page']['parallel_https'])) {
				$host = $this->options['page']['parallel_https'];
			}
		}
		if (!empty($css)) {
/* form final css chunk */
			$styles = '<style type="text/css">';
			foreach ($css as $class => $rules) {
				$styles .= $class . '{';
				foreach ($rules as $k => $v) {
					if ($k == 'background-image') {
						$v = 'url(' .
							(empty($host) ?
								(empty($this->options['page']['far_future_expires_rewrite']) ?
								'' : $this->options['page']['cachedir_relative'] . 'wo.static.php?') .
								$this->options['page']['cachedir_relative'] :
								'//' . $host . $this->options['page']['cachedir_relative']) .
							substr($v, 4);
					}
					$styles .= $k . ':' . $v . ';';
				}
				$styles .= '}';
			}
			$styles = str_replace(';}', '}', $styles) . '</style>';
		}
		return $styles;
	}

	/**
	* Return HTML with inserted for HTML Sprites styles
	*
	**/
	function add_styles ($content, $styles) {
		if (!empty($styles)) {
/* insert css chunk to spot */
			$content = str_replace("@@@WSSSTYLES@@@", $styles, $content);
		} else {
			unset($this->css_images);
		}
		return $content;
	}

}

?>