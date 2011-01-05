<?php
/**
 * File from WEBO Site SpeedUp, WEBO Software (http://www.webogroup.com/)
 * Converts given array of images to CSS Sprites.
 * Outputs array of selectors
 * License agreement: http://www.webogroup.com/about/EULA.txt
 *
 **/
class css_sprites_optimize {

	/**
	* Constructor
	* Sets the options
	**/
	function css_sprites_optimize ($options = false) {
/* array for global media@ distribution */
		$this->media = array();
/* array for images from selectors */
		$this->css_images = array();
/* timestamp for CSS Sprites files */
		$this->timestamp = '';
		if (!empty($options)) {
/* set directories */
			$this->current_dir = $options['current_dir'];
			$this->root_dir = $options['root_dir'];
			$this->website_root = $options['website_root'];
/* directory with cached HTML, for wo.static.php */
			$this->html_dir = $options['html_cache'];
/* calculate relative path to cache directory if we require it */
			if (!empty($this->html_dir)) {
				$this->html_dir = str_replace($this->website_root, "/", $this->html_dir);
			}
/* output True Color images in JPEG (or PNG24) */
			$this->truecolor_in_jpeg = $options['truecolor_in_jpeg'];
/* use aggressive logic for repeat-x/y */
			$this->aggressive = $options['aggressive'];
/* leave some space for combined Sprites to handle resized fonts */
			$this->extra_space = $options['extra_space'];
/* Exclude or include only files? */
			$this->ignore = $options['ignore'];
/* list of excluded from CSS Sprites files */
			$this->ignore_list = explode(" ", $options['ignore_list']);
/* create data:URI based on parsed CSS file */
			$this->data_uris = $options['data_uris'];
/* max size of images to be converted to data:URI */
			$this->data_uris_size = $options['data_uris_size'];
/* list of excluded from data:URI files */
			$this->data_uris_ignore_list = explode(" ", $options['data_uris_ignore_list']);
/* create mhtml based on parsed CSS file */
			$this->mhtml = $options['mhtml'];
/* max size of images to be converted to mhtml */
			$this->mhtml_size = $options['mhtml_size'];
/* list of excluded from data:URI files */
			$this->mhtml_ignore_list = explode(" ", $options['mhtml_ignore_list']);
/* external URL to place into file with mhtml */
			$this->css_url = $options['css_url'];
/* return separate files: rules only and with base64 images */
			$this->separated = $options['data_uris_separate'];
/* part or full process */
			$this->partly = $options['partly'];
/* exclude IE6 from CSS Sprites */
			$this->no_ie6 = $options['no_ie6'];
/* if there is initial images dimensional limit */
			$this->dimensions_limited = $options['dimensions_limited'];
/* only compress CSS and convert images to data:URI */
			$this->no_sprites = $options['no_css_sprites'];
/* multiple hosts */
			$this->multiple_hosts = $options['multiple_hosts'];
			if (count($this->multiple_hosts) > 4) {
				$this->multiple_hosts = array($this->multiple_hosts[0], $this->multiple_hosts[1], $this->multiple_hosts[2], $this->multiple_hosts[3]);
			}
/* number of multiple hosts */
			$this->multiple_hosts_count = count($this->multiple_hosts);
			if (empty($this->multiple_hosts[0]) && empty($this->multiple_hosts[1])) {
				$this->multiple_hosts_count = 0;
			}
/* use rewrite (add spot to images?) */
			$this->no_rewrite = $options['expires_rewrite'];
/* cache images */
			$this->cache_images = $options['cache_images'];
/* cache images (wo.static.php will be used?) */
			$this->cache_images_htaccess = !$options['cache_images_rewrite'];
/* current USER AGENT spot */
			$this->ua = empty($options['user_agent']) ? '' : substr($options['user_agent'], 1);
/* is USER AGENT old IE? */
			$this->ie = in_array($this->ua, array('ie4', 'ie5', 'ie6', 'ie7'));
/* or IE7@Vista? */
			$this->ie7v = $this->ua == 'ie4' ? 1 : 0;
			$this->compressed_mhtml = $this->ie ? "/*\nContent-Type:multipart/related;boundary=\"_\"" : '';
/* punypng API key */
			$this->punypng_key = $options['punypng'];
/* Restore or not missed CSS properties? */
			$this->restore_properties = $options['restore_properties'];
/* FTP access to upload Sprites to CDN */
			$this->ftp_access = $options['ftp_access'];
/* using HTTPS ?*/
			$this->https = empty($_SERVER['HTTPS']) ? '' : 's';
/* ordinar host for HTTP */
			$this->http_host = $options['http_host'];
/* special host for HTTPS */
			$this->https_host = $options['https_host'];
/* create only data:URI chunks? */
			$this->uniform_cache = $options['uniform_cache'];
/* CSS rule to avoid overlapping of properties */
			$this->none = 'none!important';
		}
		$memory_available = function_exists('memory_get_usage') ? memory_get_usage() : 10000000;
/* restrict square for large Sprites due to system limitations */
		$this->possible_square = round((round(str_replace("M", "000000", str_replace("K", "000", @ini_get('memory_limit')))) - $memory_available) / 10);
/* view library (to upload files) */
		$this->lib = new compressor_view();
	}
/* download requested image */
	function get_image ($mode = 0, $location = 1, $css_image) {
		$image_saved = $css_image;
/* handle cases with data:URI */
		if (substr($css_image, 0, 5) == 'data:') {
			$image_name = md5($css_image) . "." . preg_replace("/.*image\/([^;]*);base64.*/", "$1", $css_image);
			$fp = @fopen($image_name , "w");
			if ($fp) {
				@fwrite($fp, base64_decode(preg_replace("/.*;base64,/", "", $css_image)));
				@fclose($fp);
				$css_image = $image_name;
			} else {
				$css_image = '';
			}
/* handle cases with mhtml: NEED TO BE FIXED! */
		} elseif (substr($css_image, 0, 6) == 'mhtml:') {
			$css_image = '';
		} else {
			if (strpos($css_image, "://")) {
				$cached = preg_replace("/.*\//", "", $css_image);
/* check for cached version */
				if (!@is_file($cached)) {
					$this->download_file($css_image, $cached);
					$css_image = @is_file($cached) ? $cached : '';
				} else {
					$css_image = $cached;
				}
			} else {
				if (!preg_match("/^webo[ixy\.]/", $css_image)) {
					$css_image = $css_image{0} == '/' ? $this->website_root . $css_image : $this->current_dir . '/' .$css_image;
				}
			}
		}
		$chunks = explode(".", $css_image);
		$extension = str_replace('jpg', 'jpeg', strtolower(array_pop($chunks)));
		if ($mode > 0) {
			$chunks = explode("/", $css_image);
			$filename = array_pop($chunks);
/* Thx for htc for ali@ */
			if (!@is_file($css_image) || in_array($extension, array('htc', 'cur', 'eot', 'ttf', 'svg', 'otf', 'woff')) || strpos($css_image, "://")) {
				$css_image = $image_saved;
				return $css_image;
			}
		}
		switch ($mode) {
/* mhtml */
			case 2:
/* 50KB default restriction for mhtml: -- why? */
				if (@filesize($css_image) < $this->mhtml_size && !in_array($filename, $this->mhtml_ignore_list)) {
					$this->compressed_mhtml .= "\n\n--_\nContent-Location:$location\nContent-Transfer-Encoding:base64\n\n" . base64_encode(@file_get_contents($css_image));
					$css_image = 'mhtml:' . $this->css_url . '!' . $location;
				} else {
					$css_image = $image_saved;
				}
				return $css_image;
/* data:URI */
			case 1:
				$encoded = base64_encode(@file_get_contents($css_image));
/* don't create data:URI greater than 32KB -- for IE8 */
				if (@filesize($css_image) < $this->data_uris_size &&
					!in_array($filename, $this->data_uris_ignore_list) &&
					!empty($encoded)) {
/* convert image to base64-string */
					$css_image = 'data:image/' . $extension . ';base64,' . $encoded;
				} else {
/* just return absolute URL for image */
					$css_image = $image_saved;
				}
				return $css_image;
/* image dimensions */
			default:
				if (@is_file($css_image)) {
/* check for animation */
					if (strtolower(preg_replace("/.*\./", "", $css_image)) == 'gif' && $this->is_animated_gif($css_image)) {
						return array(0, 0);
					}
/* rewrite calculated path */
					$this->css_image = $css_image;
/* check for icons */
					if ($extension == 'ico') {
						$size = filesize($css_image);
						if ($size < 1500) {
							return array(16, 16);
						} elseif ($size < 8000) {
							return array(32, 32);
						} else {
							return array(48, 48);
						}
					} else {
/* get dimensions from downloaded image */
						return @getimagesize($css_image);
					}
				} else {
					return array(0, 0);
				}
				break;
		}
	}
/* find places for images in complicated Sprite */
	function sprites_placement ($css_images, $css_icons, $mode = 0) {
/* initial matrix for css images */
		$matrix = array();
		$css_images['x'] = $css_images['y'] = $matrix_x = $matrix_y = 0;
/* check if we have initial no-repeat images */
		if (!empty($css_images['images'])) {
/* array for images ordered by square */
			$ordered_images = array();
/* to track duplicates */
			$added_images = array();
/* add images to this matrix one-by-one */
			foreach ($css_images['images'] as $image) {
				$square = $image[1] * $image[1] + $image[2] * $image[2] - $image[1] * $image[2];
				while (!empty($ordered_images[$square])) {
/* increase square while we don't have unique key */
					$square++;
				}
				$ordered_images[$square] = $image;
			}
/* sort images by square */
			krsort($ordered_images);
/* remember already filled square - to skip some looping */
			$filled_square = 0;
/* add images to matrix */
			foreach ($ordered_images as $key => $image) {
/* restrict square if no memory */
				if ($matrix_x * $matrix_y <= $this->possible_square) {
					$minimal_x = 0;
					$minimal_y = 0;
/* if this is a unique image */
					if (empty($added_images[$image[0]])) {
/* initial coordinates */
						$I = $J = 0;
/* avoid affecting negative background-position to image placement, we have already counted it */
						$width = $image[1] + ($image[3] > 0 ? $image[3] : 0) + $image[5] + ($this->extra_space && count($ordered_images) > 1 ? 5 : 0);
						$height = $image[2] + ($image[4] > 0 ? $image[4] : 0) + $image[6] + ($this->extra_space && count($ordered_images) > 1 ? 5 : 0);
						$shift_x = $image[3];
						$shift_y = $image[4];
/* all images have equal dimensions => loop with increased step */
						$stepx = $mode == 2 ? $width : ($mode == 1 ? ceil($width/2) : 2);
						$stepy = $mode == 2 ? $height : ($mode == 1 ? ceil($height/2) : 2);
/* to remember the most 'full' place for new image */
						$minimal_square = $matrix_x * $matrix_y;
/* flag if we have enough space */
						$no_space = 1;
/**
Some magic here: we shrink actual array with 16 values in 1 cell - to reduce overall memory size.
This increases (in comparison to raw array[x][y] call) execution time by ~2x.
**/
						for ($i = $filled_square; $i < $matrix_x; $i = $i + $stepx) {
							for ($j = $filled_square; $j < $matrix_y; $j = $j + $stepy) {
								$j0 = ($j - $j%16)/16;
								if ($mode < 2) {
									$j1 = ($j + $height - ($j + $height)%16)/16;
									$j2 = ($j + $height)%16;
									$i1 = $i + $width;
									$j3 = ($j + ($height - $height%2)/2 - ($j + ($height - $height%2)/2)%16)/16;
									$j4 = ($j + ($height - $height%2)/2)%16;
									$i3 = $i + ($width - $width%2)/2;
								}
/* left top corner is empty and three other corners are empty -- we have a placeholder */
								if ((empty($matrix[$i][$j0]) || !($matrix[$i][$j0] & (2<<($j%16)))) &&
/* performance improvement - skip some checks if we have identical images */
									($mode > 1 ||
										((empty($matrix[$i][$j1]) ||
											!($matrix[$i][$j1] & (2<<$j2)))) &&
										(empty($matrix[$i1][$j0]) ||
											!($matrix[$i1][$j0] & (2<<($j%16)))) &&
										(empty($matrix[$i1][$j1]) ||
											!($matrix[$i1][$j1] & (2<<$j2))) &&
/* additionally check 4 points in the middle of edges + 1 center point */
										(empty($matrix[$i3][$j0]) ||
											!($matrix[$i3][$j0] & (2<<($j%16)))) &&
										(empty($matrix[$i][$j3]) ||
											!($matrix[$i][$j3] & (2<<$j4))) &&
										(empty($matrix[$i1][$j3]) ||
											!($matrix[$i1][$j3] & (2<<$j4))) &&
										(empty($matrix[$i3][$j1]) ||
											!($matrix[$i3][$j1] & (2<<$j4))) &&
										(empty($matrix[$i3][$j3]) ||
											!($matrix[$i3][$j3] & (2<<$j4))))) {
/* and Sprite is big enough */
									if ($i + $width < $matrix_x && $j + ($height - $height%2)/2 < $matrix_y) {
										$I = $i;
										$J = $j;
										$i = $matrix_x;
										$j = $matrix_y;
										$no_space = 0;
									} else {
/* else try to remember this placement -- it can be the optimal one */
										if (!$I && !$J && ($i + $width > $matrix_x ? $i + $width - $matrix_x : 0) * $height + ($j + $height > $matrix_y ? $j + $height - $matrix_y : 0) * $matrix_x < $minimal_square ) {
/* if this place is better and we haven't chosen placement yet */
											$minimal_square = ($i + $width > $matrix_x ? $i + $width - $matrix_x : 0) * $height + ($j + $height > $matrix_y ? $j + $height - $matrix_y : 0) * $matrix_x;
											$I = $i;
											$J = $j;
										}
									}
								}
							}
						}
						if ($no_space) {
/* re-count minimal square with linear enlargement */
							if ($width * $matrix_y <= $minimal_square) {
								$I = $matrix_x;
								$J = 0;
							} elseif ($height * $matrix_x <= $minimal_square) {
								$I = 0;
								$J = $matrix_y;
							}
						} else {
							$filled_square = min($I, $J);
						}
/* shift calculated values to remove empty blocks, this leaks a bit */
						$I0 = $I;
						$J1 = ($J-$J%16)/16;
						$J2 = ($J+$height-($J+$height)%16)/16;
						$J3 = ($J+round($height/2)-($J+round($height/2))%16)/16;
						do {
							$I0--;
						} while ($I0 > -1 && empty($matrix[$I0][$J1]) &&
							empty($matrix[$I0][$J2]) &&
							empty($matrix[$I0][$J3]));
						$I = $I0 > 0 ? $I0 + 1 : 0;
						$J0 = $J;
						$I1 = $I + $width;
						$I2 = $I + ($width - $width%2)/2;
						do {
							$J0--;
							$J1 = ($J0-$J0%16)/16;
						} while ($J0 > -1 && (empty($matrix[$I][$J1])
								|| !($matrix[$I][$J1] & (2<<($J0%16)))) &&
							(empty($matrix[$I1][$J1])
								|| !($matrix[$I1][$J1] & (2<<$J0%16))) &&
							(empty($matrix[$I2][$J1])
								|| !($matrix[$I2][$J1] & (2<<$J0%16))));
						$J = $J0 > 0 ? $J0 + 1 : 0;
/* calculate increase of matrix dimensions */
						$minimal_x = $I + $width > $matrix_x ? $width + $I - $matrix_x : 0;
						$minimal_y = $J + $height > $matrix_y ? $height + $J - $matrix_y : 0;
/* fill matrix for this image */
						for ($i = $I; $i < $I + $width; $i++) {
							for ($j = $J; $j < $J + $height; $j++) {
								$j0 = ($j - $j%16)/16;
								$matrix[$i][$j0] = (empty($matrix[$i][$j0]) ?
									0 : $matrix[$i][$j0]) + (2<<($j%16));
							}
						}
/* remember coordinates for this image, keep top/left */
						$ordered_images[$key][3] = $I + $ordered_images[$key][3];
						$ordered_images[$key][4] = $J + $ordered_images[$key][4];
/* add images to processed */
						$added_images[$image[0]] = array($I, $J);
					} else {
/* just copy calculated coordinates */
						$ordered_images[$key][3] = $added_images[$image[0]][0];
						$ordered_images[$key][4] = $added_images[$image[0]][1];
					}
/* remember initial background-position */
					$ordered_images[$key][5] = $shift_x;
					$ordered_images[$key][6] = $shift_y;
					$matrix_x += $minimal_x;
					$matrix_y += $minimal_y;
				}
			}
			$css_images['images'] = $ordered_images;
			$css_images['x'] = $matrix_x;
			$css_images['y'] = $matrix_y;
		}
		$distance = $x = $y = 0;
/* count initial shift (not to hurt current images) */
		$shift = $matrix_y;
/* need to add weboi Sprite to the main one */
		if (!empty($css_icons['images'])) {
			foreach ($css_icons['images'] as $image) {
				$shift += $image[2] + $image[4];
			}
/* distance from the main Sprite */
			$distance = $shift - $matrix_y - $css_icons['images'][0][2] - $css_icons['images'][0][4];
			$x = 0;
			$y = $shift;
			$done_images = array();
/* creating 'steps' */
			foreach ($css_icons['images'] as $image) {
/* skip already processed images */
				if (empty($done_images[$image[0]])) {
/* restrict square if no memory */
					if ($x * ($shift - $distance) + $matrix_x * $matrix_y < $this->possible_square) {
						$width = $image[1];
						$height = $image[2];
						$final_x = $image[3];
						$final_y = $image[4];
						$i = $x;
						$x += $width + $final_x;
						$y -= ($height + $final_y);
						$image[3] = $x - $width;
						$image[4] = $y + $final_y;
						$image[5] = $final_x;
						$image[6] = $final_y;
						$j = $y;
/* fix for negative initial positions */
						$x -= $final_x < 0 ? $final_x : 0;
						$y += $final_y < 0 ? $final_y : 0;
						$j0 = ($j - $j%16)/16;
/* check for 3 points: left, middle and right for the top border */
						while ((empty($matrix[$i][$j0]) || !($matrix[$i][$j0] & (2<<($j%16)))) &&
							(empty($matrix[$i + ($width - $width%2)/2][$j0]) || !($matrix[$i + ($width - $width%2)/2][$j0] & (2<<($j%16)))) &&
							(empty($matrix[$i + $width][$j0]) || !($matrix[$i + $width][$j0] & (2<<($j%16)))) && $j>0) {
								$j--;
								$j0 = ($j - $j%16)/16;
						}
/* remember minimal distance */
						if ($distance > $y - $j - 1) {
							$distance = $y - $j - 1 - $final_y;
						}
/* to separate new images from old ones */
						$image[] = 1;
						$css_images['images'][] = $image;
					} else {
						$css_images['images'][] = $done_images[$image[0]];
					}
				}
			}
		}
/* try to restore required pixels in the Sprite */
		$addon_y = $y < 0 ? -$y : 0;
		if (is_array($css_images['images'])) {
			foreach ($css_images['images'] as $key => $image) {
/* images from the main Sprite */
				if (empty($image[9])) {
					$css_images['images'][$key][4] += $addon_y;
/* shrink distance between webo and weboi Sprites */
				} else {
					$css_images['images'][$key][4] -= $distance;
				}
			}
/* increase dimensions */
			$css_images['x'] = $x > $matrix_x ? $x : $matrix_x;
			$css_images['y'] = $shift - $distance + ($y - $distance < 0 ? $distance - $y : 0);
			return $css_images;
		} else {
			return array();
		}

	}
/* merge all images into final CSS Sprite */
	function merge_sprites ($type, $sprite, $mode = 0) {

		if ((!empty($this->css_images[$sprite]) || ($type == 4 && !empty($this->css_images['weboi.'. $this->timestamp .'.png'])))) {
/* avoid re-calculating of images to switch from PNG to JPEG */
			$file_exists = is_file(preg_replace("/\.png$/i", empty($this->css_images[$sprite]['jpeg']) ? '.png' : '.jpg', $sprite));
			$images_number = false;
			if (!empty($this->css_images[preg_replace("/(x|y)/", "$1l", $sprite)])) {
				$images_number = count($this->css_images[$sprite]['images']) && count($this->css_images[preg_replace("/(x|y)/", "$1l", $sprite)]['images']);
			} else {
				$images_number = empty($this->css_images[$sprite]) ? 0 : count($this->css_images[$sprite]['images']) > 1;
			}

			if ($images_number || $type == 4) {

				$this->css_images[$sprite]['x'] = 0;
				$this->css_images[$sprite]['y'] = 0;
/* recount x/y sizes for repeat-x / repeat-y / repeat icons -- we can have duplicated dimensions */
				$counted_images = array();
				if (!empty($this->css_images[$sprite]['images'])) {
					foreach ($this->css_images[$sprite]['images'] as $key => $image) {
						$filename = $image[0];
						if (($type == 1 || $type == 2 || $type == 5 || $type == 6) && empty($counter_images[$filename])) {
							$width = $image[1];
							$height = $image[2];
							$final_x = $image[3];
							$final_y = $image[4];
							$shift_x = $image[5];
							$shift_y = $image[6];
							switch ($type) {
								case 6:
/* glue image to the bottom edge */
									$this->css_images[$sprite]['images'][$key][3] = $this->css_images[$sprite]['x'] + $final_x;
									$this->css_images[$sprite]['images'][$key][4] = $this->css_images[$sprite]['y'] - $height;
									$this->css_images[$sprite]['images'][$key][5] = $final_x;
									$this->css_images[$sprite]['x'] += $width + $final_x + $shift_x + ($this->extra_space ? 5 : 0);
									if ($height > $this->css_images[$sprite]['y']) {
										$shift = $this->css_images[$sprite]['y'] - $height;
										$this->css_images[$sprite]['y'] = $height;
/* move current images futher to the new bottom */
										foreach ($this->css_images[$sprite]['images'] as $k => $i) {
											$this->css_images[$sprite]['images'][$k][4] += $shift;
										}
									}
									break;
								case 5:
/* glue image to the right edge */
									$this->css_images[$sprite]['images'][$key][3] = $this->css_images[$sprite]['x'] - $width;
									$this->css_images[$sprite]['images'][$key][4] = $this->css_images[$sprite]['y'] + $final_y;
									$this->css_images[$sprite]['images'][$key][6] = $final_y;
									$this->css_images[$sprite]['y'] += $height + $final_y + $shift_y + ($this->extra_space ? 5 : 0);
									if ($width > $this->css_images[$sprite]['x']) {
										$shift = $this->css_images[$sprite]['x'] - $width;
										$this->css_images[$sprite]['x'] = $width;
/* move current images futher to the new right */
										foreach ($this->css_images[$sprite]['images'] as $k => $i) {
											$this->css_images[$sprite]['images'][$k][3] += $shift;
										}
									}
									break;
								case 1:
									$this->css_images[$sprite]['images'][$key][3] = 0;
									$this->css_images[$sprite]['images'][$key][4] = $this->css_images[$sprite]['y'] + $final_y;
									$this->css_images[$sprite]['images'][$key][6] = $final_y;
									$this->css_images[$sprite]['x'] = $this->SCM($width, $this->css_images[$sprite]['x'] ? $this->css_images[$sprite]['x'] : 1);
									$this->css_images[$sprite]['y'] += $height + $final_y + $shift_y;
									break;
								case 2:
									$this->css_images[$sprite]['images'][$key][3] = $this->css_images[$sprite]['x'] + $final_x;
									$this->css_images[$sprite]['images'][$key][4] = 0;
									$this->css_images[$sprite]['images'][$key][5] = $final_x;
									$this->css_images[$sprite]['x'] += $width + $final_x + $shift_x;
									$this->css_images[$sprite]['y'] = $this->SCM($height, $this->css_images[$sprite]['y'] ? $this->css_images[$sprite]['y'] : 1);
									break;
							}
							$counter_images[$filename] = 1;
						}
					}
				}
				$this->css_images[$sprite]['addon_y'] = 0;
				$this->css_images[$sprite]['addon_x'] = 0;
				if ($type == 1) {
					$no_dimensions = preg_replace("/x/", "xl", $sprite);
/* add to the end of Sprite repeat-x w/o dimensions */
					if (!empty($this->css_images[$no_dimensions]) && count($this->css_images[$no_dimensions]['images'])) {
						foreach ($this->css_images[$no_dimensions]['images'] as $image) {
							if (!empty($image)) {
								continue;
							}
						}
						$final_y = empty($image[4]) ? 0 : $image[4];
						$image[3] = 0;
						$image[4] = (empty($image[4]) ? 0 : $image[4]) + $this->css_images[$sprite]['y'];
						$this->css_images[$sprite]['images'][] = $image;
						$this->css_images[$sprite]['x'] = !empty($image[1]) && $image[1] > $this->css_images[$sprite]['x'] ? $this->SCM($image[1], $this->css_images[$sprite]['x']) : $this->css_images[$sprite]['x'];
						$this->css_images[$sprite]['y'] += (empty($image[2]) ? 0 : $image[2]) + $final_y;
						unset($this->css_images[$no_dimensions][0]);
					}
					$counted_images = array();
					$no_repeat = preg_replace("/x/", "", $sprite);
					if (!empty($this->css_images[$no_repeat]['images'])) {
/* try to find small no-repeat image to put before all repeat-x ones */
						foreach ($this->css_images[$no_repeat]['images'] as $key => $image) {
							if ($image[1] <= $this->css_images[$sprite]['x'] && empty($counted_images[$image[0]])) {
								$counted_images[$image[0]] = 1;
								$final_y = $image[4];
								$image[3] = 0;
								$image[4] = $this->css_images[$sprite]['addon_y'] + $final_y + $image[6];
								$this->css_images[$sprite]['addon_y'] += $image[2] + $final_y + $image[6] + ($this->extra_space ? 5 : 0);
								$this->css_images[$sprite]['y'] += $image[2] + $final_y + $image[6] + ($this->extra_space ? 5 : 0);
								$image[] = 1;
								$this->css_images[$sprite]['images'][] = $image;
								unset($this->css_images[$no_repeat]['images'][$key]);
							}
						}
					}
				}
				if ($type == 2) {
					$no_dimensions = preg_replace("/y/", "yl", $sprite);
/* add to the end of Sprite repeat-x w/o dimensions */
					if (!empty($this->css_images[$no_dimensions]) && count($this->css_images[$no_dimensions]['images'])) {
						foreach ($this->css_images[$no_dimensions]['images'] as $image) {
							if (!empty($image)) {
								continue;
							}
						}
						$final_x = $image[3];
						$image[3] += $this->css_images[$sprite]['x'];
						$image[4] = 0;
						$this->css_images[$sprite]['images'][] = $image;
						$this->css_images[$sprite]['x'] += $image[1] + $final_x;
						$this->css_images[$sprite]['y'] = !empty($image[2]) && $image[2] > $this->css_images[$sprite]['y'] ? $this->SCM($image[2], $this->css_images[$sprite]['y']) : $this->css_images[$sprite]['y'];
						unset($this->css_images[$no_dimensions][0]);
					}
					$counted_images = array();
					$no_repeat = preg_replace("/y/", "", $sprite);
					if (!empty($this->css_images[$no_repeat]['images'])) {
/* try to find small no-repeat image to put before all repeat-y ones */
						foreach ($this->css_images[$no_repeat]['images'] as $key => $image) {
							if ($image[2] <= $this->css_images[$sprite]['y'] && empty($counted_images[$image[0]])) {
								$counted_images[$image[0]] = 1;
								$final_x = $image[3];
								$image[3] = $this->css_images[$sprite]['addon_x'] + $final_x + $image[5];
								$this->css_images[$sprite]['addon_x'] += $image[1] + $final_x + $image[5] + ($this->extra_space ? 5 : 0);
								$this->css_images[$sprite]['x'] += $image[1] + $final_x + $image[5] + ($this->extra_space ? 5 : 0);
								$image[] = 1;
								$this->css_images[$sprite]['images'][] = $image;
								unset($this->css_images[$no_repeat]['images'][$key]);
							}
						}
					}
				}
				$merged_selector = array();
/* need to count placement for each image in array */
				if ($type == 4) {
					$icons_sprite = preg_replace("/webo/", "weboi", $sprite);
					$icons = empty($this->css_images[$icons_sprite]) ? array() : $this->css_images[$icons_sprite];
					$this->css_images[$sprite] = $this->sprites_placement($this->css_images[$sprite], $icons, $mode);
					$sprite_right = preg_replace("/webo/", "webor", $sprite);
/* add right Sprite to the right top corner */
					if (@is_file($sprite_right)) {
						$this->css_images[$sprite]['y'] += $this->css_images[$sprite_right]['y'];
						$this->css_images[$sprite]['addon_y'] += $this->css_images[$sprite_right]['y'];
/* change background image for the right Sprite selectors */
						foreach ($this->css_images[$sprite_right]['images'] as $image) {
							$merged_selector[$image[7]] = (empty($merged_selector[$image[7]]) ? "" : $merged_selector[$image[7]] . ",") . $image[8];
						}
					}
					$sprite_bottom = preg_replace("/webo/", "webob", $sprite);
/* add bottom Sprite to the bottom left corner */
					if (@is_file($sprite_bottom)) {
						$this->css_images[$sprite]['x'] += $this->css_images[$sprite_bottom]['x'];
						$this->css_images[$sprite]['addon_x'] += $this->css_images[$sprite_bottom]['x'];
/* change background image for the right Sprite selectors */
						$merged_selector = array();
						foreach ($this->css_images[$sprite_bottom]['images'] as $image) {
							$merged_selector[$image[7]] = (empty($merged_selector[$image[7]]) ? "" : $merged_selector[$image[7]] . ",") . $image[8];
						}
					}
				}
				if (!$file_exists) {
					$sprite_raw = @imagecreatetruecolor($this->css_images[$sprite]['x'], $this->css_images[$sprite]['y']);
				}
				if (!empty($sprite_raw) || $file_exists) {
/* for final sprite */
					if (!$file_exists) {
/* handling 32bit colors in PNG */
						@imagealphablending($sprite_raw, false);
						@imagesavealpha($sprite_raw, true);
						$this->background = @imagecolorallocatealpha($sprite_raw, 255, 255, 255, 127);
/* fill sprite with white color */
						@imagefilledrectangle($sprite_raw, 0, 0, $this->css_images[$sprite]['x']-1, $this->css_images[$sprite]['y']-1, $this->background);
/* make this color transparent */
						@imagecolortransparent($sprite_raw, $this->background);
					}
/* loop in all given CSS images */
					foreach ($this->css_images[$sprite]['images'] as $image_key => $image) {

						$filename = empty($image[0]) ? '' : $image[0];
						$width = empty($image[1]) ? 0 : $image[1];
						$height = empty($image[2]) ? 0 : $image[2];
						$final_x = empty($image[3]) ? 0 : $image[3];
						$final_y = empty($image[4]) ? 0 : $image[4];
/* re-use of shifts -- to restore initial background-position */
						$shift_x = empty($image[5]) ? 0 : $image[5];
						$shift_y = empty($image[6]) ? 0 : $image[6];
						$import = empty($image[7]) ? '' : $image[7];
						$key = empty($image[8]) ? '' : $image[8];
/* for added to repeat-x / repeat-y image with no-repeat */
						$added = empty($image[9]) ? 0 : $image[9];
/* remember existing background */
						$this->css_images[$sprite]['images'][$image_key][10] = empty($this->css->css[$import][$key]['background']) ? '' : $this->css->css[$import][$key]['background'];
						$this->css_images[$sprite]['images'][$image_key][11] = empty($this->css->css[$import][$key]['background-image']) ? '' : $this->css->css[$import][$key]['background-image'];
						if (!$added || $type == 4) {
							$final_x += $this->css_images[$sprite]['addon_x'];
							$final_y += $this->css_images[$sprite]['addon_y'];
						}
/* try to detect duplicates in this Sprite */
						$image_used = 0;
						foreach ($this->css_images[$sprite]['images'] as $image) {
							if (!empty($image[7]) && !empty($image[8]) && !empty($this->media[$image[7]][$image[8]]) &&
								!empty($this->media[$image[7]][$image[8]]['background']) &&
								!empty($image[0]) && $image[0] == $filename &&
								!empty($this->css->css[$image[7]][$image[8]]['background'])) {
								$image_used = 1;
								$background = $this->css->css[$image[7]][$image[8]]['background'];
							}
						}
/* leave rules for IE6 */
						if ($this->no_ie6) {
							if (!empty($this->css->css[$import][$key]) && !empty($this->css->css[$import][$key]['background'])) {
/* should we preserve current IE6 hack for background? */
								$this->css->css[$import]["* html " . $key]['background'] = $this->css->css[$import][$key]['background'];
							}
						}

						if (!$image_used) {
							if (empty($im)) {
/* flag semi-transparency, 0 - disabled, 1 - enabled */
								$this->alpha = 0;
/* try to copy initial image into sprite */
								switch (strtolower(preg_replace("/.*\./", "", $filename))) {
									case 'ico':
										$im = $this->imagecreatefromico($filename, $width, $height);
										break;
									case 'gif':
										$im = @imagecreatefromgif($filename);
										break;
									case 'png':
										$im = @imagecreatefrompng($filename);
										if ($im && @imagecolorstotal($im) > 256) {
											$this->alpha = 1;
										}
										break;
									case 'jpg':
									case 'jpeg':
										$im = @imagecreatefromjpeg($filename);
										break;
									case 'bmp':
										$im = @imagecreatefromwbmp($filename);
										break;
									default:
										$im = @imagecreatefromxbm($filename);
										break;
								}
							}
/* some images can have incorrect extension */
							if (empty($im) && is_file($filename)) {
								$im = @imagecreatefrompng($filename);
								if ($im && @imagecolorstotal($im) > 256) {
									$this->alpha = 1;
								}
								if (empty($im)) {
									$im = @imagecreatefromjpeg($filename);
									$this->alpha = 0;
								}
								if (empty($im)) {
									$im = @imagecreatefromgif($filename);
									$this->alpha = 0;
								}
								if (empty($im)) {
									$im = @imagecreatefromwbmp($filename);
									$this->alpha = 0;
								}
								if (empty($im)) {
									$im = @imagecreatefromxbm($filename);
									$this->alpha = 0;
								}
								if (empty($im)) {
									$im = $this->imagecreatefromico($filename, $width, $height);
									$this->alpha = 0;
								}
							}
							$css_top = $css_left = $css_releat = '';
							if (!empty($im) || $file_exists) {
								switch ($type) {
/* 0 100% case */
									case 6:
										$css_top = '100%';
										$css_left = -$final_x + $shift_x;
										$css_repeat = 'no-repeat';
										if (!$file_exists) {
											$this->imagecopymerge_alpha($sprite_raw, $im, $final_x, $this->css_images[$sprite]['y'] - $height, 0, 0, $width, $height);
										}
										break;
/* 100% 0 case */
									case 5:
										$css_top = -$final_y + $shift_y;
										$css_left = '100%';
										$css_repeat = 'no-repeat';
										if (!$file_exists) {
											$this->imagecopymerge_alpha($sprite_raw, $im, $this->css_images[$sprite]['x'] - $width, $final_y, 0, 0, $width, $height);
										}
										break;
/* the most complicated case */
									case 4:
										$css_left = -$final_x + $shift_x;
										$css_top = -$final_y + $shift_y;
										$css_repeat = 'no-repeat';
										if (!$file_exists) {
											$this->imagecopymerge_alpha($sprite_raw, $im, -$css_left + ($added || $shift_x > 0 ? $shift_x : 0), -$css_top + ($added || $shift_y > 0 ? $shift_y : 0), 0, 0, $width - ($final_x < 0 ? $final_x : 0), $height - ($final_y < 0 ? $final_y : 0));
										}
										break;
/* repeat-y */
									case 2:
										$css_left = -$final_x;
										$css_top = 0;
										if ($added) {
											$css_repeat = 'no-repeat';
										} else {
											$css_left += $shift_x;
											$css_repeat = 'repeat-y';
										}
										if (!$file_exists) {
											$this->imagecopymerge_alpha($sprite_raw, $im, $final_x, $final_y, 0, 0, $width, $height);
											$final_y = $height;
/* semi-fix for bug with different height of repeating images, thx to xstroy */
											while ($final_y < $this->css_images[$sprite]['y'] && !$added) {
												$this->imagecopymerge_alpha($sprite_raw, $im, $final_x, $final_y, 0, 0, $width, $height);
												$final_y += $height;
											}
										}
										break;
/* repeat-x */
									case 1:
										$css_left = 0;
										$css_top = -$final_y;
										if ($added) {
											$css_repeat = 'no-repeat';
										} else {
											$css_top += $shift_y;
											$css_repeat = 'repeat-x';
										}
										if (!$file_exists) {
											$this->imagecopymerge_alpha($sprite_raw, $im, $final_x, $final_y, 0, 0, $width, $height);
											$final_x = $width;
/* semi-fix for bug with different width of repeating images, thx to xstroy */
											while ($final_x < $this->css_images[$sprite]['x'] && !$added) {
												$this->imagecopymerge_alpha($sprite_raw, $im, $final_x, $final_y, 0, 0, $width, $height);
												$final_x += $width;
											}
										}
										break;

								}
								if (!empty($im)) {
									@imagedestroy($im);
									unset($im);
								}
							}

							if (!empty($this->css->css[$import][$key]['background-color']) ||
								!empty($css_left) ||
								!empty($css_top) ||
								!empty($this->css->css[$import][$key]['background-attachement']) ||
								!empty($this->css->css[$import][$key]['background'])) {
/* update current styles in initial selector */
									$this->css->css[$import][$key]['background'] =
										trim(((!empty($this->media) &&
										!empty($this->media[$import][$key]['background-color']) &&
										$this->media[$import][$key]['background-color'] != 'transparent') ?
											$this->media[$import][$key]['background-color'] : '') .
										' ' .
										(empty($css_left) || $css_left == 'left' ?
											'0' : ($css_left . (is_numeric($css_left) ? 'px' : ''))) .
										' ' .
										(empty($css_top) || $css_top == 'top' ?
											'0' : ($css_top . (is_numeric($css_top) ? 'px' : ''))) .
										' ' .
										(empty($css_repeat) ? '' : $css_repeat) .
										' ' .
										(!empty($this->media) &&
											!empty($this->media[$import][$key]['background-attachement']) ?
											$this->media[$import][$key]['background-attachement'] : ''));
							}

						} else {
/* or just copy existing styles, save initial background-color, need also save background-attachement... */
							if (!in_array($background{0}, array('-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'))) {
								$background = substr($background, strpos($background, " ") + 1);
							}
							$background = (!empty($this->media) &&
								!empty($this->media[$import][$key]['background-color']) &&
								$this->media[$import][$key]['background-color'] != 'transparent' ?
									trim($this->media[$import][$key]['background-color']) . ' ' : '') . $background;
							$this->css->css[$import][$key]['background'] = $background;
						}
/* update array with chosen selectors -- to mark this image as used */
						$this->media[$import][$key]['background'] = 1;
						$merged_selector[$import] = (empty($merged_selector[$import]) ? '' : $merged_selector[$import] . ",") . $key;
						$tags = empty($this->media[$import][$key]['tags']) ? $key :
							$this->media[$import][$key]['tags'];
/* unset overwritten values, use remembered multiple selectors */
						unset($this->css->css[$import][$tags]['background-image']);
						unset($this->css->css[$import][$tags]['background-color']);
						unset($this->css->css[$import][$tags]['background-position']);
						unset($this->css->css[$import][$tags]['background-repeat']);
						unset($this->css->css[$import][$tags]['background-attachement']);
						unset($this->css->css[$import][$tags]['background-image']);
					}
					if (!$file_exists) {
/* try to add right and bottom Sprites to the main one */
						if ($type == 4) {
							if (is_file($sprite_right)) {
								$im = @imagecreatefrompng($sprite_right);
								$this->imagecopymerge_alpha($sprite_raw, $im, $this->css_images[$sprite]['x'] - $this->css_images[$sprite_right]['x'], 0, 0, 0, $this->css_images[$sprite_right]['x'], $this->css_images[$sprite_right]['y']);
							}
							if (is_file($sprite_bottom)) {
								$im = @imagecreatefrompng($sprite_bottom);
								$this->imagecopymerge_alpha($sprite_raw, $im, 0, $this->css_images[$sprite]['y'] - $this->css_images[$sprite_bottom]['y'], 0, 0, $this->css_images[$sprite_bottom]['x'], $this->css_images[$sprite_bottom]['y']);
							}
						}
/* output final sprite */
						if ($this->truecolor_in_jpeg) {
							$sprite = preg_replace("/png$/", "jpg", $sprite);
							try {
								@imagejpeg($sprite_raw, $sprite, 80);
							} catch (Exception $e) {
								$failed = 1;
							}
						} else {
							try {
								@imagepng($sprite_raw, $sprite, 9, PNG_ALL_FILTERS);
							} catch (Exception $e) {
								try {
									@imagepng($sprite_raw, $sprite, 9);
								} catch (Exception $e) {
									try {
										@imagepng($sprite_raw, $sprite);
									} catch (Exception $e) {
										$failed = 1;
									}
								}
							}
						}
						@imagedestroy($sprite_raw);
/* additional optimization via smush.it */
						if (@is_file($sprite)) {
							$this->smushit($this->current_dir . $sprite);
/* upload file to CDN */
							if (!empty($this->ftp_access)) {
								$this->lib->upload_cdn($this->current_dir . $sprite,
									$this->current_dir,
									$this->ftp_access,
									$this->truecolor_in_jpeg ? 'image/jpeg' : 'image/png',
									$this->http_host);
							}
						}
					}
/* don't touch webor / webob Sprites -- they will be included into the main one */
					if (@is_file($sprite)) {
/* add selector with final sprite */
						foreach ($merged_selector as $import => $keys) {
							$this->css->css[$import][$keys]['background-image'] = 'url('.
								preg_replace("/webo[rb]/", "webo",
								substr($sprite, 0, strlen($sprite) - 3) . 
								(!$this->no_rewrite && $this->cache_images ? 'wo' . time() . '.' : '') .
								substr($sprite, strlen($sprite) - 3) .
								($this->no_rewrite && $this->cache_images && $this->cache_images_htaccess ? '?' . time() : '')) .')';
						}
					}
					if (count($this->css_images[$sprite]['images'])) {
/* finish deal with CSS */
						foreach ($this->css_images[$sprite]['images'] as $image) {
							$import = empty($image[7]) ? '' : $image[7];
							$key = empty($image[8]) ? '' : $image[8];
/* delete initial CSS rules only on success */
							if (is_file($sprite)) {
								unset($this->css->css[$import][$key]['background-color'], $this->css->css[$import][$key]['background-repeat'], $this->css->css[$import][$key]['background-attachement'], $this->css->css[$import][$key]['background-position']);
/* otherwise restore background-image */
							} else {
								if (!empty($image[10])) {
									$this->css->css[$import][$key]['background'] = $image[10];
								}
								if (!empty($image[11])) {
								$this->css->css[$import][$key]['background-image'] = $image[11];
								}
							}
						}
					}
				}

			}

		}
		if (strpos($sprite, 'webor') !== false && strpos($sprite, 'webob') !== false) {
/* unset done arrays, not for webor/webob cases - they can be used in the future */
			unset($this->css_images[$sprite]);
		}
	}

/**
 * Thanks to ZeBadger for original example, and Davide Gualano for pointing me to it
 * Original at http://it.php.net/manual/en/function.imagecreatefromgif.php#59787
**/
	function is_animated_gif ($filename) {
		$raw = @file_get_contents($filename);
		$offset = 0;
		$frames = 0;
		while ($frames < 2) {
			$where1 = strpos($raw, "\x00\x21\xF9\x04", $offset);
			if ($where1 === false) {
				break;
			}
			$offset = $where1 + 1;
			$where2 = strpos( $raw, "\x00\x2C", $offset );
			if ($where2 === false) {
				break;
			}
			if ($where1 + 8 == $where2) {
				$frames++;
			}
			$offset = $where2 + 1;
		}

		return $frames > 1;
	}
/* generic download function */
	function download_file ($remote, $local, $referer = "") {
/* check for curl */
		if (function_exists('curl_init')) {
/* try to download image */
			$ch = curl_init($remote);
			$fp = @fopen($local, "w");
			if ($fp && $ch) {
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Web Optimizer; Faster than Lightning; http://web-optimizer.us/) Firefox 3.5.2");
				if (!empty($referer)) {
					curl_setopt($ch, CURLOPT_REFERER, $referer);
				}
				curl_exec($ch);
				curl_close($ch);
				fclose($fp);
			}
		}
	}
	function SegReg($h, $str, $s, $e) {
		if ($h >= $s && $h <= $e)
		return $str.(ord($h) - ord($s));
		return false;
	}
/* image optimization via WEBO + bolk algorithm */
	function webolk ($file) {
		$tmp_file = $file . '.tmp';
		$im = null;
		$jpeg = 0;
		switch (strtolower(preg_replace("/.*\./", "", $file))) {
			case 'gif':
				if (!$this->is_animated_gif($file)) {
					$im = @imagecreatefromgif($file);
				}
				break;
			case 'png':
				$im = @imagecreatefrompng($file);
				break;
			case 'jpg':
			case 'jpeg':
				$im = @imagecreatefromjpeg($file);
				$jpeg = 1;
				break;
			case 'bmp':
				$im = @imagecreatefromwbmp($file);
				break;
			default:
				$im = 1;
				break;
		}
		if (empty($im)) {
			$im = @imagecreatefrompng($file);
			if (empty($im)) {
				$im = @imagecreatefromjpeg($file);
				$jpeg = 1;
			}
			if (empty($im)) {
				if (!$this->is_animated_gif($file)) {
					$im = @imagecreatefromgif($file);
				}
			}
			if (empty($im)) {
				$im = @imagecreatefromwbmp($file);
			}
			if (empty($im)) {
				$im = @imagecreatefromxbm($file);
			}
		}
		if (!empty($im)) {
/* PCR from bolk, more info as bolknote.ru */
			if ($jpeg) {
				@imagedestroy($im);
				$fp = @fopen($file, 'r');
				$fd = @fopen($tmp_file, 'w');
				@fwrite ($fd, "\xFF\xD8"); /* Write JPEG header */
				@fread($fp, 2);
				for ($sum = 0; !@feof ($fp); ) {
					$handle = @fread ($fp, 2);
					$seg = join ('', unpack("H*", $handle));
					$h = $handle[1];
					if ($h == "\x01" || $h >= "\xD0" && $h <= "\xD7") {
						continue;
					}
					if ($h == "\xDA") {
						break;
					}
					$t = array ("\xC4" => "DHT", "\xC8" => 'JPG', "\xCC" => 'DAC', "\xD8" => 'SOI', "\xD9" => 'EOI', "\xDA" => 'SOS', "\xDB" => 'DQT', "\xDC" => 'DNL', "\xDD" => 'DRI', "\xFE" => 'COM');
					if (isset($type[$h])) {
						$type = $t[$h];
					} elseif (($v = $this->SegReg($h, 'SOF', "\xC0", "\xCA")) !== false) {
						$type = $v;
					} elseif (($v = $this->SegReg($h, 'RST', "\xD0", "\xD7")) !== false) {
						$type = $v;
					} elseif (($v = $this->SegReg($h, 'APP', "\xE0", "\xEF")) !== false) {
						$type = $v;
					} else {
						$type = 'UNKNOWN';
					}
					$len = join('', unpack ('n', $str = @fread ($fp, 2)));
					if ($type == 'UNKNOWN' || $h >= "\xE0" && $h <= "\xEF" || $h == "\xFE") {
						@fseek ($fp, $len - 2, SEEK_CUR);
						$sum += $len + 2;
					} else {
						@fwrite ($fd, $handle);
						@fwrite ($fd, $str.fread ($fp, $len - 2));
					}
				}
				if (!feof($fp)) {
					@fwrite ($fd, "\xFF\xDA"); /* Write SOS signature */
					while (!feof($fp)) { /* Copy content */
						@fwrite ($fd, fread ($fp, 4096));
					}
				}
				@fclose ($fp);
				@fclose ($fd);
			} else {
				try {
					@imagepng($im, $tmp_file, 9, PNG_ALL_FILTERS);
				} catch (Exception $e) {
					try {
						@imagepng($im, $tmp_file, 9);
					} catch (Exception $e) {
						@imagepng($im, $tmp_file);
					}
				}
			}
			if (@filesize($file) > @filesize($tmp_file)) {
				@copy($file . '.backup', $file);
				@copy($tmp_file, $file);
			}
			@unlink($tmp_file);
		}
	}
/* image optimization via punypng.com */
	function punypng ($file, $recursion = 0) {
		$tmp_file = $file . ".tmp";
		$this->download_file('http://www.gracepointafterfive.com/punypng/api/optimize?img=http%3A%2F%2F' .
			urlencode($_SERVER['HTTP_HOST']) . '%2F' .
			urlencode(str_replace($this->website_root, "", $file)) .
			'&key=' . $this->punypng_key, $tmp_file);
		if (is_file($tmp_file)) {
			$str = @file_get_contents($tmp_file);
			if (!preg_match("/['\"]error['\"]/i", $str) && @filesize($tmp_file)) {
				$optimized = preg_replace("/\\\\\//", "/", preg_replace("/['\"].*/", "", preg_replace("/.*optimized_url['\"]:\s?['\"]/", "", $str)));
				if (!@is_file($file . '.backup')) {
					@copy($file, $file . '.backup');
				}
				$this->download_file($optimized, $file, 'http://www.gracepointafterfive.com/');
				$content = @file_get_contents($file);
				if (empty($content) || strpos($content, "DOCTYPE") || strpos($content, 'Error Code')) {
					@copy($file . '.backup', $file);
				} else {
					@unlink($file . '.backup');
				}
			}
			@unlink($tmp_file);
		}
		if (!@filesize($file)) {
			if ($recursion < 10) {
				sleep(1);
/* if can't optimize file - try once more */
				@copy($file . '.backup', $file);
				$this->punypng($file, ++$recursion);
/* just restore backed up copy */
			} else {
				@copy($file . '.backup', $file);
				@unlink($file . '.backup');
			}
		}
	}
/* image optimization via smush.it */
	function smushit ($file, $recursion = 0) {
		$tmp_file = $file . ".tmp";
		$this->download_file("http://www.smushit.com/ysmush.it/ws.php?img=http%3A%2F%2F" .
			urlencode($_SERVER['HTTP_HOST']) . '%2F' .
			urlencode(str_replace($this->website_root, "", $file)), $tmp_file);
		if (@is_file($tmp_file)) {
			$str = @file_get_contents($tmp_file);
			if ((!preg_match("/['\"]error['\"]/i", $str) || strpos($str, 'No savings')) && strlen($str)) {
				$optimized = preg_replace("/\\\\\//", "/", preg_replace("/['\"].*/", "", preg_replace("/.*dest['\"]:['\"]/", "", $str)));
				if (!@is_file($file . '.backup')) {
					@copy($file, $file . '.backup');
				}
				$this->download_file($optimized, $file, 'http://www.smushit.com/ysmush.it/');
				$content = @file_get_contents($file);
				if (empty($content) || strpos($content, "DOCTYPE") || strpos($content, 'Error Code')) {
					@copy($file . '.backup', $file);
				} else {
					@unlink($file . '.backup');
				}
			}
			@unlink($tmp_file);
		}
		if (!@filesize($file)) {
			if ($recursion < 10) {
				sleep(1);
/* if can't optimize file - try once more */
				@copy($file . '.backup', $file);
				$this->smushit($file, ++$recursion);
/* just restore backed up copy */
			} else {
				@copy($file . '.backup', $file);
				@unlink($file . '.backup');
			}
		}
	}
/* calculate smallest common multiple, NOK */
	function SCM ($a, $b) {
		$return = $a * $b;
		while($a && $b) {
			if ($a >= $b) {
				$a = $a % $b;
			} else {
				$b = $b % $a;
			}
		}
		return $return / ($a + $b);
	}
/** 
 * PNG ALPHA CHANNEL SUPPORT for imagecopymerge(); 
 * This is a function like imagecopymerge but it handle alpha channel well
 * A fix to get a function like imagecopymerge WITH ALPHA SUPPORT 
 * Main script by aiden dot mail at freemail dot hu 
 * Transformed to imagecopymerge_alpha() by rodrigo dot polo at gmail dot com
 **/ 
	function imagecopymerge_alpha ($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct = 100){
		switch ($this->alpha) {
			case 1:
				$pct /= 100;
/* Get image width and height */
				$w = @imagesx($src_im);
				$h = @imagesy($src_im);
/* Turn alpha blending off */
				@imagealphablending($src_im, false);
/* Find the most opaque pixel in the image (the one with the smallest alpha value) */
				$minalpha = 127;
				for( $x = 0; $x < $w; $x++ ) {
					for( $y = 0; $y < $h; $y++ ) {
						$alpha = (@imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
						if ($alpha < $minalpha) {
							$minalpha = $alpha;
						}
					}
				}
/* loop through image pixels and modify alpha for each */
				for( $x = 0; $x < $w; $x++ ) {
					for( $y = 0; $y < $h; $y++ ) {
/* get current alpha value (represents the TRANSPARENCY!) */
						$colorxy = @imagecolorat($src_im, $x, $y);
						$alpha = ($colorxy >> 24) & 0xFF;
/* calculate new alpha */
						if ($minalpha !== 127) {
							$alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
						} else {
							$alpha += 127 * $pct;
						}
/* get the color index with new alpha */
						$alphacolorxy = @imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
/* set pixel with the new color + opacity */
						if (!@imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
							return false;
						}
					}
				}
/* skip all logic for non-semitransparent images */
			case 0:
/* The image copy */
				@imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
				break;
		}
	}

/** 
 * Functions to operate with ICO files
 * Based on Jakub Vrana's code
 * Code from http://printf.ru/mediawiki/index.php?title=imagecreatefromico
 **/

	function imagecreatefromicoraw($data, $width, $height, $bitdepth) {
		$image = imagecreatetruecolor($width, $height);
		imagefilledrectangle($image, 0, 0, $width - 1, $height - 1,
			imagecolorallocate($image, 255, 255, 255));
		if ($bitdepth < 9) {
			$cs = array();
			for ($i = 0; $i < pow(2, $bitdepth); ++$i) {
				$c = @unpack('Cb/Cg/Cr', substr($data, 4 * $i, 3));
				$cs[] = imagecolorallocate($image, $c['r'], $c['g'], $c['b']);
			}
			$data = substr($data, 4 * pow(2, $bitdepth));
		}
		$stride = ceil($width * $bitdepth / 32);
		for ($y = 0; $y < $height; ++$y) {
			for ($x = 0; $x < $width; ++$x) {
				$offset = 32 * $y * $stride + $x * $bitdepth;
				$transparent = ord($data { 4 * $height * $stride + 4 * $y *
					ceil($width / 32) + floor($x / 8) }) & (1 << (7 - $x % 8));
				if ($bitdepth < 9) {
					$c = $cs[ord($data { floor($offset / 8) }) >>
						(8 - $bitdepth - $offset % 8) & (pow(2, $bitdepth) - 1)];
				} elseif ($bitdepth == 16) {
					$cs = @unpack('nbgr', substr($data, $offset / 8, 2));
					$c = imagecolorallocate($image,
						round(255 / 31 * ($cs['bgr'] & 31)),
						round(255 / 63 * (($cs['bgr'] >> 5) & 63)),
						round(255 / 31 * ($cs['bgr'] >> 11)));
				} elseif ($bitdepth == 32) {
					$cs = @unpack('Cb/Cg/Cr/Ca', substr($data, $offset / 8, 4));
					$c = imagecolorallocate($image,
						255 - $cs['a'] + round($cs['r'] * $cs['a'] / 255),
						255 - $cs['a'] + round($cs['g'] * $cs['a'] / 255),
						255 - $cs['a'] + round($cs['b'] * $cs['a'] / 255));
				} else {
					$cs = @unpack('Cb/Cg/Cr', substr($data, $offset / 8, 3));
					$c = imagecolorallocate($image, $cs['r'], $cs['g'], $cs['b']);
				}
				if (!$transparent) imagesetpixel($image, $x, $height - $y - 1, $c);
			}
		}
		return $image;
	}

	function imagecreatefromico($filename, $width = 16, $height = 16) {
		if ($data = @file_get_contents($filename)) {
			list(, $count) = @unpack('v', substr($data, 4, 2));
			$icon = null;
			for ($i = 0; $i < $count; ++$i) {
				$meta = @unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/' .
					'vbitdepth/Vlength/Voffset', substr($data, 16 * $i + 6, 16));
				if ($meta) {
					$meta_ = @unpack('vibitdepth/Vcompression',
						substr($data, $meta['offset'] + 14, 6));
					if ($meta_) {
						$meta += $meta_;
						if ($icon) {
							if ($icon['width'] == $width &&
								$icon['height'] == $height) {
								if ($meta['width'] != $width ||
									$meta['height'] != $height ||
									$meta['ibitdepth'] < $icon['ibitdepth']) {
									continue;
								}
							} elseif ($meta['width'] < $icon['width'] ||
								$meta['height'] < $icon['height'] ||
								$meta['ibitdepth'] < $icon['ibitdepth']) {
								continue;
							}
						}
						$icon = $meta;
					}
				}
			}
			if ($icon) {
				$image = $this->imagecreatefromicoraw(substr($data,
					$icon['offset'] + 40, $icon['length']),
					$icon['width'], $icon['height'], $icon['ibitdepth']);
				if ($icon['width'] != $width || $icon['height'] != $height) {
					$image_ = imagecreatetruecolor($width, $height);
					imagecopyresampled($image_, $image, 0, 0, 0, 0, $width,
						$height, imagesx($image), imagesy($image));
					return $image_;
				}
				return $image;
			}
		}
		return false;
	}

}

?>