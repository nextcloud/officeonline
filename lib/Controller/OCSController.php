<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Officeonline\Controller;

use OCA\Officeonline\Db\DirectMapper;
use OCA\Officeonline\TemplateManager;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IURLGenerator;

class OCSController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IRootFolder $rootFolder,
		private string $userId,
		private DirectMapper $directMapper,
		private IURLGenerator $urlGenerator,
		private TemplateManager $manager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Init an editing session
	 *
	 * @throws OCSNotFoundException|OCSBadRequestException
	 */
	#[NoAdminRequired]
	public function create(int $fileId): DataResponse {
		try {
			$userFolder = $this->rootFolder->getUserFolder($this->userId);
			$node = $userFolder->getFirstNodeById($fileId);

			if ($node === null) {
				throw new OCSNotFoundException();
			}

			if ($node instanceof Folder) {
				throw new OCSBadRequestException('Cannot view folder');
			}

			$direct = $this->directMapper->newDirect($this->userId, $fileId);

			return new DataResponse([
				'url' => $this->urlGenerator->linkToRouteAbsolute('officeonline.directView.show', [
					'token' => $direct->getToken()
				])
			]);
		} catch (NotFoundException $e) {
			throw new OCSNotFoundException();
		}
	}

	/**
	 * @param string $type The template type
	 * @throws OCSBadRequestException
	 */
	#[NoAdminRequired]
	public function getTemplates(string $type): DataResponse {
		if (array_key_exists($type, TemplateManager::$tplTypes)) {
			$templates = $this->manager->getAllFormatted($type);
			return new DataResponse($templates);
		}
		throw new OCSBadRequestException('Wrong type');
	}

	/**
	 * @param ?string $path Where to create the document
	 * @param ?int $template The template id
	 */
	#[NoAdminRequired]
	public function createFromTemplate(?string $path, ?int $template): DataResponse {
		if ($path === null || $template === null) {
			throw new OCSBadRequestException('path and template must be set');
		}

		if (!$this->manager->isTemplate($template)) {
			throw new OCSBadRequestException('Invalid template provided');
		}

		$info = $this->mb_pathinfo($path);

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$folder = $userFolder->get($info['dirname']);
		$name = $folder->getNonExistingName($info['basename']);
		$file = $folder->newFile($name);

		try {
			$direct = $this->directMapper->newDirect($this->userId, $template, $file->getId());

			return new DataResponse([
				'url' => $this->urlGenerator->linkToRouteAbsolute('officeonline.directView.show', [
					'token' => $direct->getToken()
				])
			]);
		} catch (NotFoundException $e) {
			throw new OCSNotFoundException();
		}
	}

	private function mb_pathinfo(string $filepath): array {
		$result = [];
		preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', ltrim('/' . $filepath), $matches);
		if ($matches[1]) {
			$result['dirname'] = $matches[1];
		}
		if ($matches[2]) {
			$result['basename'] = $matches[2];
		}
		if ($matches[5]) {
			$result['extension'] = $matches[5];
		}
		if ($matches[3]) {
			$result['filename'] = $matches[3];
		}
		return $result;
	}
}
