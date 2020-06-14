#!/usr/bin/env bash
BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
XDMOD_APPKERNEL_DIR="$( dirname "${BASEDIR}" )"
XDMOD_WSP_DIR="$( dirname "${XDMOD_APPKERNEL_DIR}" )"
# This script is executed in docker
# either on shippable or in local docker run
# mimicing shippable directory layout

# exit on first error
set -e
set -o pipefail

cd $XDMOD_APPKERNEL_DIR

export ORG_NAME=${ORG_NAME:-ubccr}
export REPO_NAME=${REPO_NAME:-xdmod-appkernel}
export REPO_FULL_NAME=${REPO_FULL_NAME:-$ORG_NAME/$REPO_NAME}
export SHIPPABLE_BUILD_DIR=${SHIPPABLE_BUILD_DIR:-$XDMOD_APPKERNEL_DIR}
export XDMOD_TEST_MODE=${XDMOD_TEST_MODE:-fresh_install}
export BRANCH=${BRANCH:-"$(git rev-parse --abbrev-ref HEAD)"}
export XDMOD_BRANCH=${XDMOD_BRANCH:-$BRANCH}
export AKRR_DIR=${AKRR_DIR:-$XDMOD_WSP_DIR/akrr}
export XDMOD_DIR=${XDMOD_DIR:-$XDMOD_WSP_DIR/xdmod}

export AKRR_USER=akrruser

echo XDMOD_APPKERNEL_DIR=$XDMOD_APPKERNEL_DIR
echo BRANCH=$BRANCH
echo XDMOD_BRANCH=$XDMOD_BRANCH
echo PWD="$(pwd)"
echo PATH=$PATH
echo USER=$USER
echo REPO_FULL_NAME=$REPO_FULL_NAME
echo SHIPPABLE_BUILD_DIR=$SHIPPABLE_BUILD_DIR
echo XDMOD_TEST_MODE=$XDMOD_TEST_MODE



# get xdmod
if [ ! -d "$XDMOD_DIR" ]; then
    git clone --depth=1 --branch=$XDMOD_BRANCH https://github.com/ubccr/xdmod.git ../xdmod
fi

# create link in XDMoD to xdmod-appkernels module
cd $XDMOD_DIR/open_xdmod/modules
if [ ! -d "$XDMOD_DIR/open_xdmod/modules/xdmod-appkernels" ]; then
    ln -s ../../../xdmod-appkernels .
fi
cd $XDMOD_APPKERNEL_DIR

# check that open_xdmod/modules/xdmod-appkernels linked to right directory
if [ "$(realpath "$XDMOD_DIR/open_xdmod/modules/xdmod-appkernels")" != "$(realpath "$XDMOD_APPKERNEL_DIR")" ]; then
    echo "$XDMOD_DIR/open_xdmod/modules/xdmod-appkernels do not point to $XDMOD_APPKERNEL_DIR"
fi


composer install -d ../xdmod --no-progress

# build xdmod rpms
cd ../xdmod
~/bin/buildrpm xdmod xdmod-appkernels
cd $XDMOD_APPKERNEL_DIR

# get akrr
git clone --depth=1 https://github.com/ubccr/akrr.git ../akrr
cd $AKRR_DIR
./make_rpm.sh
cd $XDMOD_APPKERNEL_DIR

XDMOD_BOOTSTRAP=$XDMOD_APPKERNEL_DIR/../xdmod/tests/ci/bootstrap.sh

# get phpunit
if [ ! -f "/usr/local/bin/phpunit" ]; then
    wget -O /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-4.phar
    chmod 755 /usr/local/bin/phpunit
fi


if [ "$XDMOD_TEST_MODE" = "fresh_install" ];
then
    $XDMOD_BOOTSTRAP

    # Turn on novice user mod
    sed -i 's@novice_user = "off"@novice_user = "on"@' /etc/xdmod/portal_settings.ini

    # Load mod_akrr and mod_appkernel db (created by AKRR)
    mysql -u root < $XDMOD_APPKERNEL_DIR/tests/artifacts/create_akrr_db.sql
    mysql -u root mod_akrr < $XDMOD_APPKERNEL_DIR/tests/artifacts/mod_akrr_xdmod_dev_test.sql
    mysql -u root mod_appkernel < $XDMOD_APPKERNEL_DIR/tests/artifacts/mod_appkernel_xdmod_dev_test.sql

    # install akrr
    yum install -y $AKRR_DIR/dist/akrr-*.noarch.rpm

    # copy akrr config
    cp -r ~/src/ubccr/xdmod-appkernels/tests/artifacts/akrr ~/
    mkdir -p ~/akrr/log/akrrd ~/akrr/log/comptasks ~/akrr/log/data

    # start akrr
    akrr daemon start

    # Configure xdmod appkernels
    expect $XDMOD_APPKERNEL_DIR/tests/ci/scripts/xdmod-appkernels-setup.tcl  | col -b

    # Ingest AK runs
    xdmod-akrr-ingestor -q -l load -c -r
    # Test report
    appkernel_reports_manager -m centerdirector -v -d -e 2019-02-28

    # Do tests
    cd $XDMOD_APPKERNEL_DIR/tests/unit
    phpunit --log-junit $XDMOD_APPKERNEL_DIR/shippable/testresults/results.xml \
        --coverage-xml $XDMOD_APPKERNEL_DIR/shippable/codecoverage/coverage.xml .

fi

if [ "$XDMOD_TEST_MODE" = "upgrade" ];
then
    $XDMOD_BOOTSTRAP
    echo "Update test is not yet implemented"
    exit 1
fi