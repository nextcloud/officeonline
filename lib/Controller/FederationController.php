<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Officeonline\Controller;

use OCA\Officeonline\Db\WopiMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Share\IManager;

class FederationController extends OCSController {

	/** @var IConfig */
	private $config;

	/** @var WopiMapper */
	private $wopiMapper;

	/** @var IManager */
	private $shareManager;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config,
		WopiMapper $wopiMapper,
		IManager $shareManager
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->wopiMapper = $wopiMapper;
		$this->shareManager = $shareManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function index() {
		return new DataResponse([
			'wopi_url' => $this->config->getAppValue('officeonline', 'wopi_url')
		]);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * Check the file info of a remote accessing a file
	 *
	 * this is used to make sure we respect reshares of federated shares with the
	 * applied permissions and also have information about the actual editor
	 *
	 * @param $token
	 * @return DataResponse
	 * @throws DoesNotExistException
	 */
	public function remoteWopiToken($token) {
		$wopi = $this->wopiMapper->getWopiForToken($token);
		if (empty($wopi)) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}
		return new DataResponse([
			'ownerUid' => $wopi->getOwnerUid(),
			'editorUid' => $wopi->getEditorUid(),
			'canwrite' => $wopi->getCanwrite(),
			'hideDownload' => $wopi->getHideDownload(),
			'direct' => $wopi->getDirect(),
			'serverHost' => $wopi->getServerHost(),
			'guestDisplayname' => $wopi->getGuestDisplayname()
		]);
	}
}
