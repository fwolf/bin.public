#! /usr/bin/php
<?php
/**
 * git-stat.php
 *
 * Copyright (c) 2011-2013, Fwolf <fwolf.aide+bin.public@gmail.com>
 * All rights reserved.
 * Distributed under the GNU Lesser General Public License, version 3.0.
 *
 * Count summary each author, each file type in git repo.
 *
 * V 0.02, 2011-08-24, hash: 844d984be1.
 *      - Enh: Dynamic col width.
 *      - Add: Stat info line 2 and spacer.
 *      - Add: Merge cols to 'other' col if too width.
 *
 * V 0.01, since 2011-08-21, hash: b8a9b3c2ff.
 *      - New: Based on origin cnt-git-php.sh,
 *          seperate count for each file type, user and rank them.
 *
 * @package     bin.public
 * @copyright   Copyright © 2011-2013, Fwolf
 * @author      Fwolf <fwolf.aide+bin.public@gmail.com>
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL v3
 * @since       2011-08-21
 */

// Include
if (!defined('P2R')) define('P2R', dirname(__FILE__) . '/');
require_once(P2R . 'config.default.php');
require_once(FWOLFLIB . 'func/array.php');
require_once(FWOLFLIB . 'func/ecl.php');
require_once(FWOLFLIB . 'func/filesystem.php');


// Check

// Check parameter amount, at least 1 param needed
if (1 > $argc) {
    PrintUsage();
    exit(-1);
}
if (isset($argv[1]) && ('h' == $argv[1] || 'help' == $argv[1]))
    exit(PrintUsage());
if (isset($argv[1]) && ('tidy' == $argv[1] || 'radio' == $argv[1]))
    exit(PrintTidy());

// In git repo ?
if (!IsInGitRepo())
    die(Ecl('This must run in git repository dir.', 0, 0));

// Read param
if (isset($argv[1]))
    $s_fext = $argv[1];
else
    $s_fext = '';
if (isset($argv[2]))
    $s_author = $argv[2];
else
    $s_author = '';

/**
 * Line cnt for each ext
 * @var array   array(ext => cnt)
 */
$ar_ext = array();

/**
 * Total result
 * @var array
 */
$ar_result = array();


// Main body
__Main();


/**
 * Main body
 */
function __Main () {
    global $s_fext, $s_author, $ar_ext, $ar_result;

    echo 'Stat @ ' . date('Y-m-d H:i:s') . ' for: ';
    system('git-id.sh');
    Ecl('');

    // Get file list
    $ar_file = FileList($s_fext);
    if (empty($ar_file))
        exit(Ecl('No file match.', 0, 0));

    // Do count
    $ar_result = GitBlame($ar_file);

    // Sort count result, include col-ext and row-author
    arsort($ar_ext);
    ArraySort($ar_result, 'line-tidy', 'desc');

    //PrintResult($ar_result);
    PrintResult2($ar_result);
} // end of func __Main


// Functions define


/**
 * Add value to counter, special for $ar_rs[author]
 *
 * @param   array   &$ar_rs Result array
 * @param   string  $s_author
 * @param   string  $key    Counter key.
 * @param   integer $i_val
 */
function ArrayAdd2 (&$ar_rs, $s_author, $key, $i_val = 1) {
    if (isset($ar_rs[$s_author][$key]))
        $ar_rs[$s_author][$key] += $i_val;
    else
        $ar_rs[$s_author][$key] = $i_val;
} // end of func ArrayAdd2


/**
 * Tidy value for line count by char count given.
 *
 * @param   integer $i_line
 * @param   integer $i_avg
 * @return  integer
 */
function CntTidy ($i_line, $i_avg) {
    return round(GetTidyRadio($i_avg) * $i_line, 0);
} // end of func CntTidy


/**
 * Get extension of filename, copy from Fwolflib/func/filesystem.php
 * and have modified.
 *
 * @param   string  $filename
 * @return  string
 */
function FileExt2 ($filename) {
    $i1 = strrpos($filename, '.');
    $i2 = strrpos($filename, '/');
    $s_ext = '';
    if ($i1 >= $i2)
        $s_ext = substr($filename, $i1 +1);

    // No ext means bash
    if ('' == $s_ext)
        $s_ext = 'sh';
    // .gitignore => conf
    elseif ('gitignore' == $s_ext || 'gitattribues' == $s_ext
        || 'htaccess' == $s_ext)
        $s_ext = 'conf';
    // Merge image extension to img
    elseif (in_array($s_ext, array('gif', 'ico', 'jpg', 'png')))
        $s_ext = 'img';

    return $s_ext;
} // end of func FileExt2


/**
 * List files in git, and filter ext by rule
 *
 * @param   string  $s_fext
 * @return  array
 */
function FileList ($s_fext) {
    $s_cmd = 'find . -type f \( -not -path ./.git/\* \)';
    $ar_file = array();
    exec($s_cmd, $ar_file);

    if (empty($s_fext) || empty($ar_file))
        return $ar_file;
    else {
        // Find all ext first
        $ar_ext = array();
        foreach ($ar_file as $file) {
            $s = FileExt2($file);
            if (!in_array($s, $ar_ext))
                $ar_ext[] = $s;
        }
        // Filter by ext rules
        $ar_ext = FilterWildcard($ar_ext, $s_fext);
        // Remove unmatched file by ext
        foreach ($ar_file as $k => $file)
            if (!in_array(FileExt2($file), $ar_ext))
                unset($ar_file[$k]);

        return $ar_file;
    }
} // end of func FileList


/**
 * Fill a string to fixed width for output
 *
 * @param   string  $str
 * @param   integer $width
 * @return  string
 */
function FixedWidth ($str, $width) {
    if ('-' == $str || '=' == $str)
        return str_repeat($str, $width);

    $str .= str_repeat(' ', $width);
    $str = mb_substr($str, 0, $width);

    // Make text center
    $i = floor((strlen($str) - strlen(trim($str))) / 2);
    if (0 < $i)
        $str = str_repeat(' ', $i)
            . substr($str, 0, strlen($str) - $i);

    return $str;
} // end of func FixedWidth


/**
 * Get tidy radio
 *
 * @param   numeric $i_avg      Average line width
 * @return  numeric
 */
function GetTidyRadio ($i_avg) {
    $i_std = GetCfg('git-stat.tidy.std');
    $f_radio = 1 - pow(abs($i_std - $i_avg), 2)
        / pow($i_std, 2);
    if (0.5 > $f_radio)
        $f_radio = 0.5;
    return $f_radio;
} // end of func GetTidyRadio


/**
 * Count info using git blame
 *
 * @param   array   $ar_file
 * @return  array   array(author => array(line, line-tidy
 *      , line-ext-lang, line-ext-lang-tidy, etc))
 */
function GitBlame ($ar_file) {
    if (empty($ar_file))
        return array();

    $ar_rs = array();
    // Count each file
    foreach ($ar_file as $file) {
        $ar_line = array();
        exec('git blame \'' . $file . '\'', $ar_line);
        if (empty($ar_line))
            continue;

        global $ar_ext;
        $s_ext = FileExt2($file);

        // Count line by line
        foreach ($ar_line as $s_line) {
            // /\(([^)]+)\s+(\d{4}[^)]+?) \d+\) ?(.*)$/
            $i = preg_match_all('/\(([^)]+)\s+(\d{4}[^)]+) [\+\-]\d{4} +\d+\) ?(.*)$/'
                , $s_line, $ar);
            if (0 < $i) {
                $s_author = trim($ar[1][0]);
                $s_code = $ar[3][0];

                if ('Not Committed Yet' == $s_author)
                    // This also include chg lines not commit yet.
                    //break 1;
                    continue;

                // Add to counter
                ArrayAdd($ar_ext, $s_ext, 1);
                ArrayAdd2($ar_rs, $s_author, 'line', 1);
                ArrayAdd2($ar_rs, $s_author, 'line-ext-' . $s_ext, 1);
                ArrayAdd2($ar_rs, $s_author, 'char'
                    , mb_strwidth($s_code, 'utf-8'));
                ArrayAdd2($ar_rs, $s_author, 'char-ext-' . $s_ext
                    , mb_strwidth($s_code, 'utf-8'));
            }
            else
                Ecl('Error occur when blame ' . $file . "\n" . $s_line);
        }
    }
    if (empty($ar_rs))
        return array();


    $ar_t = $ar_rs; // Used to loop only
    // Tidy count by avg line width
    if (true == GetCfg('git-stat.tidy.on'))
        foreach ($ar_t as $author => $ar)
            foreach ($ar as $k => $v) {
                $ar_p = & $ar_rs[$author];

                // Total lines
                if ('line' == $k) {
                    $ar_p['line-avg'] = round($ar_p['char'] / $v, 0);
                    $ar_p['line-tidy'] = CntTidy($v, $ar_p['line-avg']);
                }
                // Other ext
                elseif ('line-ext-' == substr($k, 0, 9)) {
                    $s_ext = substr($k, 9);
                    $ar_p['line-ext-' . $s_ext . '-avg']
                        = round($ar_p['char-ext-' . $s_ext] / $v, 0);
                    $ar_p['line-ext-' . $s_ext . '-tidy']
                        = CntTidy($v
                            , $ar_p['line-ext-' . $s_ext . '-avg']);
                }
            }

    return($ar_rs);
} // end of func GitBlame


/**
 * Check if cmd is run in cli mode and under git repo dir.
 *
 * @return  boolean
 */
function IsInGitRepo () {
    if (!IsCli())
        return false;

    return file_exists(getcwd() . '/.git/');
} // end of func IsInGitRepo


/**
 * Merge col >= $i_other to col other
 *
 * @param   array   &$ar_col
 * @param   integer $i_other
 * @return  array
 */
function MergeOther (&$ar_col, $i_other) {
    if (0 == $i_other)
        return $ar_col;
    global $ar_ext, $ar_result;

    $ar_other = array();
    foreach ($ar_result as $author => $ar) {
        $ar_other[$author] = array();
        $i_ext = 0;
        $i_totalchar = 0;
        foreach ($ar_ext as $k => $v) {
            // Plus 2 col, author and sum
            if ($i_other <= ($i_ext++ + 2)
                    && !empty($ar['line-ext-' . $k])) {
                ArrayAdd($ar_other[$author], 'line'
                    , $ar['line-ext-' . $k]);
                ArrayAdd($ar_other[$author], 'line-tidy'
                    , $ar['line-ext-' . $k . '-tidy']);
                $i_totalchar += $ar['line-ext-' . $k]
                    * $ar['line-ext-' . $k . '-avg'];
            }
        }
        if (!empty($ar_other[$author]['line']))
            $ar_other[$author]['line-avg']
                = round($i_totalchar / $ar_other[$author]['line']);
    }

    // Merge to col array
    $ar_col[$i_other] = array('Other', '=');
    $i_sum = 0;
    foreach ($ar_other as $author => $v) {
        if (isset($v['line'])) {
            PrintLine($ar_col[$i_other], $v);
            if (1 < GetCfg('git-stat.depth') && GetCfg('git-stat.spacer'))
                $ar_col[$i_other][] = '';
            $i_sum += $v['line-tidy'];
        }
        else {
            for ($i=1; $i<=GetCfg('git-stat.depth'); $i++)
                $ar_col[$i_other][] = '';
            if (1 < GetCfg('git-stat.depth') && GetCfg('git-stat.spacer'))
                $ar_col[$i_other][] = '';
        }
    }
    // Remove last spacer
    array_pop($ar_col[$i_other]);

    $ar_col[$i_other][] = '-';
    $ar_col[$i_other][] = $i_sum;

    // Cut cols after other
    for ($i = count($ar_col); $i > $i_other; $i--)
        unset($ar_col[$i]);

    return $ar_col;
} // end of func MergeOther


/**
 * Print content of on row to output array
 *
 * @param   array   &$ar_out
 * @param   array   $ar_srce
 * @param   string  $s_ext
 * @return  array
 */
function PrintLine (&$ar_out, $ar_srce, $s_ext = '') {
    for ($i=1; $i<=GetCfg('git-stat.depth'); $i++) {
        $s_f = 'PrintLine' . $i;
        $ar_out[] = $s_f($ar_srce, $s_ext);
    }
    return $ar_out;
} // end of func PrintLine


/**
 * Gen content for print, line1
 *
 * @param   array   $ar     Array include cnt info of a author
 * @param   string  $s_ext
 * @return  string
 */
function PrintLine1 ($ar, $s_ext = '') {
    if (empty($s_ext))
        return $ar['line-tidy'] . '/' . $ar['line-avg'] . 'c';
    else
        return $ar['line-ext-' . $s_ext . '-tidy']
            . '/' . $ar['line-ext-' . $s_ext . '-avg'] . 'c';
} // end of func PrintLine1


/**
 * Gen content for print, line2
 *
 * @param   array   $ar     Array include cnt info of a author
 * @param   string  $s_ext
 * @return  string
 */
function PrintLine2 ($ar, $s_ext = '') {
    if (empty($s_ext)) {
        $i_line = $ar['line'];
        $i_tidy = $ar['line-tidy'];
        $i_avg = $ar['line-avg'];
    }
    else {
        $i_line = $ar['line-ext-' . $s_ext];
        $i_tidy = $ar['line-ext-' . $s_ext . '-tidy'];
        $i_avg = $ar['line-ext-' . $s_ext . '-avg'];
    }

    // Same with CntTidy()
    $i_std = GetCfg('git-stat.tidy.std');
    $i_radio = GetTidyRadio($i_avg);
    $i_radio = floor($i_radio * 100) . '%';

    return $i_line . '/' . $i_radio;
} // end of func PrintLine2


/**
 * Print result, fixed width
 *
 * @param   array   $ar_rs
 */
function PrintResult ($ar_rs) {
    if (empty($ar_rs))
        return;

    $s_separator = ' | ';
    $w1 = 8;    // Width of name
    $w2 = 10;   // Width of lines etc

    // Prepare sum array and output data array
    global $ar_ext;
    $ar_sum = array();

    $s_empty = str_repeat('-', $w1 + 3
        + (count($ar_ext) + 1) * ($w2 + 3));


    // Title
    echo(FixedWidth('Author', $w1) . $s_separator
        . FixedWidth('Line/Avg', $w2) . $s_separator
    );
    foreach ($ar_ext as $s_ext => $i_cnt)
        echo FixedWidth($s_ext, $w2) . $s_separator;
    Ecl('');
    Ecl($s_empty);


    // Loop fill data, know how many lang got
    foreach ($ar_rs as $author => $ar) {
        $s = 'line';
        ArrayAdd($ar_sum, $s, $ar[$s]);
        $s = 'line-tidy';
        ArrayAdd($ar_sum, $s, $ar[$s]);

        echo FixedWidth($author, $w1) . $s_separator;
        echo FixedWidth($ar['line-tidy'] . '/' . $ar['line-avg'], $w2)
            . $s_separator;

        foreach ($ar_ext as $s_ext => $i_cnt) {
            if (isset($ar['line-ext-' . $s_ext])) {
                $s = 'line-ext-' . $s_ext;
                ArrayAdd($ar_sum, $s, $ar[$s]);
                $s = 'line-ext-' . $s_ext . '-tidy';
                ArrayAdd($ar_sum, $s, $ar[$s]);

                echo FixedWidth($ar['line-ext-' . $s_ext . '-tidy']
                    . '/' . $ar['line-ext-' . $s_ext . '-avg'], $w2)
                    . $s_separator;
            }
            else
                echo FixedWidth('', $w2) . $s_separator;
        }
        Ecl('');
    }


    // Total sum
    Ecl($s_empty);
    echo FixedWidth('Total', $w1) . $s_separator
        . FixedWidth($ar_sum['line-tidy']
            //. '/' . $ar_sum['line'], $w2)
            , $w2)
        . $s_separator;
    foreach ($ar_ext as $s_ext => $i_cnt)
        echo FixedWidth($ar_sum['line-ext-' . $s_ext . '-tidy']
                //. '/' . $ar_sum['line-ext-' . $s_ext], $w2)
                , $w2)
            . $s_separator;
    Ecl('');
} // end of func PrintResult


/**
 * Print result, dynamic width
 *
 * @param   array   $ar_rs
 */
function PrintResult2 ($ar_rs) {
    if (empty($ar_rs))
        return;

    $s_separator = ' | ';

    // Prepare sum array and output data array
    global $ar_ext;
    $ar_sum = array();

    $ar_col = array();
    $i_col = 0;

    // Header
    // Col 1, author
    $ar_col[$i_col][] = 'Author';
    $ar_col[$i_col++][] = '=';
    // Col 2, line
    $ar_col[$i_col][] = 'Sum';
    $ar_col[$i_col++][] = '=';
    foreach ($ar_ext as $s_ext => $i_cnt) {
        $ar_col[$i_col][] = $s_ext;
        $ar_col[$i_col++][] = '=';
    }

    // Body, and sum total by the way
    foreach ($ar_rs as $author => $ar) {
        $i_col = 0;

        // Col author
        $ar_col[$i_col][] = $author;
        for ($i=2; $i<=GetCfg('git-stat.depth'); $i++) {
            $ar_col[$i_col][] = '';
        }
        if (1 < GetCfg('git-stat.depth') && GetCfg('git-stat.spacer'))
            $ar_col[$i_col][] = '';
        $i_col++;

        // Col sum
        PrintLine($ar_col[$i_col], $ar);
        if (1 < GetCfg('git-stat.depth') && GetCfg('git-stat.spacer'))
            $ar_col[$i_col][] = '';
        $i_col++;

        // Sum
        $s = 'line';
        ArrayAdd($ar_sum, $s, $ar[$s]);
        $s = 'line-tidy';
        ArrayAdd($ar_sum, $s, $ar[$s]);

        // Col of ext
        foreach ($ar_ext as $s_ext => $i_cnt) {
            // This author has this ext
            if (isset($ar['line-ext-' . $s_ext])) {
                $s1 = 'line-ext-' . $s_ext;
                $s2 = 'line-ext-' . $s_ext . '-tidy';
                PrintLine($ar_col[$i_col], $ar, $s_ext);
                if (1 < GetCfg('git-stat.depth')
                        && GetCfg('git-stat.spacer'))
                    $ar_col[$i_col][] = '';
                $i_col++;

                // Sum
                ArrayAdd($ar_sum, $s1, $ar[$s1]);
                ArrayAdd($ar_sum, $s2, $ar[$s2]);
            }
            else {
                for ($i=1; $i<=GetCfg('git-stat.depth'); $i++)
                    $ar_col[$i_col][] = '';
                if (1 < GetCfg('git-stat.depth')
                        && GetCfg('git-stat.spacer'))
                    $ar_col[$i_col][] = '';
                $i_col++;
            }
        }
    }
    // Remove last spacer
    foreach ($ar_col as $k => $col)
        array_pop($ar_col[$k]);

    // Footer
    $i_col = 0;
    // Col 1, author
    $ar_col[$i_col][] = '-';
    $ar_col[$i_col++][] = 'Total';
    // Col 2, line
    $ar_col[$i_col][] = '-';
    $ar_col[$i_col++][] = $ar_sum['line-tidy'];
    foreach ($ar_ext as $s_ext => $i_cnt) {
        $ar_col[$i_col][] = '-';
        $ar_col[$i_col++][]
            = $ar_sum['line-ext-' . $s_ext . '-tidy'];
    }


    // Compute width of each col, if too wide, merge to col 'other'
    $ar_col_width = array();
    // If widht exceed 69, merge to other col.
    // 80 - len(900000 / 28) + 2 = 69
    $i_other = 0;
    $i_totalwidth = 0;
    foreach ($ar_col as $k => $col) {
        // Find col width, with 1 space before and after
        $i_width = 0;
        foreach ($col as $v)
            if ($i_width < mb_strwidth($v, 'utf-8'))
                $i_width = mb_strwidth($v, 'utf-8');
        $i_width += 2;
        $ar_col_width[$k] = $i_width;

        // Add 1 separator width, to count total line width
        $i_totalwidth += $i_width + 1;
        if (0 == $i_other && ($i_totalwidth
                > GetCfg('git-stat.width') - 11))
            // From this col, merge to other col.
            $i_other = $k;
    }
    if (0 < $i_other) {
        // Merge too many cols to other col
        MergeOther($ar_col, $i_other);

        // Change width of other col
        // Same with lines some rows before
        $i_width = 0;
        foreach ($ar_col[$i_other] as $v)
            if ($i_width < mb_strwidth($v, 'utf-8'))
                $i_width = mb_strwidth($v, 'utf-8');
        $i_width += 2;
        $ar_col_width[$i_other] = $i_width;
    }


    // Print
    $ar_out = array();
    foreach ($ar_col as $k => $col)
        foreach ($col as $key => $val)
            ArrayAdd($ar_out, $key
                , FixedWidth($val, $ar_col_width[$k]) . '|');
    foreach ($ar_out as $v) {
        // Remove last '|'
        $v = substr($v, 0, strlen($v) - 1);
        Ecl($v);
    }

    if (0 < $i_other) {
        echo 'Col \'other\' include ext: ';
        $ar = array_keys($ar_ext);
        for ($i=$i_other - 2; $i<count($ar); $i++)
            echo $ar[$i] . ' ';
        Ecl('');
    }
} // end of func PrintResult2


/**
 * Print tidy radio info
 */
function PrintTidy () {
    foreach (array(8, 12, 16, 19, 22, 23, 24, 25, 26, 27
            , 28
            , 29, 30, 32, 34, 37, 40, 44, 48, 53, 58) as $i) {
        echo 'Average line width: ' . ($i)
            . ', radio: '
            . round(GetTidyRadio($i) * 100, 2)
            . '%';
        Ecl('');
    }
} // end of func PrintTidy


/*
 * Print usage message
 */
function PrintUsage () {
    $s = basename(__FILE__);
    echo <<<EOF
Usage: $s [[+|-]Fext[,[+|-]Fext]]
       $s [h/help]
       $s [tidy|radio]

Parameters:
  -Fext         Only count info of files with these extension.
                Can use wildcard */? and prefix +/-, split by ','.
  -h/help       Print this help.
  -tidy|radio   Print tidy radio info.

Result format:
     Author |                     Sum                    | php
    ========|============================================|=====
      Name  | Total line after tidy / Average line width | ...
            |            Total line / Tidy keep radio    | ...
    --------|--------------------------------------------|-----
     Total  |           Sum of line after tidy           | ...

EOF;
/*
  -Author|Email Only count info of this author, or identify by email.
                Use same define rules with Fext.
*/
} // end of func PrintUsage


?>
