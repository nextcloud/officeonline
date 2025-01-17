<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;

class DirectMapper extends QBMapper {

	/** @var int Limetime of a token is 10 minutes */
	public const tokenLifeTime = 600;

	/** @var ISecureRandom */
	protected $random;

	/** @var ITimeFactory */
	protected $timeFactory;

	public function __construct(IDBConnection $db,
		ISecureRandom $random,
		ITimeFactory $timeFactory) {
		parent::__construct($db, 'officeonline_direct', Direct::class);

		$this->random = $random;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $uid
	 * @param int $fileid
	 * @param int $destination
	 * @return Direct
	 */
	public function newDirect($uid, $fileid, $destination = null) {
		$direct = new Direct();
		$direct->setUid($uid);
		$direct->setFileid($fileid);
		$direct->setToken($this->random->generate(64, ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER));
		$direct->setTimestamp($this->timeFactory->getTime());
		$direct->setTemplateDestination($destination);

		$direct = $this->insert($direct);
		return $direct;
	}

	/**
	 * @param string $token
	 * @return Direct
	 */
	public function getByToken($token) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('officeonline_direct')
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token)));

		$cursor = $qb->execute();
		$row = $cursor->fetch();
		$cursor->closeCursor();

		//There can only be one as the token is unique
		if ($row === false) {
			throw new DoesNotExistException('Could not find token.');
		}

		$direct = Direct::fromRow($row);

		if (($direct->getTimestamp() + self::tokenLifeTime) < $this->timeFactory->getTime()) {
			$this->delete($direct);
			throw new DoesNotExistException('Could not find token.');
		}

		return $direct;
	}
}
