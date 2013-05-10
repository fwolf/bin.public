#! /bin/bash
#====================================================================
#	filename.sh
#
#	Copyright (c) 2013, Fwolf <fwolf.aide+fwolfbin@gmail.com>
#	All rights reserved.
#
#	Distributed under the GNU General Public License, version 3.0.
#	http://www.gnu.org/licenses/gpl.html
#	Distributed under the GNU Lesser General Public License, version 3.0.
#	http://www.gnu.org/licenses/lgpl.html
#	Distributed under the MIT License.
#	http://opensource.org/licenses/mit-license
#
#	Description of this script.
#
#	Other information like requirement, usage etc.
#====================================================================


# Print usage message
function PrintUsage {
	cat <<EOF
Usage: `basename $0` P1 P2 [P3] [P4]

Parameters:
  -P1           Note for P1.
  -P2           Note for P2.
EOF
} # end of func PrintUsage


# Check parameter amount
if [[ $# -lt 2 ]]; then
	PrintUsage
	exit 1
fi


# Begin
P2R=${0%/*}/
source ${P2R}config.default.sh


#====================================================================
#	ChangeLog
#
#	V 0.04 / 2011-08-18 / 707fbe7615
#		- Add execute of config.default.sh.
#
#	V 0.03 / 2011-07-25 / 7374770e25
#		- Add hash in version history.
#
#	V 0.02 / 2010-05-01 / 425f9743f7
#		- Chg header comment.
#
#	V 0.01 / 2009-03-28 / ce1ba81e69
#====================================================================
