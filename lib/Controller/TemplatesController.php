<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Officeonline\Controller;

use OC\Files\Filesystem;
use OCA\Officeonline\TemplateManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;

class TemplatesController extends Controller {
	/** @var IL10N */
	private $l10n;

	/** @var TemplateManager */
	private $manager;

	/** @var IPreview */
	private $preview;

	/** @var int Max template size */
	private $maxSize = 20 * 1024 * 1024;

	/**
	 * Templates controller
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param L10N $l10n
	 * @param TemplateManager $manager
	 * @param IPreview $preview
	 */
	public function __construct($appName,
		IRequest $request,
		IL10N $l10n,
		TemplateManager $manager,
		IPreview $preview) {
		parent::__construct($appName, $request);

		$this->appName = $appName;
		$this->request = $request;
		$this->l10n = $l10n;
		$this->manager = $manager;
		$this->preview = $preview;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Get preview for a specific template
	 *
	 * @param int $fileId The template id
	 * @param int $x
	 * @param int $y
	 * @param bool $a
	 * @param bool $forceIcon
	 * @param string $mode
	 * @return DataResponse
	 * @throws NotFoundResponse
	 */
	public function getPreview($fileId,
		$x = 150,
		$y = 150,
		$a = false,
		$forceIcon = true,
		$mode = 'fill') {
		if ($fileId === '' || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$template = $this->manager->get($fileId);
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if ($template instanceof ISimpleFile) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return $this->fetchPreview($template, $x, $y, $a, $forceIcon, $mode);
	}

	/**
	 * Add a global template
	 *
	 * @return JSONResponse
	 */
	public function add() {
		$files = $this->request->getUploadedFile('files');

		if (!is_null($files)) {
			if ($files['error'][0] === 0
				&& is_uploaded_file($files['tmp_name'][0])
				&& !Filesystem::isFileBlacklisted($files['tmp_name'][0])) {

				// TODO: ensure the size limit is decent for preview
				if ($files['size'][0] > $this->maxSize) {
					return new JSONResponse(
						['data' => ['message' => $this->l10n->t('File is too big')]],
						Http::STATUS_BAD_REQUEST
					);
				}

				if (!$this->manager->isValidTemplateMime($files['type'][0])) {
					return new JSONResponse(
						['data' => ['message' => $this->l10n->t('Only template files can be uploaded')]],
						Http::STATUS_BAD_REQUEST
					);
				}

				$templateName = $files['name'][0];
				$templateFile = file_get_contents($files['tmp_name'][0]);

				unlink($files['tmp_name'][0]);

				$template = $this->manager->add($templateName, $templateFile);

				return new JSONResponse(
					['data' => $template],
					Http::STATUS_CREATED
				);
			}
		}

		return new JSONResponse(
			['data' => ['message' => $this->l10n->t('Invalid file provided')]],
			Http::STATUS_BAD_REQUEST
		);
	}

	/**
	 * Delete a global template
	 *
	 * @param int $fileId
	 * @return JSONResponse
	 */
	public function delete($fileId) {
		try {
			$this->manager->delete($fileId);

			return new JSONResponse(
				['data' => ['status' => 'success']],
				Http::STATUS_NO_CONTENT
			);
		} catch (NotFoundException $e) {
			return new JSONResponse(
				['data' => ['message' => $this->l10n->t('Template not found')]],
				Http::STATUS_NOT_FOUND
			);
		}
	}

	/**
	 * @param Node $node
	 * @param int $x
	 * @param int $y
	 * @param bool $a
	 * @param bool $forceIcon
	 * @param string $mode
	 * @return DataResponse|FileDisplayResponse
	 */
	private function fetchPreview(
		Node $node,
		$x,
		$y,
		$a = false,
		$forceIcon = true,
		string $mode = 'fill'): Http\Response {
		if (!($node instanceof Node) || (!$forceIcon && !$this->preview->isAvailable($node))) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if (!$node->isReadable()) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		try {
			$f = $this->preview->getPreview($node, $x, $y, !$a, $mode);
			$response = new FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);
			$response->cacheFor(3600 * 24);

			return $response;
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
	}
}
