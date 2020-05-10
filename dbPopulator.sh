#!/bin/sh

$BROWSER "http://localhost/stuff/dbPopulator/"

case $1 in
    /*) PTH="$1" ;; # absolute path
    *) PTH="${PWD}/${1}" ;; # relative path
esac

sudo cp /tmp/dbPopulatorOutput.sql "${PTH}"
sudo chown "$(whoami)" "${PTH}"
