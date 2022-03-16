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
					'columns' => ['name', 'status'],
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

		// Do some additional changes
		$prefix_columns = $smcFunc['db_list_columns']('{db_prefix}postprefixes', true);

		if (!empty($prefix_columns))
		{
			// ID
			if (isset($prefix_columns['id']))
			{
				$smcFunc['db_change_column'](
					'{db_prefix}postprefixes',
					'id',
					[
						'type' => 'smallint',
						'size' => 5,
						'auto' => true,
						'not_null' => true,
						'unsigned' => true,
					]
				);
			}
			// Name
			if (isset($prefix_columns['name']))
			{
				$smcFunc['db_change_column'](
					'{db_prefix}postprefixes',
					'name',
					[
						'type' => 'varchar',
						'size' => 25,
						'not_null' => false,
						'default' => null,
					]
				);
			}
			// Status
			if (isset($prefix_columns['status']))
			{
				$smcFunc['db_change_column'](
					'{db_prefix}postprefixes',
					'status',
					[
						'type' => 'tinyint',
						'size' => 1,
						'unsigned' => true,
					]
				);
			}
			// Color
			if (isset($prefix_columns['color']))
			{
				$smcFunc['db_change_column'](
					'{db_prefix}postprefixes',
					'color',
					[
						'type' => 'varchar',
						'size' => 255,
						'not_null' => false,
						'default' => null,
					]
				);
			}
			//BgColor
			if (isset($prefix_columns['bgcolor']))
			{
				$smcFunc['db_change_column'](
					'{db_prefix}postprefixes',
					'bgcolor',
					[
						'type' => 'tinyint',
						'size' => 1,
						'unsigned' => true,
						'not_null' => true,
					]
				);
			}
			// Invert Color
			if (isset($prefix_columns['invert_color']))
			{
				$smcFunc['db_change_column'](
					'{db_prefix}postprefixes',
					'invert_color',
					[
						'type' => 'tinyint',
						'size' => 1,
						'unsigned' => true,
						'not_null' => true,
					]
				);
			}

			// Icon Class not added?
			if (!isset($prefix_columns['icon_class']))
			{
				$smcFunc['db_add_column'](
					'{db_prefix}postprefixes',
					[
						'name' => 'icon_class',
						'type' => 'varchar',
						'size' => 50,
						'not_null' => false,
						'default' => null,
					],
					[],
					'ignore',
				);
			}

			// Remove icon_url
			if (isset($prefix_columns['icon_url']))
			{
				$smcFunc['db_remove_column'](
					'{db_prefix}postprefixes',
					'icon_url'
				);
			}

			// Get the boards and groups
			if (isset($prefix_columns['boards']) && isset($prefix_columns['groups']))
			{
				$request = $smcFunc['db_query']('', '
					SELECT id, boards, groups
					FROM {db_prefix}postprefixes',
					[],
				);
				$prefix_groups = [];
				$prefix_boards = [];
				while ($row = $smcFunc['db_fetch_assoc']($request))
				{
					// Check for boards?
					if (isset($row['boards']) && !empty($row['boards']))
					{
						foreach (explode(',', $row['boards']) as $board)
						{
							$prefix_boards[] = [
								'id_prefix' => $row['id'],
								'id_board' => $board,
							];
						}
					}

					// Check for groups
					if (isset($row['groups']))
					{
						foreach (explode(',', $row['groups']) as $group)
						{
							$prefix_groups[] = [
								'id_prefix' => $row['id'],
								'id_group' => (int) $group,
							];
						}
					}
				}
				$smcFunc['db_free_result']($request);

				// Drop boards
				$smcFunc['db_remove_column']('{db_prefix}postprefixes', 'boards');
				// Drop groups
				$smcFunc['db_remove_column']('{db_prefix}postprefixes', 'groups');
			}

			// Add the keys
			$smcFunc['db_add_index'](
				'{db_prefix}postprefixes',
				[
					'type' => 'index',
					'columns' => ['name', 'status'],
				],
				[],
				'ignore',
			);
		}

		// Add the boards if any
		if (!empty($prefix_boards))
		{
			$smcFunc['db_insert']('ignore',
				'{db_prefix}postprefixes_boards',
				[
					'id_prefix' => 'int',
					'id_board' => 'int'
				],
				$prefix_boards,
				['id_prefix', 'id_board']
			);
		}

		// Add the groups if any
		if (!empty($prefix_groups))
		{
			$smcFunc['db_insert']('ignore',
				'{db_prefix}postprefixes_groups',
				[
					'id_prefix' => 'int',
					'id_group' => 'int'
				],
				$prefix_groups,
				['id_prefix', 'id_group']
			);
		}
	}