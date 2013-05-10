<?php
/**
 * Default configure file
 *
 * Usage:
 * 	Create a new file named 'config.php',
 * 	do your setting in it using SetCfg(),
 * 	the syntax is silimar with SetCfgDefault() below,
 *	they will be auto included by this file.
 *	DO NOT MODIFY THIS FILE DIRECTLY.
 *
 * @package		bin.public
 * @copyright	Copyright © 2008-2013, Fwolf
 * @author		Fwolf <fwolf.aide+bin.public@gmail.com>
 * @license		http://www.gnu.org/licenses/lgpl.html LGPL v3
 * @since		2008-02-17
 */


// Define location of Fwolflib
if (!defined('FWOLFLIB'))
	define('FWOLFLIB', 'fwolflib/');
require_once FWOLFLIB . 'func/config.php';
//require_once FWOLFLIB . 'func/env.php';

// Init global config data array and include user config file
if ('config.default.php' == basename(__FILE__)) {
	$config = array();

	if (file_exists(__DIR__ . '/config.php'))
		require __DIR__ . '/config.php';
}


// ======== git-stat.php settings
// How many info will this disp ? (1-2)
SetCfgDefault('git-stat.depth', 2);
// Total width of output
SetCfgDefault('git-stat.width', 80);
// Empty line between authors if depth > 1
SetCfgDefault('git-stat.spacer', true);
// Consider avg line width when count
SetCfgDefault('git-stat.tidy.on', true);
// Standard line width
SetCfgDefault('git-stat.tidy.std', 28);
// cnt-git.php settings ========


// ======== Mail settings
// Mail host
SetCfgDefault('mail.server.gmail.imap.host', 'imap.gmail.com');
SetCfgDefault('mail.server.gmail.imap.port', 993);
SetCfgDefault('mail.server.gmail.pop3.host', 'pop.gmail.com');
SetCfgDefault('mail.server.gmail.pop3.port', 995);
SetCfgDefault('mail.server.gmail.smtp.host', 'ssl://smtp.gmail.com');
SetCfgDefault('mail.server.gmail.smtp.port', 465);
// Mail account
/*
SetCfgDefault('mail.account.user@domain_tld.server', 'gmail');
SetCfgDefault('mail.account.user@domain_tld.name', 'user@domain.tld');
SetCfgDefault('mail.account.user@domain_tld.user', 'user or user@domain.tld');
SetCfgDefault('mail.account.user@domain_tld.pass', 'pass');
*/
// Mail settings ========


?>
