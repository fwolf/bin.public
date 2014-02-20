<?php
/**
 * Configure file
 *
 * Usage:
 *  1. Copy to a new file named 'config.php'
 *  2. (Optional)Remove code outside of 'Config define area'
 *  3. Change defines
 *  4. (Optional)Remove defines no change needed
 *
 * For defines which need compute to get final result:
 *  1. Remove from 'config.php'] = use compute job in 'config.default.php'
 *  2. Do compute in 'config.php'
 *
 * DO NOT MODIFY 'config.default.php' DIRECTLY.
 *
 * @package     bin.public
 * @copyright   Copyright © 2008-2013, Fwolf
 * @author      Fwolf <fwolf.aide+bin.public@gmail.com>
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL v3
 * @since       2008-02-17
 */


// Init global config array
if ('config.default.php' == basename(__FILE__)) {
    $config = array();


    // Load user config if exists
    if (file_exists(__DIR__ . '/config.php')) {
        require __DIR__ . '/config.php';
    }
    $configUser = $config;


    // Load requirement lib autoload file
    // Fwlib
    if (!isset($config['lib.path.fwlib'])) {
        $config['lib.path.fwlib'] = 'fwlib/';
    }
    require $config['lib.path.fwlib'] . 'autoload.php';


    // For backward compitible
    define('FWOLFLIB', $config['lib.path.fwlib']);
    require FWOLFLIB . 'func/config.php';
}


/***********************************************************
 * Config define area
 *
 * Use $configUser to compute value if needed.
 *
 * In config.php, code outside this area can be removed.
 **********************************************************/


// ======== git-stat.php settings
// How many info will this disp ? (1-2)
$config['git-stat.depth'] = 2;
// Total width of output
$config['git-stat.width'] = 80;
// Empty line between authors if depth > 1
$config['git-stat.spacer'] = true;
// Consider avg line width when count
$config['git-stat.tidy.on'] = true;
// Standard line width
$config['git-stat.tidy.std'] = 28;
// cnt-git.php settings ========


// ======== imap-del-for-mh.php
// Max files for one-run
$config['imap-del-for-mh.batchsize'] = 100;
// Original mh file dir
$config['imap-del-for-mh.dir.mh'] = '';
// Dir to store mh file after treatment
$config['imap-del-for-mh.dir.done'] = '';
// Dir to store mh file not found on server
$config['imap-del-for-mh.dir.error'] = '';
// Ignore these file, array or string split by ' ' or ','
$config['imap-del-for-mh.file.ignore'] = '';
// Mail account to do del operation, one or multi array
/*
$config['imap-del-for-mh.mail'] = array(
    'account name'  => array(
        'mailbox'   => 'mailbox name',
        'trash'     => 'trash name',
    ),
);
*/
// imap-del-for-mh.php ========


// ======== Mail settings
// Mail host
$config['mail.provider.gmail.imap.host'] = 'imap.gmail.com';
$config['mail.provider.gmail.imap.port'] = 993;
$config['mail.provider.gmail.pop3.host'] = 'pop.gmail.com';
$config['mail.provider.gmail.pop3.port'] = 995;
$config['mail.provider.gmail.smtp.host'] = 'ssl://smtp.gmail.com';
$config['mail.provider.gmail.smtp.port'] = 465;
// Mail account
/*
$config['mail.account.user@domain_tld.provider'] = 'gmail';
$config['mail.account.user@domain_tld.name'] = 'user@domain.tld';
$config['mail.account.user@domain_tld.user'] = 'user or user@domain.tld';
$config['mail.account.user@domain_tld.pass'] = 'pass';
*/
// Mail settings ========


/***********************************************************
 * Config define area end
 **********************************************************/


// Merge user and default config
if ('config.default.php' == basename(__FILE__)) {
    $config = array_merge($config, $configUser);

    // Deal with $config with Config class or use as global

    // For backward compatible
    foreach ($config as $k => $v) {
        SetCfg($k, $v);
    }
}
