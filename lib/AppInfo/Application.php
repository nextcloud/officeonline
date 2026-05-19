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
use OCA\Officeonline\Middleware\WOPIMiddleware;
use OCA\Officeonline\PermissionManager;
use OCA\Officeonline\Preview\MSExcel;
use OCA\Officeonline\Preview\MSWord;
use OCA\Officeonline\Preview\OOXML;
use OCA\Officeonline\Preview\OpenDocument;
use OCA\Officeonline\Preview\Pdf;
use OCA\Viewer\Event\LoadViewer;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Template\ITemplateManager;
use OCP\Files\Template\TemplateFileCreator;
use OCP\IConfig;
use OCP\IL10N;
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

		$context->registerPreviewProvider(MSExcel::class, MSExcel::MIMETYPE_REGEX);
		$context->registerPreviewProvider(MSWord::class, MSWord::MIMETYPE_REGEX);
		$context->registerPreviewProvider(OOXML::class, OOXML::MIMETYPE_REGEX);
		$context->registerPreviewProvider(OpenDocument::class, OpenDocument::MIMETYPE_REGEX);
		$context->registerPreviewProvider(Pdf::class, Pdf::MIMETYPE_REGEX);
	}

	public function boot(IBootContext $context): void {
		if (!$this->isEnabled()) {
			return;
		}

		$this->registerProvider();
		$this->registerNewFileCreators($context);
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

	public function registerProvider() {
		$container = $this->getContainer();

		// Register mimetypes
		/** @var Detection $detector */
		$detector = $container->query(\OCP\Files\IMimeTypeDetector::class);
		$detector->getAllMappings();
		$detector->registerType('ott', 'application/vnd.oasis.opendocument.text-template');
		$detector->registerType('ots', 'application/vnd.oasis.opendocument.spreadsheet-template');
		$detector->registerType('otp', 'application/vnd.oasis.opendocument.presentation-template');

		$container->query(WopiLockHooks::class)->register();
	}

	/**
	 * Strips the path and query parameters from the URL.
	 *
	 * @param string $url
	 * @return string
	 */
	private function domainOnly(string $url): string {
		$parsed_url = parse_url(trim($url));
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		return "$scheme$host$port";
	}

	private function registerNewFileCreators($context) {
		$context->injectFn(function (ITemplateManager $templateManager, IL10N $l10n, IConfig $config) {
			if (!$this->isEnabled()) {
				return;
			}
			$ooxml = $config->getAppValue(self::APP_ID, 'doc_format', '') === 'ooxml';
			$appPath = \OCP\Server::get(IAppManager::class)->getAppPath(self::APP_ID);
			$templateManager->registerTemplateFileCreator(function () use ($l10n, $ooxml, $appPath) {
				$odtType = new TemplateFileCreator('richdocuments', $l10n->t('New Document'), ($ooxml ? '.docx' : '.odt'));
				if ($ooxml) {
					$odtType->addMimetype('application/msword');
					$odtType->addMimetype('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
				} else {
					$odtType->addMimetype('application/vnd.oasis.opendocument.text');
					$odtType->addMimetype('application/vnd.oasis.opendocument.text-template');
				}
				$odtType->setIconClass('icon-filetype-document');
				$odtType->setIconSvgInline(file_get_contents($appPath . '/img/x-office-document.svg'));
				$odtType->setRatio(21 / 29.7);
				return $odtType;
			});
			$templateManager->registerTemplateFileCreator(function () use ($l10n, $ooxml, $appPath) {
				$odsType = new TemplateFileCreator('richdocuments', $l10n->t('New Spreadsheet'), ($ooxml ? '.xlsx' : '.ods'));
				if ($ooxml) {
					$odsType->addMimetype('application/vnd.ms-excel');
					$odsType->addMimetype('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				} else {
					$odsType->addMimetype('application/vnd.oasis.opendocument.spreadsheet');
					$odsType->addMimetype('application/vnd.oasis.opendocument.spreadsheet-template');
				}
				$odsType->setIconClass('icon-filetype-spreadsheet');
				$odsType->setIconSvgInline(file_get_contents($appPath . '/img/x-office-spreadsheet.svg'));
				$odsType->setRatio(16 / 9);
				return $odsType;
			});
			$templateManager->registerTemplateFileCreator(function () use ($l10n, $ooxml, $appPath) {
				$odpType = new TemplateFileCreator('richdocuments', $l10n->t('New Presentation'), ($ooxml ? '.pptx' : '.odp'));
				if ($ooxml) {
					$odpType->addMimetype('application/vnd.ms-powerpoint');
					$odpType->addMimetype('application/vnd.openxmlformats-officedocument.presentationml.presentation');
				} else {
					$odpType->addMimetype('application/vnd.oasis.opendocument.presentation');
					$odpType->addMimetype('application/vnd.oasis.opendocument.presentation-template');
				}
				$odpType->setIconClass('icon-filetype-presentation');
				$odpType->setIconSvgInline(file_get_contents($appPath . '/img/x-office-presentation.svg'));
				$odpType->setRatio(16 / 9);
				return $odpType;
			});
		});
	}
}
