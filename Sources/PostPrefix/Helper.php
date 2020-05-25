<?php

/**
 * @package SMF Post Prefix
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix;

if (!defined('SMF'))
	die('No direct access...');

class Helper
{
	public static $columns = ['pp.id', 'pp.name', 'pp.status', 'pp.color', 'pp.bgcolor', 'pp.invert_color', 'pp.groups', 'pp.boards', 'pp.icon_url'];

	public static function Save($config_vars, $return_config, $sa)
	{
		global $context, $scripturl;

		if ($return_config)
			return $config_vars;

		$context['post_url'] = $scripturl . '?action=admin;area=postprefix;sa='. $sa. ';save';

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=postprefix;sa='. $sa. '');
		}
		prepareDBSettingContext($config_vars);
	}

	public static function Count($table, $columns, $additional_query = '')
	{
		global $smcFunc;

		$columns = implode(', ', $columns);
		$request = $smcFunc['db_query']('','
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table}
			{raw:where}',
			[
				'table' => $table,
				'where' => $additional_query,
			]
		);
		$rows = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	public static function Get($start, $items_per_page, $sort, $table, $columns, $additional_query = '', $single = false, $additional_columns = '', $attachments = [])
	{
		global $smcFunc;

		$columns = implode(', ', $columns);
		$result = $smcFunc['db_query']('', '
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table} ' .
			$additional_columns. ' 
			{raw:where}'. (empty($single) ? '
			ORDER by {raw:sort}
			LIMIT {int:start}, {int:maxindex}' : ''),
			[
				'table' => $table,
				'start' => $start,
				'maxindex' => $items_per_page,
				'sort' => $sort,
				'where' => $additional_query,
			]
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

	public static function Nested($sort, $table, $column_main, $column_sec, $query_member, $additional_query = '', $additional_columns = '', $attachments = [], $attach_main = false)
	{
		global $smcFunc;

		$columns = array_merge(array_merge($column_main, $column_sec), $attachments);
		$columns = implode(', ', $columns);
		$result = $smcFunc['db_query']('', '
			SELECT ' . $columns . '
			FROM {db_prefix}{raw:table} ' .
			$additional_columns. ' 
			{raw:where}
			ORDER by {raw:sort}',
			[
				'table' => $table,
				'sort' => $sort,
				'where' => $additional_query,
			]
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
			{
				$items[$row[substr(strrchr($column_main[0], '.'), 1)]] = $tmp_main;

				// Attachments?
				if (!empty($attachments) && !empty($attach_main))
					$items[$row[substr(strrchr($column_main[0], '.'), 1)]]['avatar'] = self::Attachments($row);
			}
			$items[$row[substr(strrchr($column_main[0], '.'), 1)]][$query_member][$row[substr(strrchr($column_sec[0], '.'), 1)]] = $tmp_sec;

			// Attachments?
			if (!empty($attachments) && empty($attach_main))
				$items[$row[substr(strrchr($column_main[0], '.'), 1)]][$query_member][$row[substr(strrchr($column_sec[0], '.'), 1)]]['avatar'] = self::Attachments($row);
				
		}
		$smcFunc['db_free_result']($result);

		return $items;
	}

	public static function Attachments($row)
	{
		global $modSettings, $scripturl;

		// Build the array for avatar
		$set_attachments = [
				'name' => $row['avatar'],
				'image' => $row['avatar'] == '' ? ($row['id_attach'] > 0 ? '<img class="avatar" src="' . (empty($row['attachment_type']) ? $scripturl . '?action=dlattach;attach=' . $row['id_attach'] . ';type=avatar' : $modSettings['custom_avatar_url'] . '/' . $row['filename']) . '" alt="" />' : '') : ((stristr($row['avatar'], 'http://') || stristr($row['avatar'], 'https://')) ? '<img class="avatar" src="' . $row['avatar'] . '"' . $avatar_width . $avatar_height . ' alt="" />' : '<img class="avatar" src="' . $modSettings['avatar_url'] . '/' . htmlspecialchars($row['avatar']) . '" alt="" />'),
				'href' => $row['avatar'] == '' ? ($row['id_attach'] > 0 ? (empty($row['attachment_type']) ? $scripturl . '?action=dlattach;attach=' . $row['id_attach'] . ';type=avatar' : $modSettings['custom_avatar_url'] . '/' . $row['filename']) : '') : ((stristr($row['avatar'], 'http://') || stristr($row['avatar'], 'https://')) ? $row['avatar'] : $modSettings['avatar_url'] . '/' . $row['avatar']),
				'url' => $row['avatar'] == '' ? '' : ((stristr($row['avatar'], 'http://') || stristr($row['avatar'], 'https://')) ? $row['avatar'] : $modSettings['avatar_url'] . '/' . $row['avatar'])
		];

		return $set_attachments;
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

	public static function Delete($table, $column, $search, $additional_query = '')
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}{raw:table}
			WHERE '. $column . (is_array($search) ? ' IN ({array_int:search})' : (' = ' . $search)) . $additional_query,
			[
				'table' => $table,
				'search' => $search,
			]
		);
	}

	public static function Insert($table, $columns, $types)
	{
		global $smcFunc;

		$smcFunc['db_insert']('ignore',
			'{db_prefix}'.$table,
			$types,
			$columns,
			[]
		);
	}

	public static function Update($table, $columns, $types, $query)
	{
		global $smcFunc;

		$smcFunc['db_query']('','
			UPDATE IGNORE {db_prefix}'.$table .  '
			SET
			'.rtrim($types, ', ') . '
			'.$query,
			$columns
		);
	}
}