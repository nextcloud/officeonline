<!--
  - SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog

## 3.0.0

### Added

- Support NC 31, drop 24,25 and with it PHP <8.0 support @blizzz [#610](https://github.com/nextcloud/officeonline/pull/610)

### Fix

- fix(GS): consolidate and extend CSP management @blizzz [#559](https://github.com/nextcloud/officeonline/pull/559)
- fix: WopiLock is not 31 compatible @blizzz [#611](https://github.com/nextcloud/officeonline/pull/611)

## 2.3.2

### Fixed

- chore: Switch to new API to set volatile user to prevent persisting it in any case @juliusknorr [#606](https://github.com/nextcloud/officeonline/pull/606)
- fix: Always lookup lower case file extensions @juliusknorr [#607](https://github.com/nextcloud/officeonline/pull/607)

## 2.3.1

### Fixed

- fix: Proper default value for to make mode parameter optional @juliusknorr [#604](https://github.com/nextcloud/officeonline/pull/604)

### Other

- Add reuse compliance @AndyScherzinger [#602](https://github.com/nextcloud/officeonline/pull/602)
- style: Fix php-cs issues @juliusknorr [#605](https://github.com/nextcloud/officeonline/pull/605)

## 2.3.0

### Added

- Add support for Nextcloud 30
- fix: add clarifying use case details to appstore @joshtrichards [#590](https://github.com/nextcloud/officeonline/pull/590)

### Fixed

- fix: Properly create new files on public share links @juliusknorr [#593](https://github.com/nextcloud/officeonline/pull/593)

### Other

- chore: Align dependencies (and PHP version) with current lowest supported Nextcloud version @susnux [#580](https://github.com/nextcloud/officeonline/pull/580)
- refactor: Migrate away from deprecated `ILogger` to PSR-3 @susnux [#579](https://github.com/nextcloud/officeonline/pull/579)

## 2.2.1

### Fixed

- Fix: Add mising SharingLoadAdditionalScriptsListener [#553](https://github.com/nextcloud/officeonline/pull/553)
- Updating pr-feedback.yml workflow from template [#555](https://github.com/nextcloud/officeonline/pull/555)
- Updating dependabot-approve-merge.yml workflow from template [#556](https://github.com/nextcloud/officeonline/pull/556)
- Fix(UI): script to register at viewer has to require viewer [#558](https://github.com/nextcloud/officeonline/pull/558)
- Chore(CI): Updating pr-feedback.yml workflow from template [#562](https://github.com/nextcloud/officeonline/pull/562)

## 2.2.0

### Added

- Compatiblity with Nextcloud 29

## 2.1.2

### Fixed

- fix: Load public share link file creation again @juliushaertl [#548](https://github.com/nextcloud/officeonline/pull/548)

## 2.1.1

### Fixed

- Fix wrong language in new file menu @Dennis1993 [#528](https://github.com/nextcloud/officeonline/pull/528)
- Fixed remote redirect URL for federation shared files @hopleus [#523](https://github.com/nextcloud/officeonline/pull/523)
- Fix broken calls for inline script support @juliushaertl [#526](https://github.com/nextcloud/officeonline/pull/526)

### Other

- chore(CI): Adjust testing matrix for Nextcloud 29 on main @nickvergessen [#542](https://github.com/nextcloud/officeonline/pull/542)

## 2.1.0

### Fixed

- fix: File creation on Nextcloud 28 @juliushaertl [#519](https://github.com/nextcloud/officeonline/pull/519)
- fix: Skip CSP setup on CLI @juliushaertl [#507](https://github.com/nextcloud/officeonline/pull/507)
- fix: 28 deprecation compatiblity @juliushaertl [#490](https://github.com/nextcloud/officeonline/pull/490)

## 2.0.3

### Fixed

- fix: Do not always assume en-US as language if we cannot clearly determine @juliushaertl [#500](https://github.com/nextcloud/officeonline/pull/500)
- fix: enforce view mode on mobile @juliushaertl [#497](https://github.com/nextcloud/officeonline/pull/497)
- fix: Avoid throwing during app setup when federation classes could not be queried @juliushaertl [#488](https://github.com/nextcloud/officeonline/pull/488)

## 2.0.2

### Fixed

- fix: Return X-WOPI-Lock when a manual lock from outside exists @juliushaertl [#442](https://github.com/nextcloud/officeonline/pull/442)
- Add known issues to README @juliushaertl [#427](https://github.com/nextcloud/officeonline/pull/427)
- Update dependencies



## 2.0.1

### Fixed

- fix: Avoid unlocking too early and fix collaboration in Word @juliushaertl [#423](https://github.com/nextcloud/officeonline/pull/423)


## 2.0.0

### Added

- Implment locking using files_lock [#414](https://github.com/nextcloud/officeonline/pull/414)
- Compatiblity with Nextcloud 26

## 1.1.4

### Fixed

- Set allow_local_remote_servers to true for integration tests @juliushaertl [#290](https://github.com/nextcloud/officeonline/pull/290)
- Replace deprecated String.prototype.substr() @CommanderRoot [#282](https://github.com/nextcloud/officeonline/pull/282)
- Do not use libxml_disable_entity_loader on PHP 8 or later @juliushaertl [#289](https://github.com/nextcloud/officeonline/pull/289)
- Move to QBMapper @juliushaertl [#371](https://github.com/nextcloud/officeonline/pull/371)
- Do not use DOMContentLoaded for registering the viewer handler @juliushaertl [#378](https://github.com/nextcloud/officeonline/pull/378)
- Fix viewer positioning on newer nextcloud releases @juliushaertl [#370](https://github.com/nextcloud/officeonline/pull/370)
- Unify middleware checks @juliushaertl [#384](https://github.com/nextcloud/officeonline/pull/384)


## 1.1.3

### Fixed

- Fix icons in new file menu @andyxheli [#253](https://api.github.com/repos/nextcloud/officeonline/pulls/253)
- Change Token Lifetime Limit @andyxheli [#267](https://api.github.com/repos/nextcloud/officeonline/pulls/267)


## 1.1.2

### Fixed

- #244 Avoid opening PDF files @andyxheli
- Update dependencies


## 1.1.1

### Fixed

- #204 Unify error messages accross controllers @juliushaertl

### Dependencies

- #193 Bump stylelint-scss from 3.19.0 to 3.20.1 @dependabot[bot]
- #190 Bump stylelint-config-recommended-scss from 4.2.0 to 4.3.0 @dependabot[bot]


## 1.1.0

* [#143](https://github.com/nextcloud/officeonline/pull/143) Fix opening files when groupfolder ACL has revoked share permissions @juliushaertl
* [#148](https://github.com/nextcloud/officeonline/pull/148) Set UI language parameter in the backend @juliushaertl
* [#160](https://github.com/nextcloud/officeonline/pull/160) Use new viewer syntax with destructuring object @azul

## 1.0.3

* [#111](https://github.com/nextcloud/officeonline/pull/111) Remove asset handling since Office Online does not support it
* Bump dependencies

## 1.0.2

* [#59](https://github.com/nextcloud/officeonline/pull/59) Do not generate random parts that exceet 8bit values @juliushaertl
* [#69](https://github.com/nextcloud/officeonline/pull/69) Properly encode filename in urls @juliushaertl
* Bump dependencies


## 1.0.1

- Fix support for Oracle database backends
- Remove unsupported mimetypes
- Fix issue with duplicate index name

## 1.0.0

- Implement Office Online integration
