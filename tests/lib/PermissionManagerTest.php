<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Officeonline;

use OCA\Officeonline\PermissionManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PermissionManagerTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var PermissionManager */
	private $permissionManager;

	public function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->permissionManager = new PermissionManager($this->config, $this->groupManager);
	}

	public function testIsEnabledForUserEnabledNoRestrictions() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('officeonline', 'use_groups', '')
			->willReturn('');

		$this->assertTrue($this->permissionManager->isEnabledForUser($user));
	}

	public function testIsEnabledForUserEnabledNotInGroup() {
		/** @var IUser|MockBuilder $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('TestUser');

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('officeonline', 'use_groups', '')
			->willReturn('Enabled1|Enabled2|Enabled3');

		$this->groupManager
			->expects($this->at(0))
			->method('isInGroup')
			->with('TestUser', 'Enabled1')
			->willReturn(false);
		$this->groupManager
			->expects($this->at(1))
			->method('isInGroup')
			->with('TestUser', 'Enabled2')
			->willReturn(false);
		$this->groupManager
			->expects($this->at(2))
			->method('isInGroup')
			->with('TestUser', 'Enabled3')
			->willReturn(false);

		$this->assertFalse($this->permissionManager->isEnabledForUser($user));
	}

	public function testIsEnabledForUserEnabledInGroup() {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('TestUser');

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('officeonline', 'use_groups', '')
			->willReturn('Enabled1|Enabled2|Enabled3');

		$this->groupManager
			->expects($this->at(0))
			->method('isInGroup')
			->with('TestUser', 'Enabled1')
			->willReturn(false);
		$this->groupManager
			->expects($this->at(1))
			->method('isInGroup')
			->with('TestUser', 'Enabled2')
			->willReturn(true);

		$this->assertTrue($this->permissionManager->isEnabledForUser($user));
	}
}
