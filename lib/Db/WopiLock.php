<?php

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

	public function columnToProperty($column) {
		if ($column === 'token_id') {
			return 'tokenId';
		} elseif ($column === 'user_id') {
			return 'userId';
		} elseif ($column === 'file_id') {
			return 'fileId';
		} elseif ($column === 'valid_by') {
			return 'validBy';
		} else {
			return parent::columnToProperty($column);
		}
	}

	public function propertyToColumn($property) {
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
