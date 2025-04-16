#!/bin/bash
# Bootstrap script that configures the appkernel-specific services and
# data.

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
XDMOD_BOOTSTRAP=$BASEDIR/../../../../xdmod/tests/ci/bootstrap.sh

set -e
set -o pipefail

if [ "$XDMOD_TEST_MODE" = "fresh_install" ];
then
    $XDMOD_BOOTSTRAP

    # Turn on novice user mod
    sed -i 's@novice_user = "off"@novice_user = "on"@' /etc/xdmod/portal_settings.ini

# Initiate database
mysql -u root << END
create database mod_akrr CHARACTER SET utf8;
create database mod_appkernel CHARACTER SET utf8;
CREATE USER 'akrruser'@'localhost' IDENTIFIED BY 'akrruser';
GRANT ALL ON mod_akrr.* TO 'akrruser'@'localhost';
GRANT ALL ON mod_appkernel.* TO 'akrruser'@'localhost';
GRANT SELECT ON modw.* TO 'akrruser'@'localhost';
GRANT ALL ON mod_akrr.* TO 'xdmod'@'localhost';
GRANT ALL ON mod_appkernel.* TO 'xdmod'@'localhost';
END

    expect $BASEDIR/xdmod-setup.tcl | col -b
    aggregate_supremm.sh
fi

if [ "$XDMOD_TEST_MODE" = "upgrade" ];
then
    $XDMOD_BOOTSTRAP
    echo "Update test is not yet implemented"
    exit 1
fi
