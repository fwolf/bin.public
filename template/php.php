#! /usr/bin/php
<?php
/**
 * filename.php
 *
 * Description of this script.
 *
 * Other information like requirement, usage etc.
 *
 *
 * Copyright 2017 Fwolf <fwolf.aide+bin.public@gmail.com>
 * All rights reserved.
 *
 * Distributed under the GNU General Public License v3.0 or later.
 * http://www.gnu.org/licenses/gpl.html
 * Distributed under the GNU Lesser General Public License v3.0 or later.
 * http://www.gnu.org/licenses/lgpl.html
 * Distributed under the MIT license.
 * http://opensource.org/licenses/MIT
 *
 * @package     bin.public
 * @copyright   Copyright 2017 Fwolf
 * @author      Fwolf <fwolf.aide+bin.public@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl.html GPL-3.0+
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL-3.0+
 * @license     http://opensource.org/licenses/MIT MIT
 * @since       2011-01-11
 */

// Include
if (0 <= version_compare(phpversion(), '5.3.0')) {
    if (!defined('P2R')) define('P2R', __DIR__ . '/');
    require_once(__DIR__ . '/config.default.php');
} else {
    if (!defined('P2R')) define('P2R', dirname(__FILE__) . '/');
    require_once(dirname(__FILE__) . '/config.default.php');
}


// Check

// Check parameter amount, at least 1 param needed
if (2 > $argc) {
    PrintUsage();
    exit(-1);
}



// Main body



// Functions define

/*
 * Print usage message
 */
function PrintUsage() {
    $s = basename(__FILE__);
    echo <<<EOF
Usage: $s P1 [P2] [P3] [P4]

Parameters:
  -P1           Note for P1.
  -P2           Note for P2.

EOF;
} // end of func PrintUsage


/**
 * ChangeLog
 *
 * Unversoned
 *  - Change copyright and changelog style
 *
 * v0.04 / 2012-03-01 / 90598d3f73
 *  - Chg: Use __DIR__ instead of P2R constant
 *
 * v0.03 / 2011-08-21 / 38fdf8d44b
 *  - Add P2R and auto include config.default/config
 *
 * v0.02 / 2011-07-25 / 7374770e25
 *  - Add hash in version history
 *
 * v0.01 / 2010-10-10 / 564ebdbb10
 *  - New: Basic structure with PrintUsage() func
 */
