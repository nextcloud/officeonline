<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\WOPI;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;

class DiscoveryManager {
	/** @var IClientService */
	private $clientService;
	/** @var ISimpleFolder */
	private $appData;
	/** @var IConfig */
	private $config;
	/** @var IL10N */
	private $l10n;
	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param IClientService $clientService
	 * @param IAppData $appData
	 * @param IConfig $config
	 * @param IL10N $l10n
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(IClientService $clientService,
		IAppData $appData,
		IConfig $config,
		IL10N $l10n,
		ITimeFactory $timeFactory) {
		$this->clientService = $clientService;
		try {
			$this->appData = $appData->getFolder('officeonline');
		} catch (NotFoundException $e) {
			$this->appData = $appData->newFolder('officeonline');
		}
		$this->config = $config;
		$this->timeFactory = $timeFactory;
	}

	public function get() {
		// First check if there is a local valid discovery file
		try {
			$file = $this->appData->getFile('discovery.xml');
			$decodedFile = json_decode($file->getContent(), true);
			if ($this->timeFactory->getTime() < $decodedFile['timestamp'] + 3600) {
				return $decodedFile['data'];
			}
		} catch (NotFoundException $e) {
			$file = $this->appData->newFile('discovery.xml');
		}

		$response = $this->fetchFromRemote();

		$responseBody = $response->getBody();
		$file->putContent(
			json_encode([
				'data' => $responseBody,
				'timestamp' => $this->timeFactory->getTime(),
			])
		);
		return $responseBody;
	}

	/**
	 * @return \OCP\Http\Client\IResponse
	 * @throws \Exception
	 */
	public function fetchFromRemote() {
		$remoteHost = $this->config->getAppValue('officeonline', 'wopi_url');
		$wopiDiscovery = trim($remoteHost . '/hosting/discovery');

		$client = $this->clientService->newClient();
		$options = ['timeout' => 45, 'nextcloud' => ['allow_local_address' => true]];

		if ($this->config->getAppValue('officeonline', 'disable_certificate_verification') === 'yes') {
			$options['verify'] = false;
		}

		try {
			return $client->get($wopiDiscovery, $options);
		} catch (\Exception $e) {
			throw $e;
		}
	}

	public function refretch() {
		try {
			$this->appData->getFile('discovery.xml')->delete();
		} catch (\Exception $e) {
		}
	}
}
