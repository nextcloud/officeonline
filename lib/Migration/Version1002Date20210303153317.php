<?php

declare(strict_types=1);

namespace OCA\Officeonline\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1002Date20210303153317 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('officeonline_assets')) {
			$schema->dropTable('officeonline_assets');
		}

		return $schema;
	}
}
