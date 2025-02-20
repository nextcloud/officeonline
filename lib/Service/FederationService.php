<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Service;

use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\External\Storage as SharingExternalStorage;
use OCA\Officeonline\AppConfig;
use OCA\Officeonline\TokenManager;
use OCP\AppFramework\QueryException;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class FederationService {

	/** @var ICache */
	private $cache;
	/** @var IClientService */
	private $clientService;
	/** @var LoggerInterface */
	private $logger;
	/** @var TrustedServers */
	private $trustedServers;
	/** @var TokenManager */
	private $tokenManager;
	private AppConfig $appConfig;
	private IRequest $request;

	public function __construct(
		ICacheFactory $cacheFactory,
		IClientService $clientService,
		LoggerInterface $logger,
		TokenManager $tokenManager,
		AppConfig $appConfig,
		IRequest $request,
	) {
		$this->cache = $cacheFactory->createLocal('officeonline_remote/');
		$this->clientService = $clientService;
		$this->logger = $logger;
		$this->tokenManager = $tokenManager;
		try {
			$this->trustedServers = \OC::$server->query(\OCA\Federation\TrustedServers::class);
		} catch (QueryException $e) {
		}
		$this->appConfig = $appConfig;
		$this->request = $request;
	}

	public function getRemoteCollaboraURL($remote) {
		if ($this->trustedServers === null || !$this->trustedServers->isTrustedServer($remote)) {
			$this->logger->info('Unable to determine collabora URL of remote server ' . $remote . ' - Remote is not a trusted server');
			return '';
		}
		if ($remoteCollabora = $this->cache->get('officeonline_remote/' . $remote)) {
			return $remoteCollabora;
		}
		try {
			$client = $this->clientService->newClient();
			$response = $client->get($remote . '/ocs/v2.php/apps/officeonline/api/v1/federation?format=json', ['timeout' => 5]);
			$data = \json_decode($response->getBody(), true);
			$remoteCollabora = $data['ocs']['data']['wopi_url'];
			$this->cache->set('officeonline_remote/' . $remote, $remoteCollabora, 3600);
			return $remoteCollabora;
		} catch (\Throwable $e) {
			$this->logger->info('Unable to determine collabora URL of remote server ' . $remote);
			$this->cache->set('officeonline_remote/' . $remote, '', 300);
		}
		return '';
	}

	public function getRemoteDirectUrl($remote, $shareToken, $filePath) {
		if ($this->getRemoteCollaboraURL() === '') {
			return '';
		}
		try {
			$client = $this->clientService->newClient();
			$response = $client->post($remote . '/ocs/v2.php/apps/officeonline/api/v1/federation/direct?format=json', [
				'timeout' => 5,
				'body' => [
					'shareToken' => $shareToken,
					'filePath' => $filePath
				]
			]);
			$data = \json_decode($response->getBody(), true);
			return $data['ocs']['data'];
		} catch (\Throwable $e) {
			$this->logger->info('Unable to determine collabora URL of remote server ' . $remote);
		}
		return null;
	}

	public function getRemoteFileDetails($remote, $remoteToken) {
		if ($this->trustedServers === null || !$this->trustedServers->isTrustedServer($remote)) {
			$this->logger->info('Unable to determine collabora URL of remote server ' . $remote . ' - Remote is not a trusted server');
			return null;
		}
		try {
			$client = $this->clientService->newClient();
			$response = $client->post($remote . '/ocs/v2.php/apps/officeonline/api/v1/federation?format=json', [
				'timeout' => 5,
				'body' => [
					'token' => $remoteToken
				]
			]);
			$data = \json_decode($response->getBody(), true);
			return $data['ocs']['data'];
		} catch (\Throwable $e) {
			$this->logger->info('Unable to determine collabora URL of remote server ' . $remote);
		}
		return null;
	}

	/**
	 * @param File $item
	 * @return string|null
	 * @throws NotFoundException
	 * @throws InvalidPathException
	 */
	public function getRemoteRedirectURL(File $item, $direct = null) {
		if ($item->getStorage()->instanceOfStorage(SharingExternalStorage::class)) {
			$remote = $item->getStorage()->getRemote();
			$remoteCollabora = $this->getRemoteCollaboraURL($remote);
			if ($remoteCollabora !== '') {
				if ($direct === null) {
					$wopi = $this->tokenManager->getRemoteToken($item);
				} else {
					$wopi = $this->tokenManager->getRemoteTokenFromDirect($item, $direct->getUid());
				}

				$url = rtrim($remote, '/') . '/index.php/apps/officeonline/remote?shareToken=' . $item->getStorage()->getToken() .
					'&remoteServer=' . $wopi->getServerHost() .
					'&remoteServerToken=' . $wopi->getToken();
				if ($item->getInternalPath() !== '') {
					$url .= '&filePath=' . $item->getInternalPath();
				}
				return $url;
			}
			throw new NotFoundException('Failed to connect to remote collabora instance for ' . $item->getId());
		}
		return null;
	}

	public function getTrustedServers(): array {
		if (!$this->trustedServers) {
			return [];
		}

		return array_map(function (array $server) {
			return $server['url'];
		}, $this->trustedServers->getServers());
	}

	public function isTrustedRemote($domainWithPort) {
		if (strpos($domainWithPort, 'http://') === 0 || strpos($domainWithPort, 'https://') === 0) {
			$port = parse_url($domainWithPort, PHP_URL_PORT);
			$domainWithPort = parse_url($domainWithPort, PHP_URL_HOST) . ($port ? ':' . $port : '');
		}

		if ($this->appConfig->isTrustedDomainAllowedForFederation() && $this->trustedServers !== null && $this->trustedServers->isTrustedServer($domainWithPort)) {
			return true;
		}

		$domain = $this->getDomainWithoutPort($domainWithPort);

		$trustedList = array_merge($this->appConfig->getGlobalScaleTrustedHosts(), [$this->request->getServerHost()]);
		if (!is_array($trustedList)) {
			return false;
		}

		foreach ($trustedList as $trusted) {
			if (!is_string($trusted)) {
				break;
			}
			$regex = '/^' . implode('[-\.a-zA-Z0-9]*', array_map(function ($v) {
				return preg_quote($v, '/');
			}, explode('*', $trusted))) . '$/i';
			if (preg_match($regex, $domain) || preg_match($regex, $domainWithPort)) {
				return true;
			}
		}

		return false;
	}

	private function getDomainWithoutPort($host) {
		$pos = strrpos($host, ':');
		if ($pos !== false) {
			$port = substr($host, $pos + 1);
			if (is_numeric($port)) {
				$host = substr($host, 0, $pos);
			}
		}
		return $host;
	}
}
