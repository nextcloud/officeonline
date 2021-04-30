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
	 * @param $mimetype
	 * @return array
	 * @throws Exception
	 */
	public function getUrlSrc($mimetype) {
		$discoveryParsed = $this->getParsed();

		$result = $discoveryParsed->xpath(sprintf('/wopi-discovery/net-zone/app[@name=\'%s\']/action', $mimetype));
		if ($result && count($result) > 0) {
			$urlSrc = $result[0]['urlsrc'];
			$urlSrc = preg_replace('/<ui=UI_LLCC&>/', 'ui=' . $this->getLanguageCode() . '&', $urlSrc);
			return [
				'urlsrc' => preg_replace('/<.+>/', '', $urlSrc),
				'action' => (string)$result[0]['name'],
			];
		}

		throw new Exception('Could not find urlsrc in WOPI');
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
		$loadEntities = libxml_disable_entity_loader(true);
		$discoveryParsed = simplexml_load_string($discovery);
		libxml_disable_entity_loader($loadEntities);
		$this->parsed = $discoveryParsed;
		return $discoveryParsed;
	}

	/**
	 * @param File $file
	 * @param bool $edit
	 * @return array
	 * @throws Exception
	 */
	public function getUrlSrcForFile(File $file, $edit) {
		try {
			$result = $this->getUrlSrc($file->getMimeType());
			return $result;
		} catch (Exception $e) {
		}
		// FIXME: we might want to support different action types here as well like imagepreview
		$actionName = $edit ? 'edit' : 'view';
		$discoveryParsed = $this->getParsed();
		$result = $discoveryParsed->xpath(sprintf('/wopi-discovery/net-zone[@name=\'external-https\']/app/action[@ext=\'%s\' and @name=\'%s\']', $file->getExtension(), $actionName));
		if (!$result || count($result) === 0) {
			$result = $discoveryParsed->xpath(sprintf('/wopi-discovery/net-zone[@name=\'external-https\']/app/action[@ext=\'%s\' and @name=\'%s\']', $file->getExtension(), 'view'));
		}

		if ($this->request->getServerProtocol() === 'http') {
			if (!$result || count($result) === 0) {
				$result = $discoveryParsed->xpath(sprintf('/wopi-discovery/net-zone[@name=\'external-http\']/app/action[@ext=\'%s\' and @name=\'%s\']', $file->getExtension(), $actionName));
			}
			if (!$result || count($result) === 0) {
				$result = $discoveryParsed->xpath(sprintf('/wopi-discovery/net-zone[@name=\'external-http\']/app/action[@ext=\'%s\' and @name=\'%s\']', $file->getExtension(), 'view'));
			}
		}
		if ($result && count($result) > 0) {
			$urlSrc = $result[0]['urlsrc'];
			$urlSrc = preg_replace('/<ui=UI_LLCC&>/', 'ui=' . $this->getLanguageCode() . '&', $urlSrc);
			return [
				'urlsrc' => preg_replace('/<.+>/', '', $urlSrc),
				'action' => (string)$result[0]['name'],
			];
		}
		throw new Exception('Could not find urlsrc in WOPI');
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

		return 'en-US';
	}
}
