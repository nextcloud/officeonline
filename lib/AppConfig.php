<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline;

use OCA\Officeonline\AppInfo\Application;
use OCP\IConfig;

class AppConfig {
	private $defaults = [
		'wopi_url' => '',
		'doc_format' => 'ooxml'
	];

	public const APP_SETTING_TYPES = [];

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
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
}
