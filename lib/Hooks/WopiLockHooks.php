<?php

namespace OCA\Officeonline\Hooks;

use OCA\Officeonline\Db\WopiLockMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;

class WopiLockHooks {
	private $rootFolder;
	/**
	 * @var WopiLockMapper
	 */
	private $lockMapper;
	/**
	 * @var ITimeFactory
	 */
	private $timeFactory;

	/**
	 * @var bool
	 */
	private $lockBypass;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(IRootFolder $rootFolder, ITimeFactory $timeFactory, LoggerInterface $logger, WopiLockMapper $lockMapper) {
		$this->rootFolder = $rootFolder;
		$this->lockMapper = $lockMapper;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
	}

	public function register() {
		$this->rootFolder->listen('\OC\Files', 'preWrite', [$this, 'preWrite']);
	}

	public function preWrite(Node $node) {
		if ($node instanceof File) {
			try {
				$lock = $this->lockMapper->find($node->getId());
				if (empty($lock)) {
					return;
				}
				if ($lock->getValidBy() < $this->timeFactory->getTime()) {
					$this->lockMapper->delete($lock);
					return;
				}
				if (!$this->lockBypass) {
					$node->lock(ILockingProvider::LOCK_SHARED);
				}
			} catch (InvalidPathException $e) {
				$this->logger->error('Invalid path', ['exception' => $e]);
			} catch (NotFoundException $e) {
				$this->logger->debug('not a file', ['exception' => $e]);
			} catch (LockedException $e) {
				$this->logger->error('Node already locked', ['exception' => $e]);
			}
		}
	}

	/**
	 * @param bool $lockBypass
	 */
	public function setLockBypass($lockBypass): void {
		$this->lockBypass = $lockBypass;
	}
}
