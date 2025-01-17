<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Officeonline\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Class WopiEntity
 *
 * @package OCA\Officeonline\Db
 *
 * @method void setOwnerUid(string $uid)
 * @method string getOwnerUid()
 * @method void setEditorUid(string $uid)
 * @method string getEditorUid()
 * @method void setFileid(int $fileid)
 * @method int getFileid()
 * @method void setVersion(int $version)
 * @method int getVersion()
 * @method void setCanwrite(bool $canwrite)
 * @method bool getCanwrite()
 * @method void setServerHost(string $host)
 * @method string getServerHost()
 * @method void setToken(string $token)
 * @method string getToken()
 * @method void setExpiry(int $expiry)
 * @method int getExpiry()
 * @method void setGuestDisplayname(string $token)
 * @method string getGuestDisplayname()
 * @method void setTemplateDestination(int $fileId)
 * @method int getTemplateDestination()
 * @method void setTemplateId(int $fileId)
 * @method int getTemplateId()
 * @method getRemoteServer()
 */
class Wopi extends Entity {
	/** @var string */
	protected $ownerUid;

	/** @var string */
	protected $editorUid;

	/** @var int */
	protected $fileid;

	/** @var int */
	protected $version;

	/** @var bool */
	protected $canwrite;

	/** @var string */
	protected $serverHost;

	/** @var string */
	protected $token;

	/** @var int */
	protected $expiry;

	/** @var string */
	protected $guestDisplayname;

	/** @var int */
	protected $templateDestination;

	/** @var int */
	protected $templateId;

	/** @var bool */
	protected $hideDownload;

	/** @var bool */
	protected $direct;

	/** @var bool */
	protected $isRemoteToken;

	/** @var string */
	protected $remoteServer = '';

	/** @var string */
	protected $remoteServerToken = '';

	/** @var string */
	protected $share;

	public function __construct() {
		$this->addType('ownerUid', 'string');
		$this->addType('editorUid', 'string');
		$this->addType('fileid', 'int');
		$this->addType('version', 'int');
		$this->addType('canwrite', 'bool');
		$this->addType('serverHost', 'string');
		$this->addType('token', 'string');
		$this->addType('expiry', 'int');
		$this->addType('guestDisplayname', 'string');
		$this->addType('templateDestination', 'int');
		$this->addType('templateId', 'int');
		$this->addType('hideDownload', 'bool');
		$this->addType('direct', 'bool');
		$this->addType('isRemoteToken', 'bool');
		$this->addType('remoteServer', 'string');
		$this->addType('remoteServerToken', 'string');
	}

	public function isTemplateToken() {
		return $this->getTemplateDestination() !== 0 && $this->getTemplateDestination() !== null;
	}

	public function hasTemplateId() {
		return $this->getTemplateId() !== 0 && $this->getTemplateId() !== null;
	}

	public function isGuest() {
		return $this->getGuestDisplayname() !== null;
	}

	public function getUserForFileAccess() {
		if ($this->share !== null) {
			return $this->getOwnerUid();
		}
		return $this->isGuest() ? $this->getOwnerUid() : $this->getEditorUid();
	}

	public function getCanwrite() {
		return (bool)$this->canwrite;
	}

	public function getHideDownload() {
		return (bool)$this->hideDownload;
	}

	public function getDirect() {
		return (bool)$this->direct;
	}
}
