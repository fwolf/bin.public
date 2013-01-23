<?php
/**
 * Default configure file
 *
 * Copy this file as a new file named 'config.php',
 *	set your configure in the new file,
 *	and DO NOT modify this file directly.
 *
 * @package		bin.public
 * @copyright	Copyright 2008-2013, Fwolf
 * @author		Fwolf <fwolf.aide+bin.public@gmail.com>
 * @license		http://www.gnu.org/licenses/lgpl.html LGPL v3
 * @since		2008-02-17
 */

if (!defined('FWOLFLIB')) define('FWOLFLIB', 'fwolflib/');
require_once(FWOLFLIB . 'func/config.php');
require_once(FWOLFLIB . 'func/env.php');
// Global config data array
if (!defined('DEFAULT_CONFIG_DONE')) {
	$config = array();
}

// ======== git-stat.php settings
// How many info will this disp ? (1-2)
SetCfg('git-stat.depth', 2);
// Total width of output
SetCfg('git-stat.width', 80);
// Empty line between authors if depth > 1
SetCfg('git-stat.spacer', true);
// Consider avg line width when count
SetCfg('git-stat.tidy.on', true);
// Standard line width
SetCfg('git-stat.tidy.std', 28);
// cnt-git.php settings ========


// ========= In the config.php, you can delete below contents =========
if (!defined('DEFAULT_CONFIG_DONE')) {

	define('DEFAULT_CONFIG_DONE', true);

	// If config.php exists, include it.
	if (defined('P2R') && file_exists(P2R . 'config.php'))
		require(P2R . 'config.php');
}
?>
