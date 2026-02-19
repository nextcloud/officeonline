<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Officeonline\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class WopiMapper extends QBMapper {
	// Tokens expire after this many seconds (not defined by WOPI specs).
	public const TOKEN_LIFETIME_SECONDS = 86400;

	/** @var ISecureRandom */
	private $random;

	/** @var LoggerInterface */
	private $logger;

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(IDBConnection $db,
		ISecureRandom $random,
		LoggerInterface $logger,
		ITimeFactory $timeFactory) {
		parent::__construct($db, 'officeonline_wopi', Wopi::class);

		$this->random = $random;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param int $fileId
	 * @param string $owner
	 * @param string $editor
	 * @param int $version
	 * @param bool $updatable
	 * @param string $serverHost
	 * @param string $guestDisplayname
	 * @param int $templateDestination
	 * @return Wopi
	 */
	public function generateFileToken($fileId, $owner, $editor, $version, $updatable, $serverHost, $guestDisplayname, $templateDestination = 0, $hideDownload = false, $direct = false, $isRemoteToken = false, $templateId = 0, $share = null) {
		$token = $this->random->generate(32, ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_DIGITS);

		$wopi = Wopi::fromParams([
			'fileid' => $fileId,
			'ownerUid' => $owner,
			'editorUid' => $editor,
			'version' => $version,
			'canwrite' => $updatable,
			'serverHost' => $serverHost,
			'token' => $token,
			'expiry' => $this->timeFactory->getTime() + self::TOKEN_LIFETIME_SECONDS,
			'guestDisplayname' => $guestDisplayname,
			'templateDestination' => $templateDestination,
			'hideDownload' => $hideDownload,
			'direct' => $direct,
			'isRemoteToken' => $isRemoteToken,
			'templateId' => $templateId,
			'share' => $share
		]);

		/** @var Wopi $wopi */
		$wopi = $this->insert($wopi);

		return $wopi;
	}

	/**
	 * Given a token, validates it and
	 * constructs and validates the path.
	 * Returns the path, if valid, else false.
	 */
	public function getWopiForToken(string $token): ?Wopi {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('officeonline_wopi')
			->where(
				$qb->expr()->eq('token', $qb->createNamedParameter($token))
			);
		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		$this->logger->debug('Loaded WOPI Token record: {row}.', [
			'row' => $row,
			'app' => 'officeonline'
		]);
		if ($row === false) {
			return null;
		}

		/** @var Wopi $wopi */
		$wopi = Wopi::fromRow($row);

		if ($wopi->getExpiry() < $this->timeFactory->getTime()) {
			$qb = $this->db->getQueryBuilder();
			$qb->delete('officeonline_wopi')->where($qb->expr()->lt('expiry',
				$qb->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT)))->executeStatement();
			return null;
		}

		return $wopi;
	}
}
