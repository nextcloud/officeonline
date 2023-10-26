<?php

namespace OCA\Officeonline\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class FilesScriptListener implements IEventListener {
	public function handle(Event $event): void {
		\OCP\Util::addScript('officeonline', 'files', 'viewer');
	}
}
