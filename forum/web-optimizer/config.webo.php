<?php
#########################################
## WEBO Site SpeedUp configuration ######
#########################################
## Access control
$compress_options['username'] = "";
$compress_options['password'] = "";
$compress_options['htpasswd'] = "";
$compress_options['optimization'] = "1";
$compress_options['showbeta'] = "0";
$compress_options['email'] = "";
$compress_options['name'] = "";
## Active configuration
$compress_options['config'] = "safe";
## Path info. Cache directory for JS files
$compress_options['javascript_cachedir'] = "";
## Cache directory for CSS files
$compress_options['css_cachedir'] = "";
## Cache directory for HTML files
$compress_options['html_cachedir'] = "";
## Website installation directory
$compress_options['website_root'] = "";
## Document Root directory of the website
$compress_options['document_root'] = "";
## Host name, to include before static resources
$compress_options['host'] = "";
## Website charset, to include to .htaccess and cache hit
$compress_options['charset'] = "";
## Add JS loader for all widgets on onload event
$compress_options['unobtrusive']['on'] = "0";
## Add merged script right before </body>
$compress_options['unobtrusive']['body'] = "0";
## Put all scripts right before </body>
$compress_options['unobtrusive']['all'] = "0";
## Put all known JS informers right before </body>
$compress_options['unobtrusive']['informers'] = "0";
## Put all known JS counters right before </body>
$compress_options['unobtrusive']['counters'] = "0";
## Put all known advertisement blocks right before </body>
$compress_options['unobtrusive']['ads'] = "0";
## Load all iframes near </body>
$compress_options['unobtrusive']['iframes'] = "0";
## Merge external JavaScript files
$compress_options['external_scripts']['on'] = "1";
## Merge inline scripts in head
$compress_options['external_scripts']['inline'] = "1";
## Move merged scripts to </head>
$compress_options['external_scripts']['head_end'] = "1";
## Merge external CSS files
$compress_options['external_scripts']['css'] = "1";
## Merge inline styles in head
$compress_options['external_scripts']['css_inline'] = "1";
## Ignore list, JS files separated by space
$compress_options['external_scripts']['ignore_list'] = "joomla.javascript.js topsy.js wpsf-js.php tiny_mce.js tiny_mce_src.js tiny_init.js tiny_mce_gzip.php fckeditor.js";
## Ignore list, CSS files separated by space
$compress_options['external_scripts']['additional_list'] = "";
## Include CSS code to all generated files
$compress_options['external_scripts']['include_code'] = "";
## Include try-catch construction to merged JS file
$compress_options['external_scripts']['include_try'] = "1";
## Remove duplicates of common libraries
$compress_options['external_scripts']['duplicates'] = "1";
## Exclude the following scripts from minify
$compress_options['external_scripts']['minify_exclude'] = "";
## To get through HTTP Basic Authorization
$compress_options['external_scripts']['user'] = "";
$compress_options['external_scripts']['pass'] = "";
## Performance options, don't check files mtime
$compress_options['performance']['mtime'] = "1";
## Don't use RegExp everywhere where it's possible
$compress_options['performance']['plain_string'] = "0";
## Cache version, ignore cache integrity, no I/O overhead
$compress_options['performance']['cache_version'] = "0";
## Uniform cache files through all browsers
$compress_options['performance']['uniform_cache'] = "0";
## Restore missed CSS properties
$compress_options['performance']['restore_properties'] = "0";
## Days to delete old files from cache, 0 disables logic
$compress_options['performance']['delete_old'] = "0";
## Caching engine, 0 - files, 1 - memcache, 2 - APC, 3 - XCache, 4 - Zend Cache, 5 - semaphores
$compress_options['performance']['cache_engine'] = "0";
## Options to connect to cache engine, i.e. server and port
$compress_options['performance']['cache_engine_options'] = "127.0.0.1:11211";
## Minify options, JS
$compress_options['minify']['javascript'] = "0";
## Minify JS inside <body> tag. Please be carefull
$compress_options['minify']['javascript_body'] = "0";
## Minify JS with JSMin from Douglas Crockford
$compress_options['minify']['with_jsmin'] = "0";
## Minify JS with Dean Edwards Packer
$compress_options['minify']['with_packer'] = "0";
## Minify JS with YUI Compressor (requires java installed)
$compress_options['minify']['with_yui'] = "0";
## Minify CSS
$compress_options['minify']['css'] = "0";
## Minify CSS, 0 - no, 1 - basic
$compress_options['minify']['css_min'] = "0";
## Minify CSS inside <body> tag
$compress_options['minify']['css_body'] = "0";
## Remove whitespaces
$compress_options['minify']['page'] = "0";
## Remove comments from HTML. Some JS counters can be broken
$compress_options['minify']['html_comments'] = "0";
## Shrink HTML code to 1 string, CPU intensive
$compress_options['minify']['html_one_string'] = "0";
## CSS file name (only if 1 CSS set for website is used)
$compress_options['minify']['css_file'] = "";
## JS file name (only if 1 JS set for website is used)
$compress_options['minify']['javascript_file'] = "";
## CSS file host (both for CDN and merged file)
$compress_options['minify']['css_host'] = "";
## JavaScript file host (both for CDN and merged file)
$compress_options['minify']['javascript_host'] = "";
## Gzip options
$compress_options['gzip']['javascript'] = "1";
$compress_options['gzip']['page'] = "0";
$compress_options['gzip']['css'] = "0";
## Gzip font files (SVG, TTF, OTF, etc)
$compress_options['gzip']['fonts'] = "1";
## Check for gzip possibility via cookie
$compress_options['gzip']['cookie'] = "1";
## Exclude IE6/7 from gzip logic
$compress_options['gzip']['noie'] = "0";
## Compression levels for JS/HTML/CSS files, work only in PHP
$compress_options['gzip']['javascript_level'] = "9";
$compress_options['gzip']['page_level'] = "9";
$compress_options['gzip']['css_level'] = "9";
$compress_options['gzip']['fonts_level'] = "9";
## Caching
$compress_options['far_future_expires']['javascript'] = "1";
$compress_options['far_future_expires']['css'] = "0";
## Cache static assets via .htaccess or PHP proxy
$compress_options['far_future_expires']['images'] = "0";
$compress_options['far_future_expires']['fonts'] = "0";
## Cache static assets (flash, video, etc) -- only via .htaccess
$compress_options['far_future_expires']['video'] = "0";
$compress_options['far_future_expires']['static'] = "0";
## Send cache headers for HTML files?
$compress_options['far_future_expires']['html'] = "0";
## Default timeout of client side HTML files caching, in seconds
$compress_options['far_future_expires']['html_timeout'] = "60";
## Add caching for external files
$compress_options['far_future_expires']['external'] = "0";
## Cache generated HTML files
$compress_options['html_cache']['enabled'] = "0";
## Cache timeout for generated HTML files, in seconds
$compress_options['html_cache']['timeout'] = "600";
## Flush head section with first N bytes of body?
$compress_options['html_cache']['flush_only'] = "0";
## Flush size of HTML body
$compress_options['html_cache']['flush_size'] = "1024";
## Parts of ignore URL for HTML cache, separated by space
$compress_options['html_cache']['ignore_list'] = "";
## Parts of user agents to output cached HTML, separated by space
$compress_options['html_cache']['allowed_list'] = "office data msfrontpage yahoo googlebot yandex yadirect dyatel msnbot twiceler";
## Cookies to skip HTML caching
$compress_options['html_cache']['additional_list'] = "";
## Exclude GET params from hash
$compress_options['html_cache']['params'] = "";
## Enhanced mode for HTML caching
$compress_options['html_cache']['enhanced'] = "0";
## Cache SQL queries results
$compress_options['sql_cache']['enabled'] = "0";
## Minimum execution time to cache query result, in milliseconds
$compress_options['sql_cache']['time'] = "1";
## List of tables to exclude from SQL cache
$compress_options['sql_cache']['tables_exclude'] = "session";
## Cache timeout for queries, in seconds
$compress_options['sql_cache']['timeout'] = "600";
## On or off 
$compress_options['active'] = "0";
## Display a link back to Web Optimizer
$compress_options['footer']['text'] = "0";
## Image path for Web Optimizer, empty for text link
$compress_options['footer']['image'] = "web.optimizer.stamp.png";
## Text for a text link
$compress_options['footer']['link'] = "Accelerated with WEBO Site SpeedUp";
## CSS styles to place Web Optimizer stamp
$compress_options['footer']['css_code'] = "float:right;margin:-104px 4px -100px";
## Add a spot to <title>: lang="wo" or xml:lang="wo"
$compress_options['footer']['spot'] = "1";
## Add load speed counter (via Google Analytics)
$compress_options['footer']['counter'] = "0";
## Should Web Optimizer use data URIs for background images?
$compress_options['data_uris']['on'] = "0";
## Should Web Optimizer separate CSS for rules and images?
$compress_options['data_uris']['separate'] = "0";
## Should Web Optimizer load resource CSS on DOMloaded event?
$compress_options['data_uris']['domloaded'] = "0";
## Maximum size of images to be converted, in bytes
$compress_options['data_uris']['size'] = "24576";
## data:URI ignore list, files separated by space, i.e. head.jpg
$compress_options['data_uris']['ignore_list'] = "";
## Should Web Optimizer use mhtml for background images?
$compress_options['data_uris']['mhtml'] = "0";
## Maximum size of images to be converted into mhtml, in bytes
$compress_options['data_uris']['mhtml_size'] = "51200";
## mhtml ignore list, files separated by space, i.e. head.jpg
$compress_options['data_uris']['additional_list'] = "";
## Should Web Optimizer use CSS Sprites for background images?
$compress_options['css_sprites']['enabled'] = "0";
## Save 24bit images in JPEG not PNG
$compress_options['css_sprites']['truecolor_in_jpeg'] = "0";
## Ignore no dimensions for repeat-x / repeat-y Sprites
$compress_options['css_sprites']['aggressive'] = "0";
## Add additional 5px around images to CSS Sprites
$compress_options['css_sprites']['extra_space'] = "0";
## Exclude IE6 from CSS Sprites creation
$compress_options['css_sprites']['no_ie6'] = "0";
## Restrict large Sprites creation on GDlib failure, in pixels
$compress_options['css_sprites']['dimensions_limited'] = "900";
## CSS Sprites ignore or allow list, 0 - ignore, 1 - allow
$compress_options['css_sprites']['ignore'] = "0";
## CSS Sprites ignore list, files separated by space, i.e. head.jpg
$compress_options['css_sprites']['ignore_list'] = "corners.gif";
## Combine small HTML images to sprites?
$compress_options['css_sprites']['html_sprites'] = "0";
## Restrict large HTML images from merging
$compress_options['css_sprites']['html_limit'] = "100";
## Restrict HTML Sprites to the curent page only?
$compress_options['css_sprites']['html_page'] = "0";
## Parallel downloads
$compress_options['parallel']['enabled'] = "0";
## Check hosts availability or not?
$compress_options['parallel']['check'] = "0";
## List of hosts for parallel downloads, i.e. img i1 i2
$compress_options['parallel']['allowed_list'] = "";
## Use CSS host for all CSS files?
$compress_options['parallel']['css'] = "0";
## Use JavaScript host for all CSS files?
$compress_options['parallel']['javascript'] = "0";
## List of websites (saellites) to distribute through them,
## i.e. satellite.com satellite2.com
$compress_options['parallel']['additional'] = "";
## List of satellites' hosts, i.e. i1 i2 i3
$compress_options['parallel']['additional_list'] = "";
## Lisf of ignored images, separated by space, i.e. xxc.php
$compress_options['parallel']['ignore_list'] = "";
## Custom CDN usage, 0 - defailt, 1 - cdn.host.com, 2 - Coral CDN, 3 - WEBO CDN
$compress_options['parallel']['custom'] = "0";
## FTP creadentials to upload new files to CDN
$compress_options['parallel']['ftp'] = "";
## SSL secure host to use for all CDN assets
$compress_options['parallel']['https'] = "";
## Should be gzip / cache settings written via .htaccess?
$compress_options['htaccess']['enabled'] = "0";
$compress_options['htaccess']['mod_deflate'] = "1";
$compress_options['htaccess']['mod_gzip'] = "1";
$compress_options['htaccess']['mod_expires'] = "1";
$compress_options['htaccess']['mod_headers'] = "1";
$compress_options['htaccess']['mod_setenvif'] = "1";
$compress_options['htaccess']['mod_rewrite'] = "1";
$compress_options['htaccess']['mod_mime'] = "1";
## Use local directory with installed website
$compress_options['htaccess']['local'] = "1";
## Security options
$compress_options['htaccess']['access'] = "0";
## Restricted for logic website parts
$compress_options['restricted'] = "";
## Punypng api key
$compress_options['punypng'] = "";
## List of enabled plugins for server side performance
$compress_options['plugins'] = "";
## current website performance achievements
$compress_options['awards'] = "00000";
## SaaS license daily payments, used only in UI
$compress_options['fee'] = "14";
## Current points, used only in UI
$compress_options['points'] = "";
## Web Optimizer license, empty for free edition
$compress_options['license'] = "";
#########################################
?>
