<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Controller;

use Exception;
use OCA\Officeonline\AppConfig;
use OCA\Officeonline\WOPI\DiscoveryManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class SettingsController extends Controller {

	/** @var IL10N */
	private $l10n;
	/** @var AppConfig */
	private $appConfig;
	/** @var DiscoveryManager */
	private $discoveryManager;
	/** @var LoggerInterface */
	private $logger;

	public function __construct($appName,
		IRequest $request,
		IL10N $l10n,
		AppConfig $appConfig,
		DiscoveryManager $discoveryManager,
		LoggerInterface $logger
	) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->appConfig = $appConfig;
		$this->discoveryManager = $discoveryManager;
		$this->logger = $logger;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function checkSettings(): DataResponse {
		try {
			$response = $this->discoveryManager->fetchFromRemote();
		} catch (Exception $e) {
			$this->logger->error('Could not fetch discovery details', ['app' => 'officeonline', 'exception' => $e]);
			return new DataResponse([
				'status' => $e->getCode(),
				'message' => 'Could not fetch discovery details'
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 */
	public function getSettings(): JSONResponse {
		return new JSONResponse([
			'wopi_url' => $this->appConfig->getAppValue('wopi_url'),
			'public_wopi_url' => $this->appConfig->getAppValue('public_wopi_url'),
			'disable_certificate_verification' => $this->appConfig->getAppValue('disable_certificate_verification') === 'yes',
			'edit_groups' => $this->appConfig->getAppValue('edit_groups'),
			'use_groups' => $this->appConfig->getAppValue('use_groups'),
			'doc_format' => $this->appConfig->getAppValue('doc_format'),
		]);
	}

	public function setSettings($wopi_url,
		$disable_certificate_verification,
		$edit_groups,
		$use_groups,
		$doc_format,
		$external_apps,
		$canonical_webroot): JSONResponse {
		$message = $this->l10n->t('Saved');

		if ($wopi_url !== null) {
			$this->appConfig->setAppValue('wopi_url', $wopi_url);
		}

		if ($disable_certificate_verification !== null) {
			$this->appConfig->setAppValue(
				'disable_certificate_verification',
				$disable_certificate_verification === true ? 'yes' : ''
			);
		}

		if ($edit_groups !== null) {
			$this->appConfig->setAppValue('edit_groups', $edit_groups);
		}

		if ($use_groups !== null) {
			$this->appConfig->setAppValue('use_groups', $use_groups);
		}

		if ($doc_format !== null) {
			$this->appConfig->setAppValue('doc_format', $doc_format);
		}

		if ($external_apps !== null) {
			$this->appConfig->setAppValue('external_apps', $external_apps);
		}

		if ($canonical_webroot !== null) {
			$this->appConfig->setAppValue('canonical_webroot', $canonical_webroot);
		}

		$this->discoveryManager->refretch();

		$response = [
			'status' => 'success',
			'data' => ['message' => $message]
		];

		return new JSONResponse($response);
	}
}
