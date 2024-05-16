<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc., Victor Dubiniuk <victor.dubiniuk@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline;

use OCA\Officeonline\AppInfo\Application;
use OCA\Officeonline\Service\FederationService;
use OCP\App\IAppManager;
use OCP\GlobalScale\IConfig as GlobalScaleConfig;
use OCP\IConfig;

class AppConfig {
	private $defaults = [
		'wopi_url' => '',
		'doc_format' => 'ooxml'
	];

	public const APP_SETTING_TYPES = [];
	public const SYSTEM_GS_TRUSTED_HOSTS = 'gs.trustedHosts';
	public const FEDERATION_USE_TRUSTED_DOMAINS = 'federation_use_trusted_domains';

	/** @var IConfig */
	private $config;
	private IAppManager $appManager;
	private GlobalScaleConfig $globalScaleConfig;

	public function __construct(
		IConfig $config,
		IAppManager $appManager,
		GlobalScaleConfig $globalScaleConfig,
	) {
		$this->config = $config;
		$this->appManager = $appManager;
		$this->globalScaleConfig = $globalScaleConfig;
	}

	public function getAppNamespace($key): string {
		return Application::APP_ID;
	}

	public function getAppValue(string $key) {
		$defaultValue = null;
		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}
		return $this->config->getAppValue($this->getAppNamespace($key), $key, $defaultValue);
	}

	public function getAppValueArray(string $key) {
		$value = $this->config->getAppValue($this->getAppNamespace($key), $key, []);
		if (array_key_exists($key, self::APP_SETTING_TYPES) && self::APP_SETTING_TYPES[$key] === 'array') {
			$value = $value !== '' ? explode(',', $value) : [];
		}
		return $value;
	}

	public function setAppValue(string $key, string $value): void {
		$this->config->setAppValue($this->getAppNamespace($key), $key, $value);
	}

	public function getAppSettings(): array {
		$result = [];
		$keys = $this->config->getAppKeys(Application::APP_ID);
		foreach ($keys as $key) {
			$value = $this->getAppValueArray($key);
			$value = $value === 'yes' ? true : $value;
			$result[$key] = $value === 'no' ? false : $value;
		}
		return $result;
	}

	public function getDomainList(): array {
		$urls = array_merge(
			[ $this->domainOnly($this->getCollaboraUrlPublic()) ],
			$this->getFederationDomains(),
			$this->getGSDomains()
		);

		return array_map(fn ($url) => idn_to_ascii($url), array_filter($urls));
	}

	public function domainOnly(string $url): string {
		$parsedUrl = parse_url($url);
		$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
		$host = $parsedUrl['host'] ?? '';
		$port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
		return "$scheme$host$port";
	}

	public function getCollaboraUrlPublic(): string {
		return $this->config->getAppValue(Application::APP_ID, 'public_wopi_url', $this->getCollaboraUrlInternal());
	}

	public function getCollaboraUrlInternal(): string {
		return $this->config->getAppValue(Application::APP_ID, 'wopi_url', '');
	}

	private function getFederationDomains(): array {
		if (!$this->appManager->isEnabledForUser('federation')) {
			return [];
		}

		/** @var FederationService $federationService */
		$federationService = \OCP\Server::get(FederationService::class);
		$trustedNextcloudDomains = array_filter(array_map(function ($server) use ($federationService) {
			return $federationService->isTrustedRemote($server) ? $server : null;
		}, $federationService->getTrustedServers()));

		$trustedCollaboraDomains = array_filter(array_map(function ($server) use ($federationService) {
			try {
				return $federationService->getRemoteCollaboraURL($server);
			} catch (\Exception $e) {
				// If there is no remote collabora server we can just skip that
				return null;
			}
		}, $trustedNextcloudDomains));

		return array_map(function ($url) {
			return $this->domainOnly($url);
		}, array_merge($trustedNextcloudDomains, $trustedCollaboraDomains));
	}

	private function getGSDomains(): array {
		if (!$this->globalScaleConfig->isGlobalScaleEnabled()) {
			return [];
		}

		return $this->getGlobalScaleTrustedHosts();
	}

	public function getGlobalScaleTrustedHosts(): array {
		return $this->config->getSystemValue(self::SYSTEM_GS_TRUSTED_HOSTS, []);
	}

	public function isTrustedDomainAllowedForFederation(): bool {
		return $this->config->getAppValue(Application::APP_ID, self::FEDERATION_USE_TRUSTED_DOMAINS, 'no') === 'yes';
	}
}
