#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

OC_PATH=../../../
OCC=${OC_PATH}occ
SCENARIO_TO_RUN=$1
HIDE_OC_LOGS=$2
APP_NAME=officeonline

INSTALLED=$($OCC status | grep installed: | cut -d " " -f 5)

if [ "$INSTALLED" == "true" ]; then
    $OCC app:enable $APP_NAME
else
    if [ "$SCENARIO_TO_RUN" != "setup_features/setup.feature" ]; then
        echo "Nextcloud instance needs to be installed" >&2
        exit 1
    fi
fi

composer install
composer dump-autoload

curl -v http://collabora:9980/hosting/capabilities

PORT=8080
php -S localhost:$PORT -t $OC_PATH &
PHPPID=$!
echo $PHPPID


#export BEHAT_PARAMS="context[parameters][base_url]=https://nextcloud.local.dev.bitgrid.net"
$OCC config:app:set officeonline wopi_url --value="http://collabora:9980"
$OCC config:app:set officeonline public_wopi_url --value="http://collabora:9980"


vendor/bin/behat
RESULT=$?

kill $PHPPID

echo "runsh: Exit code: $RESULT"
exit $RESULT
