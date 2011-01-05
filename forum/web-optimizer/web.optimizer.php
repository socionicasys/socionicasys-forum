<?php
// ==============================================================================================
// Licensed under the WEBO license (LICENSE.txt)
// ==============================================================================================
// @author     WEBO Software (http://www.webogroup.com/)
// @version    1.0.0
// @copyright  Copyright &copy; 2009-2010 WEBO Software. All Rights Reserved
// ==============================================================================================

$basepath = realpath(dirname(__FILE__)) . '/';
$compress_options['php'] = substr(phpversion(), 0, 1);

if (!class_exists('compressor', false)) {
	require_once($basepath . "controller/compressor.php");
}
/* Include this for path getting help */
if (!class_exists('compressor_view', false)) {
	require_once($basepath . "libs/php/view.php");
}

/* We need to know the config */
require($basepath . "config.webo.php");

/* buffer input stream or not */
$compress_options['buffered'] = empty($not_buffered) ? 1 : 0;

/* Con. the view library */
$view = new compressor_view();

/* create libraries array -- include them only if we are really compressing */
$libraries = array();

/* Include this for CSS Sprites generating */
$libraries['css_sprites'] = 'css.sprites.php';
$libraries['css_sprites_optimize'] = 'css.sprites.optimize.php';
/* JSMin */
$libraries['JSMin'] = 'jsmin5.php';
/* Dean Edwards Packer */
$libraries['JavaScriptPacker'] = 'packer5.php';
/* CSS Tidy */
$libraries['csstidy'] = 'class.csstidy.php';
/* YUI Compressor */
$libraries['YuiCompressor'] = 'class.yuicompressor.php';

/* Con. the compression controller */
$web_optimizer = new web_optimizer(array(
	'view' => $view,
	'options' => $compress_options,
	'libraries' => $libraries,
	'no_cache' => empty($no_cache) ? false : $no_cache)
);
?>