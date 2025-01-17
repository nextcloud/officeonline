<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 Collabora Productivity
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Officeonline\Preview;

use OC\Preview\Provider;
use OCA\Officeonline\Capabilities;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Image;
use Psr\Log\LoggerInterface;

abstract class Office extends Provider {

	/** @var IClientService */
	private $clientService;

	/** @var IConfig */
	private $config;

	/** @var array */
	private $capabilitites;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IClientService $clientService, IConfig $config, Capabilities $capabilities, LoggerInterface $logger) {
		$this->clientService = $clientService;
		$this->config = $config;
		$this->capabilitites = $capabilities->getCapabilities()['officeonline'];
		$this->logger = $logger;
	}

	private function getWopiURL() {
		return $this->config->getAppValue('officeonline', 'wopi_url');
	}

	public function isAvailable(\OCP\Files\FileInfo $file) {
		if (isset($this->capabilitites['collabora']['convert-to']['available'])) {
			return (bool)$this->capabilitites['collabora']['convert-to']['available'];
		}
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$fileInfo = $fileview->getFileInfo($path);
		if (!$fileInfo || $fileInfo->getSize() === 0) {
			return false;
		}

		$useTempFile = $fileInfo->isEncrypted() || !$fileInfo->getStorage()->isLocal();
		if ($useTempFile) {
			$fileName = $fileview->toTmpFile($path);
			$stream = fopen($fileName, 'r');
		} else {
			$stream = $fileview->fopen($path, 'r');
		}

		$client = $this->clientService->newClient();
		$options = ['timeout' => 10];

		if ($this->config->getAppValue('officeonline', 'disable_certificate_verification') === 'yes') {
			$options['verify'] = false;
		}

		$options['multipart'] = [['name' => $path, 'contents' => $stream]];

		try {
			$response = $client->post($this->getWopiURL() . '/lool/convert-to/png', $options);
		} catch (\Exception $e) {
			$this->logger->info('Failed to convert file to preview', [
				'exception' => $e,
				'app' => 'officeonline',
			]);
			return false;
		}

		$image = new Image();
		$image->loadFromData($response->getBody());

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);
			return $image;
		}
		return false;
	}
}
