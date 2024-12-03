<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline;

use DateTime;
use DateTimeZone;
use OCP\Files\Folder;

class Helper {

	/** @var string|null */
	private $userId;

	public function __construct($userId) {
		$this->userId = $userId;
	}

	/**
	 * @param string $fileId
	 * @return array
	 * @throws \Exception
	 */
	public static function parseFileId($fileId) {
		$arr = explode('_', $fileId);
		$templateId = null;
		if (count($arr) === 1) {
			$fileId = $arr[0];
			$instanceId = '';
			$version = '0';
		} elseif (count($arr) === 2) {
			[$fileId, $instanceId] = $arr;
			$version = '0';
		} elseif (count($arr) === 3) {
			[$fileId, $instanceId, $version] = $arr;
		} else {
			throw new \Exception('$fileId has not the expected format');
		}

		if (strpos($fileId, '-') !== false) {
			[$fileId, $templateId] = explode('/', $fileId);
		}

		return [
			$fileId,
			$instanceId,
			$version,
			$templateId
		];
	}

	/**
	 * WOPI helper function to convert to ISO 8601 round-trip format.
	 * @param integer $time Must be seconds since unix epoch
	 */
	public static function toISO8601($time) {
		// TODO: Be more precise and don't ignore milli, micro seconds ?
		$datetime = DateTime::createFromFormat('U', $time, new DateTimeZone('UTC'));
		if ($datetime) {
			return $datetime->format('Y-m-d\TH:i:s.u\Z');
		}

		return false;
	}

	public static function getNewFileName(Folder $folder, $filename) {
		$fileNum = 1;

		while ($folder->nodeExists($filename)) {
			$fileNum++;
			$filename = preg_replace('/(\.| \(\d+\)\.)([^.]*)$/', ' (' . $fileNum . ').$2', $filename);
		}

		return $filename;
	}

	public function getGuestName() {
		if ($this->userId !== null || !isset($_COOKIE['guestUser']) || $_COOKIE['guestUser'] === '') {
			return null;
		}
		return $_COOKIE['guestUser'];
	}

	public static function getGuid() {
		if (function_exists('com_create_guid') === true) {
			return trim(com_create_guid(), '{}');
		}
		// @codingStandardsIgnoreStart

		return sprintf(
			'%04x%04x-%04x-%04x-%02x%02x-%04x%04x%04x',
			mt_rand(0, 65535),
			mt_rand(0, 65535),          // 32 bits for "time_low"
			mt_rand(0, 65535),          // 16 bits for "time_mid"
			mt_rand(0, 4096) + 16384,   // 16 bits for "time_hi_and_version", with
			// the most significant 4 bits being 0100
			// to indicate randomly generated version
			mt_rand(0, 64) + 128,       // 8 bits  for "clock_seq_hi", with
			// the most significant 2 bits being 10,
			// required by version 4 GUIDs.
			mt_rand(0, 255),            // 8 bits  for "clock_seq_low"
			mt_rand(0, 65535),          // 16 bits for "node 0" and "node 1"
			mt_rand(0, 65535),          // 16 bits for "node 2" and "node 3"
			mt_rand(0, 65535)           // 16 bits for "node 4" and "node 5"
		);

		// @codingStandardsIgnoreEnd
	}
}
