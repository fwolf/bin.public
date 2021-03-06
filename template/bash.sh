#! /usr/bin/env bash
#====================================================================
# filename.sh
#
# Description of this script.
#
# Other information like requirement, usage etc.
#
#
# Copyright 2017 Fwolf <fwolf.aide+bin.public@gmail.com>
# All rights reserved.
#
# Distributed under the GNU General Public License v3.0 or later.
# https://www.gnu.org/licenses/gpl.html
# Distributed under the GNU Lesser General Public License v3.0 or later.
# https://www.gnu.org/licenses/lgpl.html
# Distributed under the MIT License.
# https://opensource.org/licenses/MIT
#====================================================================


set -eu


# Print usage message
function printUsage {
    cat <<-EOF
Usage: `basename $0` P1 P2 [P3] [P4]

Parameters:
  -P1           Note for P1.
  -P2           Note for P2.
EOF
}


# Check parameter amount
if [[ $# -lt 2 ]]; then
    printUsage
    exit 1
fi


# Begin
P2R=${0%/*}/
source ${P2R}config.default.sh


#====================================================================
# ChangeLog
#
# Un-versioned
#   - Change copyright and changelog style
#
# v0.04 / 2011-08-18 / 707fbe7615
#   - Add execute of config.default.sh
#
# v0.03 / 2011-07-25 / 7374770e25
#   - Add hash in version history
#
# v0.02 / 2010-05-01 / 425f9743f7
#   - Chg header comment
#
# v0.01 / 2009-03-28 / ce1ba81e69
#====================================================================
