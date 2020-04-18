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

class Manage
{
	public  static $table = 'postprefixes';
	public  static $columns = ['pp.id', 'pp.name', 'pp.status', 'pp.color', 'pp.bgcolor', 'pp.invert_color', 'pp.groups', 'pp.boards', 'pp.icon', 'pp.icon_url'];
	private static $cats_columns = ['c.id_cat', 'c.name AS cat_name', 'c.cat_order'];
	private static $boards_columns = ['b.id_board', 'b.board_order', 'b.id_cat', 'b.name', 'b.child_level'];
	private static $groups_columns = ['group_name', 'id_group', 'min_posts'];
	private static $fields_data = [];
	private static $fields_type = [];

	public static function prefixes()
	{
		global $context, $sourcedir, $modSettings, $scripturl, $txt;

		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'prefixlist';
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $context['page_title'];

		// The entire list
		$listOptions = [
			'id' => 'prefixlist',
			'title' => $txt['PostPrefix_tab_prefixes'],
			'items_per_page' => 15,
			'base_href' => '?action=admin;area=postprefix;sa=prefixes',
			'default_sort_col' => 'modify',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Helper::Get',
				'params' => [self::$table . ' AS pp', self::$columns],
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Helper::Count',
				'params' => [self::$table . ' AS pp', self::$columns]
			],
			'no_items_label' => $txt['PostPrefix_no_prefixes'],
			'no_items_align' => 'center',
			'columns' => [
				'status' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_status'],
						'class' => 'centertext',
					],
					'data' => [
						'function' => function($row) {
							global $scripturl;
							return ($row['status'] == 1 ? '<a href="'.$scripturl.'?action=admin;area=postprefix;sa=ups;id='. $row['id'].';status=0"><span class="main_icons warning_watch"></span></a>' : '<a href="'. $scripturl.'?action=admin;area=postprefix;sa=ups;id='. $row['id'].';status=1"><span class="main_icons warning_mute"></span></a>');
						},
						'style' => 'width: 2%',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'status DESC',
						'reverse' => 'status',
					]
				],
				'item_name' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_name'],
					],
					'data' => [
						'function' => function($row) {
							 return PostPrefix::formatPrefix($row['id']);
						},
						'style' => 'width: 20%',
					],
					'sort' =>  [
						'default' => 'name DESC',
						'reverse' => 'name',
					],
				],
				'boards' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_boards'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=showboards;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'/icons/modify_inline.png\');">'. $txt['PostPrefix_select_visible_boards']. '</a>',
							'params' => [
								'id' => false,
								'name' => true,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 4%',
					],
				],
				'groups' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_groups'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=showgroups;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'icons/members.png\');">'. $txt['PostPrefix_select_visible_groups']. '</a>',
							'params' => [
								'id' => false,
								'name' => true,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 4%',
					],
				],
				'modify' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_modify'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=edit;id=%1$d">'. $txt['PostPrefix_prefix_modify']. '</a>',
							'params' => [
								'id' => false,
							],
						],
						'style' => 'width: 5%',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'id DESC',
						'reverse' => 'id',
					]
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete']. ' <input type="checkbox" onclick="invertAll(this, this.form, \'delete[]\');" class="input_check" />',
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<input type="checkbox" name="delete[]" value="%1$d" class="check" />',
							'params' => [
								'id' => false,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 3%',
					],
				],
			],
			'form' => [
				'href' => '?action=admin;area=postprefix;sa=delete',
				'hidden_fields' => [
					$context['session_var'] => $context['session_id'],
				],
				'include_sort' => true,
				'include_start' => true,
			],
			'additional_rows' => [
				'delete' => [
					'position' => 'below_table_data',
					'value' => '<input type="submit" size="18" value="'.$txt['delete']. '" class="button" onclick="return confirm(\''.$txt['PostPrefix_prefix_delete_sure'].'\');" />',
				],
				'updated' => [
					'position' => 'top_of_list',
					'value' => (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : '<div class="infobox">'. $txt['PostPrefix_prefix_updated']. '</div>') : '<div class="infobox">'. $txt['PostPrefix_prefix_added']. '</div>') : '<div class="infobox">'. $txt['PostPrefix_prefix_deleted']. '</div>'),
				],
			],
		];
		// Let's finishem
		createList($listOptions);
	}

	public static function set_prefix()
	{
		global $txt, $context;

		// Essential bits
		$context['sub_template'] = 'postprefix';
		$context[$context['admin_menu_name']]['current_subsection'] = 'add';
		$context['prefix']['boards'] = [];
		$context['prefix']['groups'] = [];

		// Edit, or Add?
		if ($_REQUEST['sa'] == 'edit')
		{
			// Page information
			$where_query = 'WHERE pp.id = "'. (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0) . '"';
			$context['prefix'] = Helper::Get('', '', '', self::$table . ' AS pp', self::$columns, $where_query, true);
			$context[$context['admin_menu_name']]['current_subsection'] = 'prefixes';
			$context[$context['admin_menu_name']]['tab_data'] = [
				'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_edit'],
				'description' => $txt['PostPrefix_tab_prefixes_edit_desc'],
			];
			$context['prefix']['boards'] = explode(',', $context['prefix']['boards']);
			$context['prefix']['groups'] = explode(',', $context['prefix']['groups']);
			
			addInlineJavascript('var postprefix_color = \''.$context['prefix']['color']. '\';', true);

			// We found a page
			if (empty($context['prefix']))
				fatal_error($txt['PostPrefix_error_unable_tofind'], false);
		}

		// Title
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $context[$context['admin_menu_name']]['tab_data']['title'];

		// Colorpicker
		loadCSSFile('colpick.css', ['default_theme' => true]);
		loadJavascriptFile('colpick.js', ['default_theme' => true]);
		addInlineJavascript('
			$(document).ready(function (){
				$(\'#color\').colpick({
					layout:\'hex\',
					submit:0,
					colorScheme:\'light\',
					'. (!empty($context['prefix']['color']) ? 'color:\''. $context['prefix']['color']. '\',' : 'color:\'000000\',').'
					onChange:function(hsb,hex,rgb,el,bySetColor) {
						$(el).css(\'border-color\',\'#\'+hex);
						// Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
						if(!bySetColor) $(el).val(hex);
					}
				}).keyup(function(){
					$(this).colpickSetColor(this.value);
				});
			});',
			true
		);

		// Groups
		loadLanguage('ManageBoards');
		$context['forum_groups'] = [
			-1 => [
				'id_group' => '-1',
				'group_name' => $txt['parent_guests_only'],
			],
			0 => [
				'id_group' => '0',
				'group_name' => $txt['parent_members_only'],
			]
		];
		$context['forum_groups'] += Helper::Get(0, 10000, 'min_posts, group_name', 'membergroups', self::$groups_columns, 'WHERE id_group != 3');

		// Boards
		$context['forum_categories'] = Helper::Nested('b.board_order', 'boards AS b', self::$cats_columns, self::$boards_columns, 'boards', '', 'LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)');
		// Now, let's sort the list of categories into the boards for templates that like that.
		foreach ($context['forum_categories'] as $category)
			// Include a list of boards per category for easy toggling.
			$context['forum_categories'][$category['id_cat']]['child_ids'] = array_keys($category['boards']);
	}

	public static function save()
	{
		global $smcFunc, $txt;

		// Data
		self::$fields_data = [
			'id' => (int) isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0,
			'name' => (string) isset($_REQUEST['name']) ? $smcFunc['htmlspecialchars']($_REQUEST['name'], ENT_QUOTES) : '',
			'status' => (int) isset($_REQUEST['status']) ? 1 : 0,
			'color' => (string) isset($_REQUEST['color']) ? $smcFunc['htmlspecialchars']($_REQUEST['color'], ENT_QUOTES) : '',
			'bgcolor' => (int) isset($_REQUEST['bgcolor']) ? 1 : 0,
			'invert_color' => (int) isset($_REQUEST['invert_color']) ? 1 : 0,
			'groups' => (string) isset($_REQUEST['groups']) && !empty($_REQUEST['groups']) && is_array($_REQUEST['groups']) ? implode(',', $_REQUEST['groups']) : '',
			'boards' => (string) isset($_REQUEST['boardset']) && !empty($_REQUEST['boardset']) && is_array($_REQUEST['boardset']) ? implode(',', $_REQUEST['boardset']) : '',
			'icon' => (int) isset($_REQUEST['icon']) ? 1 : 0,
			'icon_url' => (string) isset($_REQUEST['icon_url']) ? $smcFunc['htmlspecialchars']($_REQUEST['icon_url'], ENT_QUOTES) : '',
		];

		// Validate info
		self::Validate(self::$fields_data);
		checkSession();
		$status = 'updated';

		if (empty(self::$fields_data['id']))
		{
			// Type
			foreach(self::$fields_data as $column => $type)
				self::$fields_type[$column] = str_replace('integer', 'int', gettype($type));

			// Insert
			Helper::Insert(self::$table, self::$fields_data, self::$fields_type);
			$status = 'added';
		}
		else
		{
			self::$fields_type = '';
			
			// Remove those that don't require updating
			unset(self::$fields_data['page_type']);

			// Type
			foreach(self::$fields_data as $column => $type)
				self::$fields_type .= $column . ' = {'.str_replace('integer', 'int', gettype($type)).':'.$column.'}, ';

			// Update
			Helper::Update(self::$table, self::$fields_data, self::$fields_type, 'WHERE id = ' . self::$fields_data['id']);
		}

		redirectexit('action=admin;area=postprefix;sa=prefixes;'.$status);
	}

	public static function validate($data)
	{
		global $txt;

		// Empty name
		if (empty($data['name']))
			fatal_error($txt['PostPrefix_error_noprefix'], false);

		// Doesn't exist
		if (!empty($data['id']) && empty(Helper::Find(self::$table . ' AS pp', 'pp.id', $data['id'])))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);
	}

	public static function delete()
	{
		global $context, $smcFunc, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_prefixes_desc'],
		);

		// If nothing was chosen to delete (shouldn't happen, but meh)
		if (!isset($_REQUEST['delete']))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);
				
		// Make sure all IDs are numeric
		foreach ($_REQUEST['delete'] as $key => $value)
			$_REQUEST['delete'][$key] = (int) $value;

		// Delete all the items
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}postprefixes
			WHERE id IN ({array_int:ids})',
			array(
				'ids' => $_REQUEST['delete'],
			)
		);

		$order = isset($_REQUEST['desc']) ? 'desc;' : '';
		$start = ($_REQUEST['start'] == 0 ? '' : $_REQUEST['start']);
			
		// Send the user to the items list with a message
		redirectexit('action=admin;area=postprefix;sa=prefixes;deleted;sort=' .$_REQUEST['sort']. ';' . $order . $start);
	}

	public static function updatestatus()
	{
		global $smcFunc, $context, $modSettings, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_edit'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_prefixes_edit_desc'],
		);

		$id = (int) $_REQUEST['id'];
		$status = (int) (!isset($_REQUEST['status']) || empty($_REQUEST['status']) ? 0 : $_REQUEST['status']);

		if (!isset($id) || empty($id))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);
		// Does the prefix exist?
		$find = self::FindPrefix($id);
		if ($find == false)
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);

		// Update the item information
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}postprefixes
			SET
				status = {int:status}
			WHERE id = {int:id}
			LIMIT 1',
			array(
				'status' => $status,
				'id' => $id,
			)
		);
		
		// Send him to the items list
		redirectexit('action=admin;area=postprefix;sa=prefixes');
	}

	public static function getGroups($allow = true, $deny = true)
	{
		global $context, $smcFunc, $modSettings, $txt;

		if ($allow)
		{
			$prefix = (int) $_REQUEST['id'];

			$request = $smcFunc['db_query']('', '
				SELECT id, member_groups, deny_member_groups
				FROM {db_prefix}postprefixes
				WHERE id = {int:id}',
				array(
					'id' => $prefix,
				)
			);
			$mg = $smcFunc['db_fetch_assoc']($request);
			$groups = explode(',', $mg['member_groups']);
			$dgroups = explode(',', $mg['deny_member_groups']);
		}

		// Get information on all the items selected to be deleted
		$result = $smcFunc['db_query']('', '
			SELECT mg.id_group, mg.group_name, mg.min_posts
			FROM {db_prefix}membergroups AS mg
			WHERE mg.id_group NOT IN (1, 3)
				AND mg.id_parent = {int:not_inherited}' . (empty($modSettings['permission_enable_postgroups']) ? '
				AND mg.min_posts = {int:min_posts}' : '') . '
			ORDER BY mg.min_posts, CASE WHEN mg.id_group < {int:newbie_group} THEN mg.id_group ELSE 4 END, mg.group_name',
			array(
				'not_inherited' => -2,
				'min_posts' => -1,
				'newbie_group' => 4,
			)
		);

		// Loop through all the results...
		// OMG Look what I had to do for the deny and allow thing.. pff
		$context['member_groups'] = array(
			0 => array(
				'id' => 0,
				'name' => $txt['membergroups_members'],
				'is_post_group' => false,
				'allow' => ($allow ? (!empty($mg['member_groups']) ? (in_array(0, $groups) ? true : '') : '') : ''),
				'deny' => ($deny ? (!empty($mg['deny_member_groups']) ? (in_array(0, $dgroups) ? true : '') : '') : ''),
			),
		);
		while ($row = $smcFunc['db_fetch_assoc']($result))
			// ... and add them to the array
			$context['member_groups'][] = array(
				'id' => $row['id_group'],
				'name' => $row['group_name'],
				'is_post_group' => $row['min_posts'] != -1,
				'allow' => ($allow ? (in_array($row['id_group'], $groups) ? true : '') : ''),
				'deny' => ($deny ? (in_array($row['id_group'], $dgroups) ? true : '') : ''),
			);
		$smcFunc['db_free_result']($result);
	}

	public static function showgroups()
	{
		global $smcFunc, $context, $txt;

		// Show them
		$context['template_layers'] = array();
		$context['from_ajax'] = true;
		$context['sub_template'] = 'postprefix_showgroups';

		// Help language
		loadLanguage('Help');

		// Check if there's an id
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);

		$prefix = (int) $_REQUEST['id'];

		$request = $smcFunc['db_query']('', '
			SELECT id, member_groups
			FROM {db_prefix}postprefixes
			WHERE id = {int:id}',
			array(
				'id' => $prefix,
			)
		);
		$mg = $smcFunc['db_fetch_assoc']($request);
		$groups = explode(',', $mg['member_groups']);
		$context['empty_groups'] = empty($mg['member_groups']) ? 0 : 1;

		if (!empty($mg['member_groups']))
		{
			// Get information on all the items selected to be deleted
			$result = $smcFunc['db_query']('', '
				SELECT mg.id_group, mg.group_name, mg.min_posts
				FROM {db_prefix}membergroups AS mg
				WHERE mg.id_group IN ({array_int:groups})',
				array(
					'groups' => $groups,
				)
			);

			if (!empty($mg))
			{
				if (in_array(0, $groups))
				{
					$context['member_groups'] = array(
						0 => array(
							'id' => 0,
							'name' => $txt['membergroups_members'],
							'is_post_group' => false,
						),
					);
				}
			}

			// Loop through all the results...
			while ($row = $smcFunc['db_fetch_assoc']($result))
				// ... and add them to the array
				$context['member_groups'][] = array(
					'id' => $row['id_group'],
					'name' => $row['group_name'],
					'is_post_group' => $row['min_posts'] != -1,
				);
			$smcFunc['db_free_result']($result);
		}
	}

	public static function showboards()
	{
		global $context, $smcFunc;
		
		// Show them
		$context['template_layers'] = array();
		$context['from_ajax'] = true;
		$context['sub_template'] = 'postprefix_showboards';

		// Help language
		loadLanguage('Help');

		// Check if there's an id
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_error($txt['PostPrefix_error_unable_tofind'], false);

		$prefix = (int) $_REQUEST['id'];

		$request = $smcFunc['db_query']('', '
			SELECT id, boards
			FROM {db_prefix}postprefixes
			WHERE id = {int:id}',
			array(
				'id' => $prefix,
			)
		);
		$brd = $smcFunc['db_fetch_assoc']($request);
		$boards = explode(',', $brd['boards']);
		$context['empty_boards'] = empty($brd['boards']) ? 0 : 1;

		if (!empty($brd['boards'])) 
		{
			// Get the boards and categories
			$request = $smcFunc['db_query']('', '
				SELECT b.id_cat, c.name AS cat_name, b.id_board, b.name, b.child_level, b.member_groups
				FROM {db_prefix}boards AS b
					LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
				WHERE b.id_board IN ({array_int:boards})'. (allowedTo('manage_boards') ? '' : '
					AND {query_see_board}'). '
				ORDER BY board_order',
				array(
					'boards' => $boards,
				)
			);
			$context['num_boards'] = $smcFunc['db_num_rows']($request);

			$context['categories'] = array();
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				// This category hasn't been set up yet..
				if (!isset($context['categories'][$row['id_cat']]))
					$context['categories'][$row['id_cat']] = array(
						'id' => $row['id_cat'],
						'name' => $row['cat_name'],
						'boards' => array()
					);

				// Set this board up, and let the template know when it's a child.  (indent them..)
				$context['categories'][$row['id_cat']]['boards'][$row['id_board']] = array(
					'id' => $row['id_board'],
					'name' => $row['name'],
					'child_level' => $row['child_level'],
				);

			}
			$smcFunc['db_free_result']($request);

			// Now, let's sort the list of categories into the boards for templates that like that.
			$temp_boards = array();
			foreach ($context['categories'] as $category)
			{
				$temp_boards[] = array(
					'name' => $category['name'],
					'child_ids' => array_keys($category['boards'])
				);
				$temp_boards = array_merge($temp_boards, array_values($category['boards']));

				// Include a list of boards per category for easy toggling.
				$context['categories'][$category['id']]['child_ids'] = array_keys($category['boards']);
			}
		}
	}
}