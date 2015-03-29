<?php

/**
 * @package SMF Post Prefix
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2014, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
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
		$tables[] = array(
			'table_name' => '{db_prefix}postprefixes',
			'columns' => array(
				array(
					'name' => 'id',
					'type' => 'int',
					'size' => 10,
					'auto' => true,
				),
				array(
					'name' => 'name',
					'type' => 'varchar',
					'size' => 80,
				),
				array(
					'name' => 'status',
					'type' => 'smallint',
					'default' => 1,
				),
				array(
					'name' => 'added',
					'type' => 'int',
				),
				array(
					'name' => 'color',
					'type' => 'varchar',
					'size' => 22,
				),
				array(
					'name' => 'bgcolor',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'member_groups',
					'type' => 'varchar',
				),
				array(
					'name' => 'deny_member_groups',
					'type' => 'varchar',
				),
				array(
					'name' => 'boards',
					'type' => 'varchar',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id'),
				),
			),
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => array(),
		);

		// Installing
		foreach ($tables as $table)
		$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);

		// Add some columns for board options
		$smcFunc['db_add_column'](
			'{db_prefix}boards', 
			array(
				'name' => 'require_prefix',
				'type' => 'tinyint',
				'default' => 0,
			)
		);

		// Prefix id on topics
		$smcFunc['db_add_column'](
			'{db_prefix}topics', 
			array(
				'name' => 'id_prefix',
				'type' => 'int',
				'default' => 0,
			)
		);

	}