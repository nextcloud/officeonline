<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class FilesScriptListener implements IEventListener {
	public function handle(Event $event): void {
		\OCP\Util::addScript('officeonline', 'officeonline-files', 'viewer');
	}
}
