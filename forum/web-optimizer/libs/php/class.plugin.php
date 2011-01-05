<?php
// ==============================================================================================
// Licensed under the GNU GPLv3 (LICENSE.txt)
// ==============================================================================================
// @author     WEBO Software (http://www.webogroup.com/)
// @version    1.1.0
// @copyright  Copyright &copy; 2009-2010 WEBO Software, All Rights Reserved
// ==============================================================================================
// no direct access

/* Default plugin implementation */

class webo_plugin
{
	function onCache ($content)
	{
		return $content;
	}

	function onBeforeOptimization ($content)
	{
		return $content;
	}

	function onAfterOptimization ($content)
	{
		return $content;
	}

	function onInstall ($root)
	{
		return;
	}

	function onUninstall ($root)
	{
		return;
	}
}

?>
