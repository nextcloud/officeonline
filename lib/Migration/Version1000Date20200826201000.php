<?php

declare(strict_types=1);

namespace OCA\Officeonline\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20200826201000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('officeonline_wopi')) {
			$table = $schema->createTable('officeonline_wopi');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('owner_uid', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('editor_uid', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('guest_displayname', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('fileid', 'integer', [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('version', 'integer', [
				//'notnull' => true,
				'notnull' => false,
				'length' => 4,
				'default' => 0,
			]);
			$table->addColumn('canwrite', 'boolean', [
				//'notnull' => true,
				'notnull' => false,
				'default' => false,
			]);
			$table->addColumn('server_host', 'string', [
				'notnull' => true,
				'default' => 'localhost',
			]);
			$table->addColumn('token', 'string', [
				//'notnull' => true,
				'notnull' => false,
				'length' => 32,
				'default' => '',
			]);
			$table->addColumn('expiry', 'integer', [
				'notnull' => false,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('template_destination', 'integer', [
				'notnull' => false,
				'length' => 4,
			]);
			$table->addColumn('template_id', 'integer', [
				'notnull' => false,
				'length' => 4,
			]);
			$table->addColumn('hide_download', 'boolean', [
				//'notnull' => true,
				'notnull' => false,
				'default' => false,
			]);
			$table->addColumn('direct', 'boolean', [
				//'notnull' => true,
				'notnull' => false,
				'default' => false,
			]);
			$table->addColumn('is_remote_token', 'boolean', [
				//'notnull' => true,
				'notnull' => false,
				'default' => false,
			]);
			$table->addColumn('remote_server', 'string', [
				//'notnull' => true,
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('remote_server_token', 'string', [
				//'notnull' => true,
				'notnull' => false,
				'length' => 32,
				'default' => '',
			]);
			$table->addColumn('share', 'string', [
				'notnull' => false,
				'length' => 64
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['token'], 'oon_wopi_token_idx');
		}

		if (!$schema->hasTable('officeonline_assets')) {
			$table = $schema->createTable('officeonline_assets');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('uid', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('fileid', 'integer', [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('token', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('timestamp', 'integer', [
				//'notnull' => true,
				'notnull' => false,
				'length' => 4,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['token'], 'oon_assets_token_idx');
			$table->addUniqueIndex(['timestamp'], 'oon_assets_timestamp_idx');
		}

		if (!$schema->hasTable('officeonline_locks')) {
			$table = $schema->createTable('officeonline_locks');
			$table->addColumn('id', 'string', [
				'length' => 36,
				'notnull' => true,
			]);
			$table->addColumn('valid_by', 'integer', [
				'notnull' => true
			]);
			$table->addColumn('file_id', 'integer', [
				'notnull' => true
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('token_id', 'string', [
				'notnull' => true,
				'length' => 36,
			]);
			$table->addColumn('value', 'string', [
				'notnull' => true,
				'length' => 1024,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['valid_by'], 'oon_locks_valid_by');
			$table->addUniqueIndex(['file_id'], 'oon_locks_file_id');
		}

		return $schema;
	}
}
