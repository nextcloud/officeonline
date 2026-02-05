<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc., Victor Dubiniuk <victor.dubiniuk@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Controller;

use OC\Files\Type\TemplateManager;
use OCA\Officeonline\AppConfig;
use OCA\Officeonline\Helper;
use OCA\Officeonline\Service\FederationService;
use OCA\Officeonline\TokenManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

class DocumentController extends Controller {
	public const ODT_TEMPLATE_PATH = '/assets/odttemplate.odt';

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $settings,
		private AppConfig $appConfig,
		private IL10N $l10n,
		private IManager $shareManager,
		private TokenManager $tokenManager,
		private IRootFolder $rootFolder,
		private ISession $session,
		private ?string $userId,
		private LoggerInterface $logger,
		private \OCA\Officeonline\TemplateManager $templateManager,
		private FederationService $federationService,
		private Helper $helper,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Returns the access_token and urlsrc for WOPI access for given $fileId
	 * Requests is accepted only when a secret_token is provided set by admin in
	 * settings page
	 *
	 * @param string $fileId
	 * @return array access_token, urlsrc
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function extAppGetData($fileId) {
		$secretToken = $this->request->getParam('secret_token');
		$apps = array_filter(explode(',', $this->appConfig->getAppValue('external_apps')));
		foreach ($apps as $app) {
			if ($app !== '' && $secretToken === $app) {
				$appName = explode(':', $app);
				$this->logger->debug('External app "{extApp}" authenticated; issuing access token for fileId {fileId}', [
					'app' => $this->appName,
					'extApp' => $appName[0],
					'fileId' => $fileId
				]);
				try {
					$folder = $this->rootFolder->getUserFolder($this->userId);
					$item = $folder->getFirstNodeById($fileId);
					if (!($item instanceof Node)) {
						throw new \Exception();
					}
					[$urlSrc, $token] = $this->tokenManager->getToken($item->getId());
					return [
						'status' => 'success',
						'urlsrc' => $urlSrc,
						'token' => $token
					];
				} catch (\Exception $e) {
					$this->logger->error($e->getMessage(), ['app' => 'officeonline', 'exception' => $e]);
				}
			}
		}
		return [
			'status' => 'error',
			'message' => 'Permission denied'
		];
	}

	/**
	 * Strips the path and query parameters from the URL.
	 *
	 * @param string $url
	 * @return string
	 */
	private function domainOnly($url) {
		$parsed_url = parse_url($url);
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		return "$scheme$host$port";
	}

	/**
	 * Redirect to the files app with proper CSP headers set for federated editing
	 * This is a workaround since we cannot set a nonce for allowing dynamic URLs in the richdocument iframe
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function open(int $fileId) {
		try {
			$folder = $this->rootFolder->getUserFolder($this->userId);
			$item = $folder->getFirstNodeById($fileId);
			if (!($item instanceof File)) {
				throw new \Exception('Node is not a file');
			}

			if ($item->getStorage()->instanceOfStorage(\OCA\Files_Sharing\External\Storage::class)) {
				$remote = $item->getStorage()->getRemote();
				$remoteCollabora = $this->federationService->getRemoteCollaboraURL($remote);
				if ($remoteCollabora !== '') {
					$absolute = $item->getParent()->getPath();
					$relative = $folder->getRelativePath($absolute);
					$url = '/index.php/apps/files?dir=' . $relative .
						'&officeonline_open=' . $item->getName() .
						'&officeonline_fileId=' . $fileId .
						'&officeonline_remote_access=' . $remote;
					return new RedirectResponse($url);
				}
				$this->logger->warning('Failed to connect to remote collabora instance for ' . $fileId);
			}
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['app' => 'officeonline', 'exception' => $e]);
			$params = [
				'errors' => [['error' => $e->getMessage()]]
			];
			return new TemplateResponse('core', 'error', $params, 'guest');
		}

		return new TemplateResponse('core', '403', [], 'guest');
	}

	#[NoAdminRequired]
	public function index(int $fileId, ?string $path = null): RedirectResponse|TemplateResponse {
		try {
			$folder = $this->rootFolder->getUserFolder($this->userId);

			if ($path !== null) {
				$item = $folder->get($path);
			} else {
				$item = $folder->getFirstNodeById($fileId);
			}

			if (!($item instanceof File)) {
				throw new \Exception();
			}

			/** Open file from remote collabora */
			$federatedUrl = $this->federationService->getRemoteRedirectURL($item);
			if ($federatedUrl !== null) {
				$response = new RedirectResponse($federatedUrl);
				$response->addHeader('X-Frame-Options', 'ALLOW');
				return $response;
			}

			[$urlSrc, $token] = $this->tokenManager->getToken($item->getId());
			$params = [
				'permissions' => $item->getPermissions(),

				// The file name needs to be URL encoded else special characters
				// cause problems when the server parses/validates the URL
				'title' => urlencode($item->getName()),

				'fileId' => $item->getId() . '_' . $this->settings->getSystemValue('instanceid'),
				'token' => $token,
				'urlsrc' => $urlSrc,
				'path' => $folder->getRelativePath($item->getPath()),
				'instanceId' => $this->settings->getSystemValue('instanceid'),
				'canonical_webroot' => $this->appConfig->getAppValue('canonical_webroot'),
				'userId' => $this->userId
			];

			$encryptionManager = \OC::$server->getEncryptionManager();
			if ($encryptionManager->isEnabled()) {
				// Update the current file to be accessible with system public shared key
				$owner = $item->getOwner()->getUID();
				$absPath = '/' . $owner . '/' . $item->getInternalPath();
				$accessList = \OC::$server->getEncryptionFilesHelper()->getAccessList($absPath);
				$accessList['public'] = true;
				$encryptionManager->getEncryptionModule()->update($absPath, $owner, $accessList);
			}

			$response = new TemplateResponse('officeonline', 'documents', $params, 'base');
			$response->addHeader('Cache-Control', 'no-cache, no-store');
			$response->addHeader('Expires', '-1');
			$response->addHeader('Pragma', 'no-cache');
			return $response;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['app' => 'officeonline', 'exception' => $e]);
			return $this->renderErrorPage('Failed to open the requested file.');
		}

		return new TemplateResponse('core', '403', [], 'guest');
	}

	/**
	 * Create a new file from a template
	 *
	 * @param int $templateId
	 * @param string $fileName
	 * @param string $dir
	 * @return TemplateResponse
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws \OCP\Files\InvalidPathException
	 */
	#[NoAdminRequired]
	public function createFromTemplate($templateId, $fileName, $dir) {
		if (!$this->templateManager->isTemplate($templateId)) {
			return new TemplateResponse('core', '403', [], 'guest');
		}

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		try {
			$folder = $userFolder->get($dir);
		} catch (NotFoundException $e) {
			return new TemplateResponse('core', '403', [], 'guest');
		}

		if (!$folder instanceof Folder) {
			return new TemplateResponse('core', '403', [], 'guest');
		}

		$file = $folder->newFile($fileName);

		$template = $this->templateManager->get($templateId);
		[$urlSrc, $wopi] = $this->tokenManager->getTokenForTemplate($template, $this->userId, $file->getId());

		$wopiFileId = $template->getId() . '-' . $file->getId() . '_' . $this->settings->getSystemValue('instanceid');
		$wopiFileId = $wopi->getFileid() . '_' . $this->settings->getSystemValue('instanceid');

		$params = [
			'permissions' => $template->getPermissions(),
			'title' => $fileName,
			'fileId' => $wopiFileId,
			'token' => $wopi->getToken(),
			'urlsrc' => $urlSrc,
			'path' => $userFolder->getRelativePath($file->getPath()),
			'instanceId' => $this->settings->getSystemValue('instanceid'),
			'canonical_webroot' => $this->appConfig->getAppValue('canonical_webroot'),
			'userId' => $this->userId
		];

		return new TemplateResponse('officeonline', 'documents', $params, 'base');
	}

	/**
	 * @throws \Exception
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function publicPage(string $shareToken, string $fileName, int $fileId): TemplateResponse {
		try {
			$share = $this->shareManager->getShareByToken($shareToken);
			// not authenticated ?
			if ($share->getPassword()) {
				if (!$this->session->exists('public_link_authenticated')
					|| $this->session->get('public_link_authenticated') !== (string)$share->getId()
				) {
					throw new \Exception('Invalid password');
				}
			}

			$node = $share->getNode();
			if ($node instanceof Folder) {
				$item = $node->getFirstNodeById($fileId);
			} else {
				$item = $node;
			}
			if ($item instanceof Node) {
				$params = [
					'permissions' => $share->getPermissions(),
					'title' => $item->getName(),
					'fileId' => $item->getId() . '_' . $this->settings->getSystemValue('instanceid'),
					'path' => '/',
					'instanceId' => $this->settings->getSystemValue('instanceid'),
					'canonical_webroot' => $this->appConfig->getAppValue('canonical_webroot'),
					'userId' => $this->userId,
				];

				if ($this->userId !== null || ($share->getPermissions() & \OCP\Constants::PERMISSION_UPDATE) === 0 || $this->helper->getGuestName() !== null) {
					[$urlSrc, $token] = $this->tokenManager->getToken($item->getId(), $shareToken, $this->userId);
					$params['token'] = $token;
					$params['urlsrc'] = $urlSrc;
				}

				return new TemplateResponse('officeonline', 'documents', $params, 'base');
			}
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['app' => 'officeonline', 'exception' => $e]);
		}

		return $this->renderErrorPage('Failed to open the requested file.');
	}

	/**
	 * @param string $shareToken
	 * @param $remoteServer
	 * @param $remoteServerToken
	 * @param null $filePath
	 * @return TemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function remote(string $shareToken, $remoteServer, $remoteServerToken, $filePath = null) {
		try {
			$share = $this->shareManager->getShareByToken($shareToken);
			// not authenticated ?
			if ($share->getPassword()) {
				if (!$this->session->exists('public_link_authenticated')
					|| $this->session->get('public_link_authenticated') !== (string)$share->getId()
				) {
					throw new \Exception('Invalid password');
				}
			}

			$node = $share->getNode();
			if ($filePath !== null) {
				$node = $node->get($filePath);
			}

			if ($node instanceof Node) {
				[$urlSrc, $token, $wopi] = $this->tokenManager->getToken($node->getId(), $shareToken, $this->userId);

				$remoteWopi = $this->federationService->getRemoteFileDetails($remoteServer, $remoteServerToken);
				$this->tokenManager->updateToRemoteToken($wopi, $shareToken, $remoteServer, $remoteServerToken, $remoteWopi);

				$permissions = $share->getPermissions();
				if (!$remoteWopi['canwrite']) {
					$permissions = $permissions & ~ Constants::PERMISSION_UPDATE;
				}

				$params = [
					'permissions' => $permissions,
					'title' => $node->getName(),
					'fileId' => $node->getId() . '_' . $this->settings->getSystemValue('instanceid'),
					'token' => $token,
					'urlsrc' => $urlSrc,
					'path' => '/',
					'instanceId' => $this->settings->getSystemValue('instanceid'),
					'canonical_webroot' => $this->appConfig->getAppValue('canonical_webroot'),
					'userId' => $remoteWopi['editorUid'] . '@' . $remoteServer
				];

				$response = new TemplateResponse('officeonline', 'documents', $params, 'base');
				$response->addHeader('X-Frame-Options', 'ALLOW');
				return $response;
			}
		} catch (ShareNotFound $e) {
			return new TemplateResponse('core', '404', [], 'guest');
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['app' => 'officeonline', 'exception' => $e]);
			return $this->renderErrorPage('Failed to open the requested file.');
		}

		return new TemplateResponse('core', '403', [], 'guest');
	}

	/**
	 * @param string $mimetype
	 * @param string $filename
	 * @param string $dir
	 * @return JSONResponse
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 */
	#[NoAdminRequired]
	public function create($mimetype,
		$filename,
		$dir = '/') {
		$root = $this->rootFolder->getUserFolder($this->userId);
		try {
			/** @var Folder $folder */
			$folder = $root->get($dir);
		} catch (NotFoundException $e) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l10n->t('Can\'t create document')
			], Http::STATUS_BAD_REQUEST);
		}

		if (!($folder instanceof Folder)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l10n->t('Can\'t create document')
			], Http::STATUS_BAD_REQUEST);
		}

		$basename = $this->l10n->t('New Document.odt');
		switch ($mimetype) {
			case 'application/vnd.oasis.opendocument.spreadsheet':
				$basename = $this->l10n->t('New Spreadsheet.ods');
				break;
			case 'application/vnd.oasis.opendocument.presentation':
				$basename = $this->l10n->t('New Presentation.odp');
				break;
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$basename = $this->l10n->t('New Document.docx');
				break;
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				$basename = $this->l10n->t('New Spreadsheet.xlsx');
				break;
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
				$basename = $this->l10n->t('New Presentation.pptx');
				break;
			default:
				// to be safe
				$mimetype = 'application/vnd.oasis.opendocument.text';
				break;
		}

		if (!$filename) {
			$filename = Helper::getNewFileName($folder, $basename);
		}

		if ($folder->nodeExists($filename)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l10n->t('Document already exists')
			], Http::STATUS_BAD_REQUEST);
		}

		try {
			$file = $folder->newFile($filename);
		} catch (NotPermittedException $e) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l10n->t('Not allowed to create document')
			], Http::STATUS_BAD_REQUEST);
		}

		$content = '';

		if (class_exists(TemplateManager::class)) {
			$manager = \OC_Helper::getFileTemplateManager();
			$content = $manager->getTemplate($mimetype);
		}

		$file->putContent($content);

		return new JSONResponse([
			'status' => 'success',
			'data' => \OCA\Files\Helper::formatFileInfo($file->getFileInfo())
		]);
	}

	private function renderErrorPage(string $message): TemplateResponse {
		$params = [
			'errors' => [['error' => $message]]
		];
		return new TemplateResponse('core', 'error', $params, 'guest');
	}
}
