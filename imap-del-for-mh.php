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

	Ecl("\n[" . date('Y-m-d H:i:s') . ']');
	$s_file = GetCfg('imap-del-for-mh.dir.mh') . $s_file;
	ImapSearch($s_file);

	if (empty($ar_uid))
		return 0;

	$ar_done = array();
	foreach ($ar_uid as $account => $i_uid) {
		Ecl("\t" . 'Account: ' . $account . ', UID: ' . $i_uid);
		$b1 = imap_mail_move($ar_mbox[$account], $i_uid
			, GetCfg('imap-del-for-mh.mail.' . $account . '.trash')
			, CP_UID);
		$b2 = imap_delete($ar_mbox[$account], $i_uid, FT_UID);
		if ($b1 && $b2) {
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

	Ecl('File: ' . $s_file);

	if (!is_readable($s_file))
		return;

	$s = file_get_contents($s_file);
	// Grap From, Date, Message-ID
	$s_date = '';
	$s_from = '';
	$s_messageid = '';
	$s_subject = '';
	$ar = array();
	$i = preg_match('/\nDate:(.+?)\n/', $s, $ar);
	if (1 === $i) {
		$s_date_original = trim($ar[1]);
		$s_date = date('d-M-Y', strtotime($ar[1]));
		$s_date_since = date('d-M-Y', strtotime($s_date . ' -1 day'));
		$s_date_before = date('d-M-Y', strtotime($s_date . ' +1 day'));
	}
	$i = preg_match('/\nFrom:(.+?)\n/i', $s, $ar);
	if (1 === $i) {
		$s_form = $ar[1];
	}
	else {
		Ecl('Date: empty.');
		return;
	}
	// From: need decode
	$i = preg_match('/\nFrom:(.+?)\n/i', $s, $ar);
	if (1 === $i) {
		$ar = imap_mime_header_decode(trim($ar[1]));
		foreach ((array)$ar as $elm)
			$s_from .= $elm->text;
	}
	else {
		Ecl('From: empty.');
		return;
	}
	$i = preg_match('/\nMessage-ID:(.+?)\n/i', $s, $ar);
	if (1 === $i) {
		$s_messageid = trim($ar[1]);
	}
	else {
		Ecl('Message-ID: empty.');
		return;
	}
	$i = preg_match('/\nSubject:(.+?)\n/i', $s, $ar);
	if (1 === $i) {
		$ar = imap_mime_header_decode(trim($ar[1]));
		foreach ((array)$ar as $elm)
			$s_subject .= $elm->text;
	}
	else {
		Ecl('Subject: empty.');
	}

	Ecl('From: ' . $s_from);
	Ecl('Date: ' . $s_date_original);
	Ecl('Subject: ' . $s_subject);
	Ecl('Message-ID: ' . $s_messageid);

	// Do search
	$s_search = '';
	if (! false === strpos('@', $s_from))
		$s_search .= ' FROM "' . addslashes($s_from) . '"';
	if (!empty($s_subject))
		$s_search .= ' SUBJECT "' . addslashes($s_subject) . '"';
	$s_search .= ' SINCE "' . $s_date_since . '"'
		. ' BEFORE "' . $s_date_before . '"'
	;

	foreach ($ar_mbox as $account => $o_mbox) {
		$ar = imap_search($o_mbox, $s_search, SE_UID);
		if (empty($ar))
			continue;

		// Found, fetch and compare with message-id
		$ar = imap_fetch_overview($o_mbox, implode(',', $ar), FT_UID);
		if (empty($ar))
			continue;
		$i_uid = -1;

		// If only 1 search result, assign directly
		if (1 == count($ar))
			$i_uid = $ar[0]->uid;
		else {
			foreach ($ar as $mail)
				if ($mail->message_id == $s_messageid) {
					$i_uid = $mail->uid;
					break;
				}
		}

		// Write to result array
		if (-1 != $i_uid)
			$ar_uid[$account] = $i_uid;
	}

	if (empty($ar_uid))
		Ecl("\t" . 'Search not found !');

	return;
} // end of func ImapSearch


/*
 * ChangeLog
 *
 * V 0.01 / 2013-05-10 /
 * 		- New.
 */
?>