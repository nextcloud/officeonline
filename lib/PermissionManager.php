<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline;

use OCA\Officeonline\AppInfo\Application;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class PermissionManager {

	/** @var IConfig */
	private $config;
	/** @var IGroupManager */
	private $groupManager;

	public function __construct(IConfig $config,
		IGroupManager $groupManager) {
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param string $groupString
	 * @return array
	 */
	private function splitGroups($groupString) {
		return explode('|', $groupString);
	}

	public function isEnabledForUser(IUser $user) {
		$enabledForGroups = $this->config->getAppValue(Application::APP_ID, 'use_groups', '');
		if ($enabledForGroups === '') {
			return true;
		}

		$groups = $this->splitGroups($enabledForGroups);
		$uid = $user->getUID();
		foreach ($groups as $group) {
			if ($this->groupManager->isInGroup($uid, $group)) {
				return true;
			}
		}

		return false;
	}
}
