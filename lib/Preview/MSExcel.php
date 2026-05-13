<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 Collabora Productivity
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Officeonline\Preview;

class MSExcel extends Office {
	public const MIMETYPE_REGEX = '/application\/vnd.ms-excel/';

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return self::MIMETYPE_REGEX;
	}
}
