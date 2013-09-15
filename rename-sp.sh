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
CHAR_TO_IN="\ |\'|\!"
CHAR_TO_OUT="\[|\]|\{|\}|\(|\)|\,|\"|\:|\&|\+|\_"

NO_ACT=0


# Scan parameters for option
I=0
for O in "$@"
do
    if [[ "x$O" == "x-n" ]]; then
        NO_ACT=1
    elif [[ "x${O%/}" != "x$O" ]]; then
        # Remove tailing '/' of directory
        F_TODO[$I]="${O%/}"
        I=$((I + 1))
    else
        F_TODO[$I]="$O"
        I=$((I + 1))
    fi
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

    # Replace splitter
    F_NEW=$(echo $F_NEW | sed -r "s/($CHAR_TO_IN)+/$CHAR_IN/g")
    F_NEW=$(echo $F_NEW | sed -r "s/($CHAR_TO_OUT)+/$CHAR_OUT/g")

    # Remove head & tail special char
    F_NEW=$(echo $F_NEW | sed -r "s/^($CHAR_TO_IN|$CHAR_TO_OUT|$CHAR_IN|$CHAR_OUT)+//g")
    F_NEW=$(echo $F_NEW | sed -r "s/($CHAR_TO_IN|$CHAR_TO_OUT|$CHAR_IN|$CHAR_OUT)+$//g")

    # Merge splitter
    F_NEW=$(echo $F_NEW | sed -r "s/($CHAR_IN)+/$CHAR_IN/g")
    F_NEW=$(echo $F_NEW | sed -r "s/(($CHAR_OUT)+($CHAR_IN)?)/$CHAR_OUT/g")
    F_NEW=$(echo $F_NEW | sed -r "s/(($CHAR_IN)?($CHAR_OUT)+)/$CHAR_OUT/g")

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
# V 2.0 / 2013-09-15
#   Rewrite, drop dependence of linux rename command
#
# V 1.1 / 2008-03-04
#====================================================================
