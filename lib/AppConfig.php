<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 * @copyright 2015 Victor Dubiniuk victor.dubiniuk@gmail.com
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Victor Dubiniuk <victor.dubiniuk@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Officeonline;

use OCA\Officeonline\AppInfo\Application;
use \OCP\IConfig;

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
