#! /usr/bin/php
<?php
/**
 * imap-del-for-mh.php
 *
 * Copyright (c) 2013, Fwolf <fwolf.aide+bin.public@gmail.com>
 * All rights reserved.
 *
 * Distributed under the GNU General Public License, version 3.0.
 * http://www.gnu.org/licenses/gpl.html
 *
 * Scan mail in MH folder, find them in imap server by message_id,
 * delete, then archive to another directory.
 *
 * @package		bin.public
 * @copyright	Copyright © 2013, Fwolf
 * @author		Fwolf <fwolf.aide+bin.public@gmail.com>
 * @license		http://www.gnu.org/licenses/gpl.html GPL v3
 * @since		2013-05-10
 */

// Include
if (0 <= version_compare(phpversion(), '5.3.0')) {
	if (!defined('P2R')) define('P2R', __DIR__ . '/');
	require_once(__DIR__ . '/config.default.php');
} else {
	if (!defined('P2R')) define('P2R', dirname(__FILE__) . '/');
	require_once(dirname(__FILE__) . '/config.default.php');
}
require_once FWOLFLIB . '/func/ecl.php';
require_once FWOLFLIB . '/func/filesystem.php';


// Main body

// Init
$ar_file = GetFileMh();
$ar_file_ignore = GetFileIgnore();
$i_done = 0;
$i_done_max = GetCfg('imap-del-for-mh.batchsize');
$ar_mail = array();
$ar_mbox = array();

// Retrieve files
if (!empty($ar_file))
	ImapConnect();

	foreach ($ar_file as $s_file) {
		if ($i_done == $i_done_max)
			break;

		if (in_array($s_file['name'], $ar_file_ignore))
			continue;

		$ar_uid = array();
		$i_done += ImapDel($s_file['name']);
	}


// Functions define


/**
 * Get files need ignore
 *
 * @return	array
 */
function GetFileIgnore () {
	$ar = GetCfg('imap-del-for-mh.file.ignore');
	if (empty($ar))
		return array();

	if (is_string($ar)) {
		// Split by ' ' or ','
		$ar = preg_replace('/[, ]+/', ',', $ar);
		$ar = explode(',', $ar);
	}

	return $ar;
} // end of func GetFileIgnore


/*
 * Get MH files
 *
 * @return	array
 */
function GetFileMh () {
	$s_path = GetCfg('imap-del-for-mh.dir.mh');
	if (empty($s_path) || !is_readable($s_path))
		return array();

	return ListDir($s_path);
} // end of func GetFileMh


/**
 * Connect to imap server
 */
function ImapConnect () {
	global $ar_mail, $ar_mbox;

	// Connect to imap
	$ar_mail = GetCfg('imap-del-for-mh.mail');
	if (empty($ar_mail))
		return 0;
	if (!is_array($ar_mail))
		$ar_mail = array($ar_mail);

	foreach ($ar_mail as $account => $mail) {
		$s_host = '{' . GetCfg('mail.server.'
				. GetCfg('mail.account.' . $account . '.server')
				. '.imap.host')
			. ':' . GetCfg('mail.server.'
				. GetCfg('mail.account.' . $account . '.server')
				. '.imap.port')
			. '/imap/ssl/novalidate-cert}' . $mail['mailbox'];
		$ar_mbox[$account] = @imap_open($s_host
			, GetCfg('mail.account.' . $account . '.user')
			, GetCfg('mail.account.' . $account . '.pass')
		);
		// Check error
		$rs = imap_last_error();
		if (!(false === $rs)) {
			Ecl('Can\'t connect to ' . $account);
			exit(-1);
		}
	}
} // end of ImapConnect


/**
 * Do imap del
 *
 * @param	string	$s_file
 * @return	int		0=fail, 1=success
 */
function ImapDel ($s_file) {
	global $ar_mbox, $ar_uid;

	$s_file = GetCfg('imap-del-for-mh.dir.mh') . $s_file;
	ImapSearch($s_file);

	if (empty($ar_uid))
		return 0;

	$ar_done = array();
	foreach ($ar_uid as $account => $i_uid) {
		Ecl("\t" . 'Account: ' . $account . ', UID: ' . $i_uid);
		$b = imap_mail_move($ar_mbox[$account], $i_uid
			, GetCfg('imap-del-for-mh.mail.' . $account . '.trash')
			, CP_UID);
		if ($b) {
			$ar_done[] = $account;
			imap_expunge($ar_mbox[$account]);
		}
	}

	// Result
	if (!empty($ar_done)) {
		Ecl("\t" . 'Deleted from: ' . implode(', ', $ar_done));
		// Archive file
		rename($s_file, GetCfg('imap-del-for-mh.dir.done')
			. basename($s_file));

		return 1;
	}
	else
		// Nothing deleted
		return 0;
} // end of func ImapDel


/**
 * Search for imap uid
 *
 * @param	string	$s_file
 * @return	int
 */
function ImapSearch ($s_file) {
	global $ar_mbox, $ar_uid;

	Ecl('');
	echo 'File: ' . $s_file;

	if (!is_readable($s_file))
		return;

	$s = file_get_contents($s_file);
	// Grap From, Date, Message-ID
	$s_date = '';
	$s_from = '';
	$s_messageid = '';
	$ar = array();
	$i = preg_match('/\nDate:(.+?)\n/', $s, $ar);
	if (1 === $i) {
		$s_date_original = trim($ar[1]);
		$s_date = date('d-M-Y', strtotime($ar[1]));
		$s_date_since = date('d-M-Y', strtotime($s_date . ' -1 day'));
		$s_date_before = date('d-M-Y', strtotime($s_date . ' +1 day'));
	}
	$i = preg_match('/\nFrom:(.+?)\n/', $s, $ar);
	if (1 === $i) {
		$s_form = $ar[1];
	}
	else {
		Ecl(', Date: empty.');
		return;
	}
	$i = preg_match('/\nFrom:(.+?)\n/', $s, $ar);
	if (1 === $i) {
		$s_from = trim($ar[1]);
	}
	else {
		Ecl(', From: empty.');
		return;
	}
	$i = preg_match('/\nMessage-ID:(.+?)\n/', $s, $ar);
	if (1 === $i) {
		$s_messageid = trim($ar[1]);
	}
	else {
		Ecl(', From: empty.');
		return;
	}

	$s_search = ' FROM "' . $s_from . '"'
		. ' SINCE "' . $s_date_since . '"'
		. ' BEFORE "' . $s_date_before . '"'
	;

	Ecl(', From: ' . $s_from . ', Date: ' . $s_date_original);

	foreach ($ar_mbox as $account => $o_mbox) {
		$ar = imap_search($o_mbox, $s_search, SE_UID);
		if (empty($ar))
			continue;

		// Found, fetch and compare with message-id
		$ar = imap_fetch_overview($o_mbox, implode(',', $ar), FT_UID);
		if (empty($ar))
			continue;
		$i_uid = -1;
		foreach ($ar as $mail)
			if ($mail->message_id == $s_messageid)
				$i_uid = $mail->uid;
		if (-1 != $i_uid)
			$ar_uid[$account] = $i_uid;
	}

	return;
} // end of func ImapSearch


/*
 * ChangeLog
 *
 * V 0.01 / 2013-05-10 /
 * 		- New.
 */
?>
