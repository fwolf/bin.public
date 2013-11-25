#! /bin/bash
#====================================================================
# rename_sp.sh
#
# Copyright 2009-2013, Fwolf <fwolf.aide+fwolfbin@gmail.com>
# All rights reserved.
#
# Distributed under the GNU General Public License, version 3.0.
#
# Remove/replace special chars in filename.
#====================================================================


# Print usage message
function PrintUsage {
    cat <<-EOF
Usage: `basename $0` [Options] Files

Options:
  -n    No Action: show what files would have been renamed.
EOF
}


# Check parameter amount
if [[ $# -lt 1 ]]; then
    PrintUsage
    exit 1
fi


# Rename single file
function RenameFile {
    echo "$1 --> $2"
    if [[ "x$NO_ACT" == "x0" ]]; then
        # Do actual rename
        mv "$1" "$2"
    fi
}


# Use '.' to join words, and '-' between names
CHAR_IN="\."
CHAR_OUT="\-"

# Define other special chars
CHAR_TO_IN="\ |'|\!"
CHAR_TO_OUT="\[|\]|\{|\}|\(|\)|\,|\"|\:|\&|\+|\_"

NO_ACT=0


# Scan option
I=0
while [[ $# -gt 0 ]]; do
    opt="$1"
    shift;
    case "$opt" in
        '-n')
            NO_ACT=1
            ;;
        *)
            if [[ "x${opt%/}" != "x$opt" ]]; then
                # Remove tailing '/' of directory
                F_TODO[$I]="${opt%/}"
                I=$((I + 1))
            else
                F_TODO[$I]="$opt"
                I=$((I + 1))
            fi
            ;;
    esac
done


# Scan files to rename
for F in "${F_TODO[@]}"
do
    F_NEW="${F%.*}"
    F_EXT="${F##*.}"
    if [[ "x$F_EXT" == "x$F_NEW" ]]; then
        # No extension, maybe is a directory
        F_EXT=""
    else
        # Prepend ext with dot
        F_EXT=.$F_EXT
    fi

    # 1. Replace splitter
    # 2. Remove head & tail special char
    # 3. Merge splitter
    F_NEW=$(echo $F_NEW | sed -r "\
        s/($CHAR_TO_IN)+/$CHAR_IN/g;
        s/($CHAR_TO_OUT)+/$CHAR_OUT/g;

        s/^($CHAR_TO_IN|$CHAR_TO_OUT|$CHAR_IN|$CHAR_OUT)+//g;
        s/($CHAR_TO_IN|$CHAR_TO_OUT|$CHAR_IN|$CHAR_OUT)+$//g;

        s/($CHAR_IN)+/$CHAR_IN/g;
        s/(($CHAR_OUT)+($CHAR_IN)?)/$CHAR_OUT/g;
        s/(($CHAR_IN)?($CHAR_OUT)+)/$CHAR_OUT/g;
    ")

    # Add file ext back
    F_NEW=$F_NEW$F_EXT

    if [[ "x$F" != "x$F_NEW" ]]; then
        # Only do rename when necessary
        RenameFile "$F" "$F_NEW"
    fi
done


#====================================================================
# ChangeLog
#
# v2.0 / 2013-09-15
#   Rewrite, drop dependence of linux rename command
#
# v1.1 / 2008-03-04
#====================================================================
