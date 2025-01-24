<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method getValue()
 * @method setUserId($userId)
 * @method setValidBy(float|int $param)
 * @method setValue(string $lck)
 * @method setFileId($id)
 * @method setTokenId(string $getId)
 * @method getValidBy()
 */
class WopiLock extends Entity {
	protected $tokenId;
	protected $userId;
	protected $fileId;
	protected $validBy;
	protected $value;

	public function __construct() {
		$this->addType('fileId', 'integer');
		$this->addType('validBy', 'integer');
		$this->addType('id', 'string');
	}

	public function columnToProperty($columnName): string {
		if ($columnName === 'token_id') {
			return 'tokenId';
		} elseif ($columnName === 'user_id') {
			return 'userId';
		} elseif ($columnName === 'file_id') {
			return 'fileId';
		} elseif ($columnName === 'valid_by') {
			return 'validBy';
		} else {
			return parent::columnToProperty($columnName);
		}
	}

	public function propertyToColumn($property): string {
		if ($property === 'tokenId') {
			return 'token_id';
		} elseif ($property === 'userId') {
			return 'user_id';
		} elseif ($property === 'fileId') {
			return 'file_id';
		} elseif ($property === 'validBy') {
			return 'valid_by';
		} else {
			return parent::propertyToColumn($property);
		}
	}
}
