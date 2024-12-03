<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Service;

use OCP\IUserManager;
use OCP\IUserSession;

class UserScopeService {
	public function __construct(IUserSession $userSession, IUserManager $userManager) {
		$this->userSession = $userSession;
		$this->userManager = $userManager;
	}

	/**
	 * Set a valid user in IUserSession since lots of server logic is relying on obtaining
	 * the current acting user from that
	 *
	 * @param $uid
	 * @throws \InvalidArgumentException
	 */
	public function setUserScope(?string $uid = null) {
		if ($uid === null) {
			return;
		}

		$user = $this->userManager->get($uid);
		if ($user === null) {
			throw new \InvalidArgumentException('No user found for the uid ' . $uid);
		}
		$this->userSession->setUser($user);
	}

	/**
	 * Setup the FS which is needed to emit hooks
	 *
	 * This is required for versioning/activity as the legacy filesystem hooks
	 * are not emitted if filesystem operations are executed though \OCP\Files\Node\File
	 *
	 * @param string $owner
	 */
	public function setFilesystemScope(string $owner) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($owner);
	}
}
