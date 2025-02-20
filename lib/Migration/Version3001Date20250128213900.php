<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Officeonline\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3001Date20250128213900 extends SimpleMigrationStep {

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('officeonline_wopi')) {
			return null;
		}

		$table = $schema->getTable('officeonline_wopi');
		if (!$table->hasColumn('guest_displayname')) {
			return null;
		}

		$column = $table->getColumn('guest_displayname');
		if ($column->getLength() === 255) {
			return null;
		}

		$column->setLength(255);
		return $schema;
	}
}
