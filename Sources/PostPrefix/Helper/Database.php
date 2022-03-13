<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix\Helper;

if (!defined('SMF'))
	die('No direct access...');

class Database
{
	// Prefix columns
	public static $_prefix_columns = [
		'pp.id AS prefix_id',
		'pp.name AS prefix_name',
		'pp.status AS prefix_status',
		'pp.color AS prefix_color',
		'pp.bgcolor AS prefix_bgcolor',
		'pp.invert_color AS prefix_invert_color',
		'pp.icon_class AS prefix_icon_class'
	];

	// Vanilla prefix columns
	public static $_prefix_normal = [
		'pp.id',
		'pp.name',
		'pp.status',
		'pp.color',
		'pp.bgcolor',
		'pp.invert_color',
		'pp.icon_class'
	];

	// Boards columns
	public static $_boards_columns = [
		'ppb.id_prefix',
		'ppb.id_board',
	];

	// Groups columns
	public static $_groups_columns = [
		'ppg.id_prefix',
		'ppg.id_group',
	];

	public static function sanitize($string)
	{
		global $smcFunc;

		return $smcFunc['htmlspecialchars']($string, ENT_QUOTES);
	}

	public static function Count($table, $columns, $additional_query = '', $additional_columns = '', $more_values = [])
	{
		global $smcFunc;

		$columns = implode(', ', $columns);
		$data = array_merge(
			[
				'table' => $table,
			],
			$more_values
		);
		$request = $smcFunc['db_query']('','
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table} ' .
			$additional_columns. ' 
			'. $additional_query,
			$data
		);
		$rows = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	public static function Get($start, $items_per_page, $sort, $table, $columns, $additional_query = '', $single = false, $additional_columns = '', $more_values = [])
	{
		global $smcFunc;

		$columns = implode(', ', $columns);
		$data = array_merge(
			[
				'table' => $table,
				'start' => $start,
				'maxindex' => $items_per_page,
				'sort' => $sort,
			],
			$more_values
		);
		$result = $smcFunc['db_query']('', '
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table} ' .
			$additional_columns. ' 
			'. $additional_query . (empty($single) ? '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:maxindex}' : ''),
			$data
		);

		// Single?
		if (empty($single))
		{
			$items = [];
			while ($row = $smcFunc['db_fetch_assoc']($result))
				$items[] = $row;
		}
		else
			$items = $smcFunc['db_fetch_assoc']($result);

		$smcFunc['db_free_result']($result);

		return $items;
	}

	public static function pNested($sort, $table, $column_main, $column_sec, $query_member, $additional_query = '', $additional_columns = '', $more_values = [], $attachments = [], $attach_main = false)
	{
		global $smcFunc;

		$columns = array_merge(array_merge($column_main, $column_sec), $attachments);
		$columns = implode(', ', $columns);
		$data = array_merge(
			[
				'table' => $table,
				'sort' => $sort,
			],
			$more_values
		);
		$result = $smcFunc['db_query']('', '
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table} ' .
			$additional_columns. ' 
			'. $additional_query . '
			ORDER by {raw:sort}',
			$data
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($result))
		{
			$tmp_main = [];

			// Split them
			foreach($row as $col => $value)
			{
				if (in_array(strstr($column_main[0], '.', true).'.'.$col, $column_main))
					$tmp_main[$col] = $value;
			}

			// Just loop once on each group/category
			if (!isset($items[$row[substr(strrchr($column_main[0], '.'), 1)]]))
				$items[$row[substr(strrchr($column_main[0], '.'), 1)]] = $tmp_main;

			// If it's empty, make it so and move on
			if (empty($row[substr(strrchr($column_sec[0], '.'), 1)]))
			{
				$items[$row[substr(strrchr($column_main[0], '.'), 1)]][$query_member] = [];
				continue;
			}

			// Insert the rest of the items
			$items[$row[substr(strrchr($column_main[0], '.'), 1)]][$query_member][] = $row[substr(strrchr($column_sec[0], '.'), 1)];
		}
		$smcFunc['db_free_result']($result);

		// Don't duplicate the values
		foreach ($items as $key => $value)
		{
			$items[$key][$query_member] = array_unique($items[$key][$query_member]);

			// If they don't have key, they are useless...
			if (empty($items[$key][$query_member]))
				unset($items[$key]);
		}

		return $items;
	}

	public static function bNested($sort, $table, $column_main, $column_sec, $query_member, $additional_query = '', $additional_columns = '', $more_values = [])
	{
		global $smcFunc;

		$columns = array_merge($column_main, $column_sec);
		$columns = implode(', ', $columns);
		$data = array_merge(
			[
				'table' => $table,
				'sort' => $sort,
			],
			$more_values
		);
		$result = $smcFunc['db_query']('', '
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table} ' .
			$additional_columns. ' 
			'. $additional_query . '
			ORDER by {raw:sort}',
			$data
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($result))
		{
			$tmp_main = [];
			$tmp_sec  = [];

			// Split them
			foreach($row as $col => $value)
			{
				if (in_array(strstr($column_main[0], '.', true).'.'.$col, $column_main))
					$tmp_main[$col] = $value;
				elseif (in_array(strstr($column_sec[0], '.', true).'.'.$col, $column_sec))
					$tmp_sec[$col] = $value;
				else
					$tmp_main[$col] = $value;
			}

			// Just loop once on each group/category
			if (!isset($items[$row[substr(strrchr($column_main[0], '.'), 1)]]))
				$items[$row[substr(strrchr($column_main[0], '.'), 1)]] = $tmp_main;

			$items[$row[substr(strrchr($column_main[0], '.'), 1)]][$query_member][$row[substr(strrchr($column_sec[0], '.'), 1)]] = $tmp_sec;
		}
		$smcFunc['db_free_result']($result);

		return $items;
	}

	public static function Find($table, $column, $search = '', $additional_query = '')
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('','
			SELECT ' . $column . '
			FROM {db_prefix}{raw:table}'.(!empty($search) ? ('
			WHERE ('. $column . (is_array($search) ? ' IN ({array_int:search})' : ('  = \''. $search . '\'')) . ') '.$additional_query) : '').'
			LIMIT 1',
			[
				'table' => $table,
				'search' => $search
			]
		);
		$result = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $result;
	}

	public static function Delete($table, $column, $search, $additional_query = '', $operator = '=', $values = [])
	{
		global $smcFunc;

		$data = array_merge(
			[
				'table' => $table,
				'search' => $search,
			],
			$values
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}{raw:table}
			WHERE '. $column . (is_array($search) ? ' '. $operator . ' ({array_int:search})' : (' ' . $operator . ' ' . $search)) . $additional_query,
			$data
		);
	}

	public static function Insert($table, $columns, $types, $indexes = [], $method = '')
	{
		global $smcFunc;

		$smcFunc['db_insert']($method,
			'{db_prefix}'.$table,
			$types,
			$columns,
			$indexes
		);
	}

	public static function Update($table, $columns, $types, $query)
	{
		global $smcFunc;

		$smcFunc['db_query']('','
			UPDATE {db_prefix}'.$table .  '
			SET
			'.rtrim($types, ', ') . '
			'.$query,
			$columns
		);
	}

	public static function Insert_id($table, $column)
	{
		global $smcFunc;

		return $smcFunc['db_insert_id']('{db_prefix}' . $table, $column);
	}
}