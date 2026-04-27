<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\AppInfo;

use OC\Files\Type\Detection;
use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Officeonline\Capabilities;
use OCA\Officeonline\Hooks\WopiLockHooks;
use OCA\Officeonline\Listener\AddContentSecurityPolicyListener;
use OCA\Officeonline\Listener\LoadViewerListener;
use OCA\Officeonline\Listener\SharingLoadAdditionalScriptsListener;
use OCA\Officeonline\Listener\RegisterTemplateFileCreatorListener;
use OCA\Officeonline\Middleware\WOPIMiddleware;
use OCA\Officeonline\PermissionManager;
use OCA\Officeonline\Preview\MSExcel;
use OCA\Officeonline\Preview\MSWord;
use OCA\Officeonline\Preview\OOXML;
use OCA\Officeonline\Preview\OpenDocument;
use OCA\Officeonline\Preview\Pdf;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Template\RegisterTemplateCreatorEvent;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'officeonline';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerMiddleWare(WOPIMiddleware::class);

		$context->registerEventListener(AddContentSecurityPolicyEvent::class, AddContentSecurityPolicyListener::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, SharingLoadAdditionalScriptsListener::class);
		$context->registerEventListener(LoadViewer::class, LoadViewerListener::class);
		$context->registerEventListener(RegisterTemplateCreatorEvent::class, RegisterTemplateFileCreatorListener::class);

		$context->registerPreviewProvider('/application\/vnd.ms-excel/', MSExcel::class);
		$context->registerPreviewProvider('/application\/msword/', MSWord::class);
		$context->registerPreviewProvider('/application\/vnd.openxmlformats-officedocument.*/', OOXML::class);
		$context->registerPreviewProvider('/application\/vnd.oasis.opendocument.*/', OpenDocument::class);
		$context->registerPreviewProvider('/application\/pdf/', Pdf::class);
	}

	public function boot(IBootContext $context): void {
		if (!$this->isEnabled()) {
			return;
		}

		$this->registerLegacyHooks();
	}

	public function isEnabled(): bool {
		$currentUser = \OC::$server->getUserSession()->getUser();
		if ($currentUser !== null) {
			/** @var PermissionManager $permissionManager */
			$permissionManager = \OC::$server->query(PermissionManager::class);
			if (!$permissionManager->isEnabledForUser($currentUser)) {
				return false;
			}
		}

		return true;
	}

	private function registerLegacyHooks(): void {
		$this->getContainer()->query(WopiLockHooks::class)->register();
	}
}
