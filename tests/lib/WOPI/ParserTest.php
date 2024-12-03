<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Officeonline\WOPI;

use OCA\Officeonline\WOPI\DiscoveryManager;
use OCA\Officeonline\WOPI\Parser;
use OCP\IL10N;
use OCP\IRequest;

class ParserTest extends \Test\TestCase {
	public function dataLanguage() {
		return [
			['de', 'de_DE', 'de-DE'],
			['foo', 'de_DE', 'foo'],
			['en', 'en_US', 'en-US'],
			['en', 'en_GB', 'en-gb'],
			['en', 'de_DE', 'en-gb'],
			['fr', 'fr_FR', 'fr-FR'],
			['fr', 'fr_CA', 'fr-ca'],
			['zh_CN', 'zh_CN', 'zh-CN'],
			['zh_TW', 'zh_TW', 'zh-TW'],
			['zh_CN', 'zh_Hans_CN', 'zh-CN'],
			['zh_CN', 'zh_Hans_CN', 'zh-CN'],
			['es_UY', 'es_UY', 'es-UY'],
		];
	}

	/** @dataProvider dataLanguage */
	public function testLanguage($language, $locale, $expected) {
		$l10n = $this->createMock(IL10N::class);
		$parser = new Parser(
			$this->createMock(DiscoveryManager::class),
			$this->createMock(IRequest::class),
			$l10n
		);

		$l10n->expects($this->once())
			->method('getLanguageCode')
			->willReturn($language);
		$l10n->expects($this->once())
			->method('getLocaleCode')
			->willReturn($locale);

		self::assertEquals($expected, self::invokePrivate($parser, 'getLanguageCode'));
	}
}
