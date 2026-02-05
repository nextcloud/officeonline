<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline;

use OCA\Officeonline\Db\Wopi;
use OCA\Officeonline\Db\WopiMapper;
use OCA\Officeonline\Service\CapabilitiesService;
use OCA\Officeonline\WOPI\Parser;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\IManager;
use OCP\Util;
use Psr\Log\LoggerInterface;

class TokenManager {
	public function __construct(
		private IRootFolder $rootFolder,
		private IManager $shareManager,
		private IURLGenerator $urlGenerator,
		private Parser $wopiParser,
		private CapabilitiesService $capabilitiesService,
		private AppConfig $appConfig,
		private ?string $userId,
		private WopiMapper $wopiMapper,
		private IL10N $trans,
		private IUserManager $userManager,
		private IGroupManager $groupManager,
		private Helper $helper,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws \Exception
	 */
	public function getToken(string $fileId, ?string $shareToken = null, ?string $editoruid = null, bool $direct = false, bool $isRemoteToken = false): array {
		[$fileId, , $version] = Helper::parseFileId($fileId);
		$owneruid = null;
		$hideDownload = false;
		// if the user is not logged-in do use the sharers storage
		if ($shareToken !== null) {
			/** @var File $file */
			$rootFolder = $this->rootFolder;
			$share = $this->shareManager->getShareByToken($shareToken);
			$updatable = (bool)($share->getPermissions() & \OCP\Constants::PERMISSION_UPDATE);
			$hideDownload = $share->getHideDownload();
			$owneruid = $share->getShareOwner();
		} elseif ($this->userId !== null) {
			try {
				$editoruid = $this->userId;
				$userFolder = $this->rootFolder->getUserFolder($editoruid);

				$files = $userFolder->getById((int)$fileId);
				$updatable = false;
				foreach ($files as $file) {
					if ($file->isUpdateable()) {
						$updatable = true;
						break;
					}
				}

				// Check if the editor (user who is accessing) is in editable group
				// UserCanWrite only if
				// 1. No edit groups are set or
				// 2. if they are set, it is in one of the edit groups
				$editGroups = array_filter(explode('|', $this->appConfig->getAppValue('edit_groups')));
				$editorUser = $this->userManager->get($editoruid);
				if ($updatable && count($editGroups) > 0 && $editorUser) {
					$updatable = false;
					foreach ($editGroups as $editGroup) {
						$editorGroup = $this->groupManager->get($editGroup);
						if ($editorGroup !== null && $editorGroup->inGroup($editorUser)) {
							$updatable = true;
							break;
						}
					}
				}
			} catch (\Exception $e) {
				throw $e;
			}
		} else {
			$rootFolder = $this->rootFolder;
			// no active user login while generating the token
			// this is required during WopiPutRelativeFile
			if (is_null($editoruid)) {
				$this->logger->warning('Generating token for SaveAs without editoruid');
				$updatable = true;
			} else {
				// Make sure we use the user folder if available since fetching all files by id from the root might be expensive
				$userFolder = $this->rootFolder->getUserFolder($editoruid);

				$updatable = false;
				$files = $userFolder->getById($fileId);

				foreach ($files as $file) {
					if ($file->isUpdateable()) {
						$updatable = true;
						break;
					}
				}
			}
		}
		/** @var File $file */
		$file = $rootFolder->getFirstNodeById($fileId);
		// If its a public share, use the owner from the share, otherwise check the file object
		if (is_null($owneruid)) {
			$owner = $file->getOwner();
			if (is_null($owner)) {
				// Editor UID instead of owner UID in case owner is null e.g. group folders
				$owneruid = $editoruid;
			} else {
				$owneruid = $owner->getUID();
			}
		}

		// force read operation to trigger possible audit logging
		$fp = $file->fopen('r');
		fclose($fp);

		$serverHost = $this->urlGenerator->getAbsoluteURL('/');//$this->request->getServerProtocol() . '://' . $this->request->getServerHost();

		$guest_name = null;
		if ($this->userId === null) {
			if ($guest_name = $this->helper->getGuestName()) {
				$guest_name = $this->trans->t('%s (Guest)', Util::sanitizeHTML($guest_name));
			} else {
				$guest_name = $this->trans->t('Anonymous guest');
			}
		}

		$wopi = $this->wopiMapper->generateFileToken($fileId, $owneruid, $editoruid, $version, (int)$updatable, $serverHost, $guest_name, 0, $hideDownload, $direct, $isRemoteToken, 0, $shareToken);

		try {
			return [
				$this->wopiParser->getUrlSrcForFile($file, $updatable),
				$wopi->getToken(),
				$wopi
			];
		} catch (\Exception $e) {
			throw $e;
		}
	}

	public function updateToRemoteToken(Wopi $wopi, $shareToken, $remoteServer, $remoteServerToken, $remoteWopi) {
		$uid = $remoteWopi['editorUid'] . '@' . $remoteServer;
		$wopi->setEditorUid($shareToken);
		$wopi->setCanwrite($wopi->getCanwrite() && $remoteWopi['canwrite']);
		$wopi->setRemoteServer($remoteServer);
		$wopi->setRemoteServerToken($remoteServerToken);
		$wopi->setGuestDisplayname($uid);
		$this->wopiMapper->update($wopi);
		return $wopi;
	}

	public function getTokenForTemplate(File $templateFile, $userId, $targetFileId, $direct = false) {
		$owneruid = $userId;
		$editoruid = $userId;
		$rootFolder = $this->rootFolder->getUserFolder($editoruid);
		/** @var File $targetFile */
		$targetFile = $rootFolder->getFirstNodeById($targetFileId);
		if ($targetFile === null) {
			// TODO: Exception
			return null;
		}
		$updatable = $targetFile->isUpdateable();
		// Check if the editor (user who is accessing) is in editable group
		// UserCanWrite only if
		// 1. No edit groups are set or
		// 2. if they are set, it is in one of the edit groups
		$editGroups = array_filter(explode('|', $this->appConfig->getAppValue('edit_groups')));
		$editorUser = $this->userManager->get($editoruid);
		if ($updatable && count($editGroups) > 0 && $editorUser) {
			$updatable = false;
			foreach ($editGroups as $editGroup) {
				$editorGroup = $this->groupManager->get($editGroup);
				if ($editorGroup !== null && $editorGroup->inGroup($editorUser)) {
					$updatable = true;
					break;
				}
			}
		}

		$serverHost = $this->urlGenerator->getAbsoluteURL('/');

		if ($this->capabilitiesService->hasTemplateSource()) {
			$wopi = $this->wopiMapper->generateFileToken($targetFile->getId(), $owneruid, $editoruid, 0, (int)$updatable, $serverHost, null, 0, false, false, false, $templateFile->getId());
		} else {
			// Legacy way of creating new documents from a template
			$wopi = $this->wopiMapper->generateFileToken($templateFile->getId(), $owneruid, $editoruid, 0, (int)$updatable, $serverHost, null, $targetFile->getId(), $direct);
		}

		return [
			$this->wopiParser->getUrlSrcForFile($targetFile, $updatable),
			$wopi
		];
	}

	public function getRemoteToken(Node $node): Wopi {
		[$urlSrc, $token, $wopi] = $this->getToken($node->getId(), null, null, false, true);
		$wopi->setIsRemoteToken(true);
		$wopi->setRemoteServer($node->getStorage()->getRemote());

		$this->wopiMapper->update($wopi);
		return $wopi;
	}

	public function getRemoteTokenFromDirect(Node $node, $editorUid): Wopi {
		[$urlSrc, $token, $wopi] = $this->getToken($node->getId(), null, $editorUid, true, true);
		$wopi->setIsRemoteToken(true);
		$wopi->setRemoteServer($node->getStorage()->getRemote());

		$this->wopiMapper->update($wopi);
		return $wopi;
	}
}
