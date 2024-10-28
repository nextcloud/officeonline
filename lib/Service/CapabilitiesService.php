<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Service;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;

class CapabilitiesService {

	/** @var IConfig */
	private $config;
	/** @var IClientService */
	private $clientService;
	/** @var ISimpleFolder */
	private $appData;
	/** @var array */
	private $capabilities;

	public function __construct(IConfig $config, IClientService $clientService, IAppData $appData) {
		$this->config = $config;
		$this->clientService = $clientService;
		try {
			$this->appData = $appData->getFolder('officeonline');
		} catch (NotFoundException $e) {
			$this->appData = $appData->newFolder('officeonline');
		}
	}


	public function getCapabilities() {
		if ($this->capabilities) {
			$isCODEInstalled = $this->getContainer()->getServer()->getAppManager()->isEnabledForUser('officeonlinecode');
			$isCODEEnabled = strpos($this->config->getAppValue('officeonline', 'wopi_url'), 'proxy.php?req=') !== false;
			if ($isCODEInstalled && $isCODEEnabled && count($this->capabilities) === 0) {
				$this->refretch();
			}
			return $this->capabilities;
		}
		try {
			$file = $this->appData->getFile('capabilities.json');
			$decodedFile = \json_decode($file->getContent(), true);
		} catch (NotFoundException $e) {
			return [];
		}

		if (!is_array($decodedFile)) {
			return [];
		}
		$this->capabilities = $decodedFile;

		return $this->capabilities;
	}

	public function hasTemplateSaveAs() {
		return $this->getCapabilities()['hasTemplateSaveAs'] ?? false;
	}

	public function hasTemplateSource() {
		return $this->getCapabilities()['hasTemplateSource'] ?? false;
	}

	private function getFile() {
		try {
			$file = $this->appData->getFile('capabilities.json');
		} catch (NotFoundException $e) {
			$file = $this->appData->newFile('capabilities.json');
			$file->putContent(json_encode([]));
		}

		return $file;
	}

	public function clear() {
		$file = $this->getFile();
		$file->putContent(json_encode([]));
	}

	public function refretch() {
		$capabilties = $this->renewCapabilities();

		if ($capabilties !== []) {
			$file = $this->getFile();
			$file->putContent(json_encode($capabilties));
		}
	}

	private function renewCapabilities() {
		$remoteHost = $this->config->getAppValue('officeonline', 'wopi_url');
		if ($remoteHost === '') {
			return [];
		}
		$capabilitiesEndpoint = $remoteHost . '/hosting/capabilities';

		$client = $this->clientService->newClient();
		$options = ['timeout' => 45, 'nextcloud' => ['allow_local_address' => true]];

		if ($this->config->getAppValue('officeonline', 'disable_certificate_verification') === 'yes') {
			$options['verify'] = false;
		}

		try {
			$response = $client->get($capabilitiesEndpoint, $options);
		} catch (\Exception $e) {
			return [];
		}

		$responseBody = $response->getBody();
		$ret = \json_decode($responseBody, true);

		if (!is_array($ret)) {
			return [];
		}

		return $ret;
	}
}
