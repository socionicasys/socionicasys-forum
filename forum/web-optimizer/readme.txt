WEBO Site SpeedUp
-----------------------
WEBO Site SpeedUp is a PHP solution that automatically speeds your website up by
combining and compressing your JavaScript and CSS assets.
It can also GZIP these assets, and the page itself (via PHP or .htaccess
options). Also it applies CSS Sprites and data:URI techniques. It supports
unobtrusive JavaScript conversion, multiple hosts, CDN, and a lot of other
useful options.
Actually WEBO Site SpeedUp applies all known client side optimization approaches
to completely speed up your website. Average acceleration is 2.5 times. WEBO
Site SpeedUp is based on Web Optimizer which was initially based on PHP Speedy.

WEBO Site SpeedUp native plugin for Wordpress:
http://wordpress.org/extend/plugins/webo-site-speedup/

WEBO Site SpeedUp native component+plugin for Joomla! 1.5 and Joomla! 1.0:
http://extensions.joomla.org/extensions/site-management/site-performance/10152

WEBO Site SpeedUp native module for Drupal 6.x and Drupal 5.x:
http://www.webogroup.com/home/download/

WEBO Site SpeedUp native module for Bitrix:
http://www.webogroup.com/corporate/download/

WEBO Site SpeedUp native addon for CS-Cart:
http://www.webogroup.com/corporate/download/

WEBO Site SpeedUp native addon for Magento:
http://www.webogroup.com/corporate/download/

WEBO Site SpeedUp native module for NetCat:
http://www.webogroup.com/corporate/download/

Installation
-----------------------
1. Download and UNZIP the WEBO Site SpeedUp package into its own directory (if
   you haven't already done this).
2. Point your browser to the WEBO Site SpeedUp directory (/web-optimizer -- the
   one you have just created).
2a. If you are using advanced framework (such as CodeIgniter, Zend Framework,
    Symfony, etc) please disable default Rewrite rules to setup WEBO Site
	SpeedUp properly. I.e. comment these lines
	  RewriteCond %{REQUEST_FILENAME} !-f
	  RewriteRule .* index.php
    in your .htaccess
2b. Or you can just try to go to /web-optimizer/index.php
3. Please also check that
   * website root is writable for your web server process or(and) there is
     writable .htaccess file
   * default cache folder (web-optimizer/cache) is writable for your web server
     process
   * config.webo.php is writable for your web server process
   * (optional) web-optimizer folder is also writable (is required to save
     custom configurations)
4. Tune and debug the application.
5. After your website is ready - just activate WEBO Site SpeedUp from
   administrative interface.
6. Enjoy your accelerated website!

Support and bug reports
-----------------------
Please submit support requests and bug reports via
http://code.google.com/p/web-optimizator/issues/list

Purchase
-----------------------
You can purchase full verion with dozens of acceleration settings or order
product installation and tuning here:
http://www.web-optimizer.us/
Version Comparison is here:
http://www.web-optimizer.us/web-optimizer/comparison.html

Donate
-----------------------
Please find all coordinates for donation here: http://sprites.in/donate/

Upgrade issues
-----------------------
Upgrade procedure from version 0.6.7 to 0.9.5+ should be a common one. But
please store config.webo.php somewhere and only then upgrade via program
interface. If configuration file isn't applied properly - you will need to copy
all previous settings to the new file (there are a number of new options there).

To proper upgrade from version 0.5.9 and below please after upgrade save
configuration options once more - this will create all necessary rules in
.htaccess.

Auto-upgrade is included since version 0.3.8. Please just enter username and
password at your Web Optimizer Admin interface and press 'Upgrade'. For
auto-upgrade curl must be enabled on the server.

Please note that on upgrading from version 0.2 and below you need to replace in
the last part in index.php file 'compressor' to 'web_optimizer'.

Known issues
-----------------------
There are several issues related to CSS Sprites usage. If you think that your
template is broken or you system shows white screen -- please try to disable
CSS Sprites in configuration. This will solve occured issue in 99% of cases.
Also you can try to exclude some images from CSS Sprites generation.
Please also visit TroubleeShooter page in Wiki:
http://code.google.com/p/web-optimizator/wiki/TroubleshootingAndSupport

Team and contributors
-----------------------
A lot of different persons contributed to this project. Some of them:
 * sunnybear (core, unit tests, htaccess, CSS Sprites, CDN, unobtrusive logic,
   performance tuning, promotion, other stuff)
 * graphite (3rd party modules, server architecture, server caching)
 * fade (design, logo, general usability, product interface)
 * gkondratenko (documentation, integration, interface & usability, make-up
   fixes, known issues gathering)
 * bazik (test cases for CSS Sprites, and CSS rules, and JS logic)
 * beshkenadze (initial YUI Compressor envelope)
 * janvarev (files MTIME check)
 * markusmerz (overall beta testing)
 * olmer2002 (overall beta testing)
 * crazyyy (UA localization)
 * Ajexandro (ES localization)
 * jos (DE localization of 0.6 version)
 * veroniquemckay (FR localization of 1.0+ versions)
 
License
-----------------------
WEBO Site SpeedUp core is licensed under Eclipse Public License (LICENSE.txt).
It's located in this package. For Russian users there is LICENSE.ru.txt or
LICENSE.ru.utf8.txt
Some parts of WEBO Site SpeedUp product are open source and licensed under
different licenses:
 * JSMin library - GNU GPLv2
 * Packer library - GNU LGPLv2
 * YUI Compressor binary / its envelope - BSD License
 * YASS library - MIT License
 * CSS Tidy library - GNU LGPLv2
 * XHTML document.write() Support - GNU GPL
 * HTML Parser - Mozilla Public License
 * WEBO CSS Sprites - WEBO License
 * WEBO HTML Sprites - WEBO License
 * WEBO PHP Static library - WEBO License
 * WEBO HTML Cache library - WEBO License
 * WEBO SQL Cache library - WEBO License