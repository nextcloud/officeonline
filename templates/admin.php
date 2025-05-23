<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2026 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

script('officeonline', 'officeonline-admin');

/** @var array $_ */
?>
<div id="admin-vue" data-initial="<?php p(json_encode($_['settings'], true)); ?>"></div>
