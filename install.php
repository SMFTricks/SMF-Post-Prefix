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
					'not_null' => true,
				),
				array(
					'name' => 'name',
					'type' => 'text',
					'not_null' => true,
				),
				array(
					'name' => 'status',
					'type' => 'smallint',
					'default' => 1,
				),
				array(
					'name' => 'color',
					'type' => 'text',
					'not_null' => true,
				),
				array(
					'name' => 'bgcolor',
					'type' => 'smallint',
					'default' => 0,
					'not_null' => true,
				),
				array(
					'name' => 'invert_color',
					'type' => 'smallint',
					'default' => 0,
					'not_null' => true,
				),
				array(
					'name' => 'groups',
					'type' => 'text',
					'not_null' => true,
				),
				array(
					'name' => 'boards',
					'type' => 'text',
					'not_null' => true,
				),
				array(
					'name' => 'icon_url',
					'type' => 'text',
					'not_null' => true,
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
				'not_null' => true,
			)
		);
	}