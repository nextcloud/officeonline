<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 Collabora Productivity
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Officeonline\Preview;

use OCA\Officeonline\AppConfig;
use OCA\Officeonline\Capabilities;
use OCP\Files\File;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IImage;
use OCP\Image;
use OCP\Preview\IProviderV2;
use Psr\Log\LoggerInterface;

abstract class Office implements IProviderV2 {
	private array $capabilitites;

	public function __construct(
		private IClientService $clientService,
		private IConfig $config,
		private AppConfig $appConfig,
		Capabilities $capabilities,
		private LoggerInterface $logger,
	) {
		$this->capabilitites = $capabilities->getCapabilities()['officeonline'] ?? [];
	}

	public function isAvailable(\OCP\Files\FileInfo $file): bool {
		if (isset($this->capabilitites['collabora']['convert-to']['available'])) {
			return (bool)$this->capabilitites['collabora']['convert-to']['available'];
		}
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if ($file->getSize() === 0) {
			return null;
		}

		$useTempFile = $file->isEncrypted() || !$file->getStorage()->isLocal();
		if ($useTempFile) {
			$fileName = $file->getStorage()->getLocalFile($file->getInternalPath());
			$stream = fopen($fileName, 'r');
		} else {
			$stream = $file->fopen('r');
		}

		$client = $this->clientService->newClient();
		$options = ['timeout' => 10];

		if ($this->config->getAppValue('officeonline', 'disable_certificate_verification') === 'yes') {
			$options['verify'] = false;
		}

		$options['multipart'] = [['name' => $file->getName(), 'contents' => $stream]];

		try {
			$response = $client->post($this->appConfig->getCollaboraUrlInternal() . '/cool/convert-to/png', $options);
		} catch (\Exception $e) {
			$this->logger->info('Failed to convert file to preview: ' . $e->getMessage(), [
				'exception' => $e,
				'app' => 'officeonline',
			]);
			return null;
		}

		$image = new Image();
		$image->loadFromData($response->getBody());

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);
			return $image;
		}
		return null;
	}
}
