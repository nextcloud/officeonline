<?php

declare(strict_types=1);

namespace OCA\Officeonline\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadViewerListener implements IEventListener {
	public function handle(Event $event): void {
		Util::addScript('officeonline', 'viewer', 'viewer');
	}
}
