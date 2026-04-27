<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline;

use Exception;
use OCA\Officeonline\WOPI\Parser;
use OCP\Capabilities\ICapability;
use OCP\IL10N;

class Capabilities implements ICapability {
	public const MIMETYPES = [
		'application/vnd.oasis.opendocument.text',
		'application/vnd.oasis.opendocument.spreadsheet',
		'application/vnd.oasis.opendocument.graphics',
		'application/vnd.oasis.opendocument.presentation',
		'application/vnd.lotus-wordpro',
		'application/vnd.visio',
		'application/vnd.wordperfect',
		'application/msonenote',
		'application/msword',
		'application/rtf',
		'text/rtf',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
		'application/vnd.ms-word.document.macroEnabled.12',
		'application/vnd.ms-word.template.macroEnabled.12',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
		'application/vnd.ms-excel.sheet.macroEnabled.12',
		'application/vnd.ms-excel.template.macroEnabled.12',
		'application/vnd.ms-excel.addin.macroEnabled.12',
		'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
		'application/vnd.ms-powerpoint',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'application/vnd.openxmlformats-officedocument.presentationml.template',
		'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'application/vnd.ms-powerpoint.addin.macroEnabled.12',
		'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
		'application/vnd.ms-powerpoint.template.macroEnabled.12',
		'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
		'text/csv'
	];

	public const MIMETYPES_OPTIONAL = [
		'application/pdf',
	];

	public function __construct(
		private IL10N $l10n,
		private AppConfig $config,
		private Parser $parser,
	) {
	}

	public function getCapabilities(): array {
		// @todo: limit capabilities to users enabled for office or guests (where it depends on the share owner if they have access)
		$discoveryResponse = false;
		try {
			$discoveryResponse = $this->parser->getParsed();
		} catch (Exception $e) {
		}
		return [
			'officeonline' => [
				'discovery' => $discoveryResponse,
				'mimetypes' => self::MIMETYPES,
				// @todo: consider making `application/pdf` conditional (i.e. on presence of `files_pdfviewer`)
				'mimetypesNoDefaultOpen' => self::MIMETYPES_OPTIONAL,
				'templates' => false,
				'productName' => $this->l10n->t('Office Online'),
				'config' => [
					'wopi_url' => $this->config->getAppValue('wopi_url'),
					'public_wopi_url' => $this->config->getAppValue('public_wopi_url'),
					'disable_certificate_verification' => $this->config->getAppValue('disable_certificate_verification'),
					'edit_groups' => $this->config->getAppValue('edit_groups'),
					'use_groups' => $this->config->getAppValue('use_groups'),
					'doc_format' => $this->config->getAppValue('doc_format'),
				]
			],
		];
	}
}
