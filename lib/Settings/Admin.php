<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Settings;

use OCA\Officeonline\AppConfig;
use OCA\Officeonline\TemplateManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	/** @var IConfig */
	private $config;

	/** @var AppConfig */
	private $appConfig;

	public function __construct(
		IConfig $config,
		AppConfig $appConfig,
		TemplateManager $manager,
	) {
		$this->config = $config;
		$this->appConfig = $appConfig;
		$this->manager = $manager;
	}

	public function getForm(): TemplateResponse {
		return new TemplateResponse(
			'officeonline',
			'admin',
			[
				'settings' => [
					'wopi_url' => $this->config->getAppValue('officeonline', 'wopi_url'),
					'edit_groups' => $this->config->getAppValue('officeonline', 'edit_groups'),
					'use_groups' => $this->config->getAppValue('officeonline', 'use_groups'),
					'doc_format' => $this->config->getAppValue('officeonline', 'doc_format', 'ooxml'),
					'external_apps' => $this->config->getAppValue('officeonline', 'external_apps'),
					'canonical_webroot' => $this->config->getAppValue('officeonline', 'canonical_webroot'),
					'disable_certificate_verification' => $this->config->getAppValue('officeonline', 'disable_certificate_verification', '') === 'yes',
					'templatesAvailable' => false,
					'settings' => $this->appConfig->getAppSettings(),
				]
			],
			'blank'
		);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'officeonline';
	}
	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	public function getPriority() {
		return 0;
	}
}
