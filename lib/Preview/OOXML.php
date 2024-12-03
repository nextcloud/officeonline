<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 Collabora Productivity
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Officeonline\Preview;

class OOXML extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.*/';
	}
}
