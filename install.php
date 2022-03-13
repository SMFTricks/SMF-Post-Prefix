<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
		// Post Prefixes
		$tables[] = [
			'table_name' => '{db_prefix}postprefixes',
			'columns' => [
				[
					'name' => 'id',
					'type' => 'smallint',
					'size' => 5,
					'auto' => true,
					'not_null' => true,
					'unsigned' => true,
				],
				[
					'name' => 'name',
					'type' => 'varchar',
					'size' => 25,
					'not_null' => false,
					'default' => null,
				],
				[
					'name' => 'status',
					'type' => 'tinyint',
					'size' => 1,
					'default' => 1,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'color',
					'type' => 'varchar',
					'size' => 255,
					'not_null' => false,
					'default' => null,
				],
				[
					'name' => 'bgcolor',
					'type' => 'tinyint',
					'size' => 1,
					'default' => 0,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'invert_color',
					'type' => 'tinyint',
					'size' => 1,
					'default' => 0,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'icon_class',
					'type' => 'varchar',
					'size' => 50,
					'not_null' => false,
					'default' => null,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['id'],
				],
				[
					'type' => 'index',
					'columns' => ['name'],
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Prefixes boards
		$tables[] = [
			'table_name' => '{db_prefix}postprefixes_boards',
			'columns' => [
				[
					'name' => 'id_prefix',
					'type' => 'smallint',
					'size' => 5,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'id_board',
					'type' => 'smallint',
					'size' => 5,
					'unsigned' => true,
					'not_null' => true,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['id_prefix', 'id_board'],
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Prefixes groups
		$tables[] = [
			'table_name' => '{db_prefix}postprefixes_groups',
			'columns' => [
				[
					'name' => 'id_prefix',
					'type' => 'smallint',
					'size' => 5,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'id_group',
					'type' => 'smallint',
					'size' => 5,
					'unsigned' => false,
					'not_null' => true,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['id_prefix', 'id_group'],
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Installing
		foreach ($tables as $table)
		$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);

		// Require Prefix column for boards
		$smcFunc['db_add_column'](
			'{db_prefix}boards', 
			[
				'name' => 'require_prefix',
				'type' => 'tinyint',
				'size' => 1,
				'unsigned' => true,
				'default' => 0,
				'not_null' => true,
			]
		);

		// Prefix id column for topics
		$smcFunc['db_add_column'](
			'{db_prefix}topics', 
			[
				'name' => 'id_prefix',
				'type' => 'smallint',
				'size' => 5,
				'default' => 0,
				'not_null' => true,
				'unsigned' => true,
			]
		);
	}