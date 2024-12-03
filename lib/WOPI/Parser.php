<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\Officeonline\WOPI;

use Exception;
use OCP\Files\File;
use OCP\IL10N;
use OCP\IRequest;
use SimpleXMLElement;

class Parser {
	public const ACTION_EDIT = 'edit';
	public const ACTION_VIEW = 'view';
	public const ACTION_EDITNEW = 'editnew';

	// https://wopi.readthedocs.io/en/latest/faq/languages.html
	public const SUPPORTED_LANGUAGES = [
		'af-ZA',
		'am-ET',
		'ar-SA',
		'as-IN',
		'az-Latn-AZ',
		'be-BY',
		'bg-BG',
		'bn-BD',
		'bn-IN',
		'bs-Latn-BA',
		'ca-ES',
		'ca-ES-valencia',
		'chr-Cher-US',
		'cs-CZ',
		'cy-GB',
		'da-DK',
		'de-DE',
		'el-GR',
		'en-gb',
		'en-US',
		'es-ES',
		'es-mx',
		'et-EE',
		'eu-ES',
		'fa-IR',
		'fi-FI',
		'fil-PH',
		'fr-ca',
		'fr-FR',
		'ga-IE',
		'gd-GB',
		'gl-ES',
		'gu-IN',
		'ha-Latn-NG',
		'he-IL',
		'hi-IN',
		'hr-HR',
		'hu-HU',
		'hy-AM',
		'id-ID',
		'is-IS',
		'it-IT',
		'ja-JP',
		'ka-GE',
		'kk-KZ',
		'km-KH',
		'kn-IN',
		'kok-IN',
		'ko-KR',
		'ky-KG',
		'lb-LU',
		'lo-la',
		'lt-LT',
		'lv-LV',
		'mi-NZ',
		'mk-MK',
		'ml-IN',
		'mn-MN',
		'mr-IN',
		'ms-MY',
		'mt-MT',
		'nb-NO',
		'ne-NP',
		'nl-NL',
		'nn-NO',
		'or-IN',
		'pa-IN',
		'pl-PL',
		'prs-AF',
		'pt-BR',
		'pt-PT',
		'quz-PE',
		'ro-Ro',
		'ru-Ru',
		'sd-Arab-PK',
		'si-LK',
		'sk-SK',
		'sl-SI',
		'sq-AL',
		'sr-Cyrl-BA',
		'sr-Cyrl-RS',
		'sr-Latn-RS',
		'sv-SE',
		'sw-KE',
		'ta-IN',
		'te-IN',
		'th-TH',
		'tk-TM',
		'tr-TR',
		'tt-RU',
		'ug-CN',
		'uk-UA',
		'ur-PK',
		'uz-Latn-UZ',
		'vi-VN',
		'zh-CN',
		'zh-TW'
	];

	/** @var DiscoveryManager */
	private $discoveryManager;
	/** @var IRequest */
	private $request;
	/** @var IL10N */
	private $l10n;

	/** @var SimpleXMLElement */
	private $parsed;

	public function __construct(DiscoveryManager $discoveryManager, IRequest $request, IL10N $l10n) {
		$this->discoveryManager = $discoveryManager;
		$this->request = $request;
		$this->l10n = $l10n;
	}

	/**
	 * @return SimpleXMLElement|bool
	 * @throws Exception
	 */
	public function getParsed() {
		if (!empty($this->parsed)) {
			return $this->parsed;
		}
		$discovery = $this->discoveryManager->get();
		// In PHP 8.0 and later, PHP uses libxml versions from 2.9.0, which disabled XXE by default. libxml_disable_entity_loader() is now deprecated.
		// Ref.: https://php.watch/versions/8.0/libxml_disable_entity_loader-deprecation
		if (\PHP_VERSION_ID < 80000) {
			$loadEntities = libxml_disable_entity_loader(true);
			$discoveryParsed = simplexml_load_string($discovery);
			libxml_disable_entity_loader($loadEntities);
		} else {
			$discoveryParsed = simplexml_load_string($discovery);
		}
		$this->parsed = $discoveryParsed;
		return $discoveryParsed;
	}

	public function getUrlSrcForFile(File $file, bool $edit): string {
		$protocol = $this->request->getServerProtocol();
		$fallbackProtocol = $protocol === 'https' ? 'http' : 'https';

		$netZones = [
			'external-' . $protocol,
			'internal-' . $protocol,
			'external-' . $fallbackProtocol,
			'internal-' . $fallbackProtocol,
		];

		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/(android|mobile)/i', $userAgent)) {
			$edit = false;
		}

		$actions = [
			$edit && ($file->getSize() === 0 || $file->getSize() === 1) ? self::ACTION_EDITNEW : null,
			$edit ? self::ACTION_EDIT : null,
			self::ACTION_VIEW,
		];
		$actions = array_filter($actions);

		foreach ($netZones as $netZone) {
			foreach ($actions as $action) {
				$result = $this->getUrlSrcByExtension($netZone, $file->getExtension(), $action);
				if ($result) {
					return $this->replaceUrlSrcParams($result);
				}
			}
		}

		foreach ($netZones as $netZone) {
			$result = $this->getUrlSrcByMimetype($netZone, $file->getMimeType());
			if ($result) {
				return $this->replaceUrlSrcParams($result);
			}
		}

		throw new Exception('Could not find urlsrc in WOPI');
	}

	private function getUrlSrcByExtension(string $netZoneName, string $actionExt, $actionName): ?string {
		$result = $this->getParsed()->xpath(sprintf(
			'/wopi-discovery/net-zone[@name=\'%s\']/app/action[@ext=\'%s\' and @name=\'%s\']',
			$netZoneName, $actionExt, $actionName
		));

		if (!$result || count($result) === 0) {
			return null;
		}

		return (string)current($result)->attributes()['urlsrc'];
	}

	private function getUrlSrcByMimetype(string $netZoneName, string $mimetype): ?string {
		$result = $this->getParsed()->xpath(sprintf(
			'/wopi-discovery/net-zone[@name=\'%s\']/app[@name=\'%s\']/action',
			$netZoneName, $mimetype
		));

		if (!$result || count($result) === 0) {
			return null;
		}

		return (string)current($result)->attributes()['urlsrc'];
	}

	private function replaceUrlSrcParams(string $urlSrc): string {
		$urlSrc = preg_replace('/<ui=UI_LLCC&>/', 'ui=' . $this->getLanguageCode() . '&', $urlSrc);
		return preg_replace('/<.+>/', '', $urlSrc);
	}

	private function getLanguageCode(): string {
		$languageCode = $this->l10n->getLanguageCode();
		$localeCode = $this->l10n->getLocaleCode();
		$splitLocale = explode('_', $localeCode);
		if (count($splitLocale) > 1) {
			$localeCode = $splitLocale[1];
		}

		$languageMatches = array_filter(self::SUPPORTED_LANGUAGES, function ($language) use ($languageCode, $localeCode) {
			return stripos($language, $languageCode) === 0;
		});

		// Unique match on the language
		if (count($languageMatches) === 1) {
			return array_shift($languageMatches);
		}
		$localeMatches = array_filter($languageMatches, function ($language) use ($languageCode, $localeCode) {
			return stripos($language, $languageCode . '-' . $localeCode) === 0;
		});

		// Matches with language and locale with region
		if (count($localeMatches) >= 1) {
			return array_shift($localeMatches);
		}

		// Fallback to first language match if multiple found and no fitting region is available
		if (count($languageMatches) > 1) {
			return array_shift($languageMatches);
		}

		// Try to provide the language code matching RFC1766 format, if no language can be determined office will fallback to the browser language and then to en-US
		// https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/online/discovery#ui_llcc
		return str_replace('_', '-', $languageCode);
	}
}
