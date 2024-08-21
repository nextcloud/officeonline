<?php
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Officeonline\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Security\ISecureRandom;

class WopiMapper extends QBMapper {
	// Tokens expire after this many seconds (not defined by WOPI specs).
	public const TOKEN_LIFETIME_SECONDS = 86400;

	/** @var ISecureRandom */
	private $random;

	/** @var ILogger */
	private $logger;

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(IDBConnection $db,
		ISecureRandom $random,
		ILogger $logger,
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
	 *
	 * @param string $token
	 * @return Wopi
	 */
	public function getWopiForToken($token) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('officeonline_wopi')
			->where(
				$qb->expr()->eq('token', $qb->createNamedParameter($token))
			);
		$result = $qb->execute();
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
				$qb->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT)))->execute();
			return null;
		}

		return $wopi;
	}
}
