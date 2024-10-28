<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Settings;

use OCA\Officeonline\Capabilities;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Section implements IIconSection {
	/** @var IL10N */
	private $l10n;
	/** @var IURLGenerator */
	private $url;
	/** @var Capabilities */
	private $capabilitites;

	/**
	 * @param IL10N $l
	 * @param IURLGenerator $url
	 */
	public function __construct(IL10N $l10n, IURLGenerator $url, Capabilities $capabilities) {
		$this->l10n = $l10n;
		$this->url = $url;
		$this->capabilitites = $capabilities;
	}
	/**
	 * {@inheritdoc}
	 */
	public function getID() {
		return 'officeonline';
	}
	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		$capabilitites = $this->capabilitites->getCapabilities();
		if (isset($capabilitites['officeonline']['productName'])) {
			return $capabilitites['officeonline']['productName'];
		}
		return $this->l10n->t('Office Online');
	}
	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 75;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon() {
		return $this->url->imagePath('officeonline', 'app-dark.svg');
	}
}
