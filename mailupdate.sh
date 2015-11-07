#!/bin/bash

X=`which "$0"`
Y=`readlink -e "$X"`
cd `dirname "$Y"`
/usr/bin/php ./twitterlosses.php > dump.txt
if [ -s 'dump.txt' ] ; then
    ( echo -e 'From: TwitterLosses <icculus@icculus.org>\nTo: icculus@icculus.org\nSubject: Twitter follower update (icculus)\nContent-Type: text/plain; charset=UTF-8\n\n' ; cat dump.txt ) |/var/qmail/bin/qmail-inject
fi

rm -f dump.txt

