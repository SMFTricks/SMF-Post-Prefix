<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix\Admin;

use PostPrefix\PostPrefix;
use PostPrefix\Helper\Database;

if (!defined('SMF'))
	die('No direct access...');

class Manage
{
	/**
	 * @var array Cat columns
	 */
	private $_cats_columns = ['c.id_cat', 'c.name AS cat_name', 'c.cat_order'];

	/**
	 * @var array Board columns
	 */
	private $_boards_columns = ['b.id_board', 'b.board_order', 'b.id_cat', 'b.name', 'b.child_level'];

	/**
	 * @var array Group columns
	 */
	private $_groups_columns = ['m.group_name', 'm.id_group', 'm.min_posts', 'm.online_color'];

	/**
	 * @var array Group array for showing prefix groups
	 */
	private $_groups = [];

	/**
	 * @var array Columns with the information.
	 */
	protected $_fields_data = [];

	/**
	 * @var array|string The type of the columns.
	 */
	protected $_fields_type;

	/**
	 * Prefix::list()
	 * 
	 * Loads the list of prefixes created
	 * 
	 * @return void
	 */
	public static function list() : void
	{
		global $context, $sourcedir, $scripturl, $txt;

		require_once($sourcedir . '/Subs-List.php');
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'prefixlist';
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $context['page_title'];

		// The entire list
		$listOptions = [
			'id' => 'prefixlist',
			'title' => $txt['PostPrefix_tab_prefixes'],
			'items_per_page' => 30,
			'base_href' => '?action=admin;area=postprefix;sa=prefixes',
			'default_sort_col' => 'modify',
			'get_items' => [
				'function' => 'PostPrefix\Helper\Database::Get',
				'params' => ['postprefixes AS pp', Database::$_prefix_columns],
			],
			'get_count' => [
				'function' => 'PostPrefix\Helper\Database::Count',
				'params' => ['postprefixes AS pp', Database::$_prefix_columns]
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
						'function' => function($row) use ($scripturl)
						{
							return '<a href="' . $scripturl . '?action=admin;area=postprefix;sa=status;id=' . $row['prefix_id'] . ';ps=' . (!empty($row['prefix_status']) ? 0 : 1) . '"><span class="main_icons warning_' . (!empty($row['prefix_status']) ? 'watch' : 'mute') . '"></span></a>';
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
						'function' => function($row)
						{
							 return PostPrefix::format($row);
						},
						'style' => 'width: 15%',
					],
					'sort' =>  [
						'default' => 'prefix_name DESC',
						'reverse' => 'prefix_name',
					],
				],
				'boards' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_boards'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=boards;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'boards\');">'. $txt['PostPrefix_select_visible_boards']. '</a>',
							'params' => [
								'prefix_id' => false,
								'prefix_name' => true,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 5%',
					],
				],
				'groups' => [
					'header' => [
						'value' => $txt['PostPrefix_prefix_groups'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=groups;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'membergroups\');">'. $txt['PostPrefix_select_visible_groups']. '</a>',
							'params' => [
								'prefix_id' => false,
								'prefix_name' => true,
							],
						],
						'class' => 'centertext',
						'style' => 'width: 5%',
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
								'prefix_id' => false,
							],
						],
						'style' => 'width: 5%',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'prefix_id DESC',
						'reverse' => 'prefix_id',
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
								'prefix_id' => false,
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

	/**
	 * Prefix::set_prefix()
	 * 
	 * Provides the interface for editing or adding a new prefix
	 * 
	 * @return void
	 */
	public function set_prefix() : void
	{
		global $txt, $context;

		// Essential bits
		$context['sub_template'] = 'postprefix';
		$context[$context['admin_menu_name']]['current_subsection'] = 'add';
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_add'],
			'description' => $txt['PostPrefix_tab_prefixes_add_desc'],
		];
		$context['prefix']['boards'] = [];
		$context['prefix']['groups'] = [];

		// Editing?
		if ($_REQUEST['sa'] == 'edit')
		{
			// Get the prefix
			$context['prefix'] = Database::Get(null, null, null,
				'postprefixes AS pp',
				Database::$_prefix_columns,
				'WHERE pp.id = {int:prefix}', true, '',
				[
					'prefix' => (int) (isset($_REQUEST['id']) ? $_REQUEST['id'] : 0),
				]
			);

			// Update the titles
			$context[$context['admin_menu_name']]['current_subsection'] = 'prefixes';
			$context[$context['admin_menu_name']]['tab_data'] = [
				'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_edit'],
				'description' => $txt['PostPrefix_tab_prefixes_edit_desc'],
			];

			// Don't go any further if there's no prefix
			if (empty($context['prefix']))
				fatal_lang_error('PostPrefix_error_unable_tofind', false);

			// Boards
			$context['prefix']['boards'] = Database::Get(0, 1000000,
				'ppb.id_board', 'postprefixes_boards AS ppb',
				Database::$_boards_columns,
				'WHERE ppb.id_prefix = {int:prefix}', false, '',
				[
					'prefix' => (int) $context['prefix']['prefix_id'],
				]
			);
			$context['prefix']['boards'] = array_column($context['prefix']['boards'], 'id_board');

			// Groups
			$context['prefix']['groups'] = Database::Get(0, 1000000,
				'ppg.id_group', 'postprefixes_groups AS ppg',
				Database::$_groups_columns,
				'WHERE ppg.id_prefix = {int:prefix}', false, '',
				[
					'prefix' => (int) $context['prefix']['prefix_id'],
				]
			);
			$context['prefix']['groups'] = array_column($context['prefix']['groups'], 'id_group');
		}

		// Title
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $context[$context['admin_menu_name']]['tab_data']['title'];

		// Colorpicker
		loadCSSFile('postprefix/coloris.css', ['default_theme' => true]);
		loadJavascriptFile('postprefix/coloris.js', ['default_theme' => true]);

		// Groups
		loadLanguage('ManageBoards');
		$context['forum_groups'] = $this->forumGroups();

		// Boards
		$context['forum_categories'] = Database::bNested('b.board_order', 'boards AS b', $this->_cats_columns, $this->_boards_columns, 'boards', '', 'LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)');

		// Now let's sort the list of categories into the boards for the template
		foreach ($context['forum_categories'] as $category)
			// Include a list of boards per category for easy toggling.
			$context['forum_categories'][$category['id_cat']]['child_ids'] = array_keys($category['boards']);
	}

	/**
	 * Prefix::save()
	 * 
	 * Saves a prefix after editing or adding
	 * 
	 * @return void
	 */
	public function save() : void
	{
		global $user_info;

		// Data
		$this->_fields_data = [
			'id' => (int) isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? $_REQUEST['id'] : 0,
			'name' => (string) isset($_REQUEST['name']) ? Database::sanitize($_REQUEST['name']) : '',
			'status' => (int) isset($_REQUEST['status']) ? 1 : 0,
			'color' => (string) isset($_REQUEST['color']) && !empty($_REQUEST['color']) ? Database::sanitize($_REQUEST['color']) : '',
			'bgcolor' => (int) isset($_REQUEST['bgcolor']) ? 1 : 0,
			'invert_color' => (int) isset($_REQUEST['invert']) ? 1 : 0,
			'icon_class' => (string) isset($_REQUEST['icon_class']) && !empty($_REQUEST['icon_class']) && isset($_REQUEST['use_iconClass']) ? Database::sanitize($_REQUEST['icon_class']) : '',
		];

		// Empty name?
		if (empty($this->_fields_data['name']))
			fatal_lang_error('PostPrefix_error_noprefix', false);

		checkSession();
		$status = 'updated';

		if (empty($this->_fields_data['id']))
		{
			// Type
			foreach($this->_fields_data as $column => $type)
				$this->_fields_type[$column] = str_replace('integer', 'int', gettype($type));

			// Insert prefixes
			Database::Insert('postprefixes', $this->_fields_data, $this->_fields_type);
			$this->_fields_data['id'] = Database::Insert_id('postprefixes', 'id_prefix');
			$status = 'added';
		}
		else
		{
			$this->_fields_type = '';
			
			// Remove those that don't require updating
			unset($this->_fields_data['page_type']);

			// Type
			foreach($this->_fields_data as $column => $type)
				$this->_fields_type .= $column . ' = {'.str_replace('integer', 'int', gettype($type)).':'.$column.'}, ';

			// Update
			Database::Update('postprefixes', $this->_fields_data, $this->_fields_type, 'WHERE id = {int:id}');

			// Drop boards that are not in the list anymore
			Database::Delete('postprefixes_boards', 
				'id_board',
				(array) (isset($_REQUEST['boardset']) ? $_REQUEST['boardset'] : [0]),
				' AND id_prefix = {int:prefix}',
				'NOT IN',
				[
					'prefix' => $this->_fields_data['id'],
				],
			);

			// Drop the groups too
			Database::Delete('postprefixes_groups', 
				'id_group',
				(array) (isset($_REQUEST['groups']) ? $_REQUEST['groups'] : [0]),
				' AND id_prefix = {int:prefix}',
				'NOT IN',
				[
					'prefix' => $this->_fields_data['id'],
				],
			);
		}

		// Boards
		if (isset($_REQUEST['boardset']) && !empty($_REQUEST['boardset']) && is_array($_REQUEST['boardset']))
		{
			// Set boards
			$this->_fields_data['boards'] = [];
			foreach ($_REQUEST['boardset'] as $board)
				$this->_fields_data['boards'][] = [
					'id_prefix' => $this->_fields_data['id'],
					'id_board' => $board,
				];
			
			// Insert boards
			Database::Insert('postprefixes_boards',
				$this->_fields_data['boards'],
				[
					'id_prefix' => 'int',
					'id_board' => 'int'
				],
				[
					'id_board',
					'id_prefix'
				],
				'replace'
			);
		}

		// Groups
		if (isset($_REQUEST['groups']) && !empty($_REQUEST['groups']) && is_array($_REQUEST['groups']))
		{
			// Set groups
			$this->_fields_data['groups'] = [];
			foreach ($_REQUEST['groups'] as $group)
				$this->_fields_data['groups'][] = [
					'id_prefix' => $this->_fields_data['id'],
					'id_group' => $group,
				];
			
			// Insert groups
			Database::Insert('postprefixes_groups',
				$this->_fields_data['groups'],
				[
					'id_prefix' => 'int',
					'id_group' => 'int'
				],
				[
					'id_group',
					'id_prefix'
				],
				'replace'
			);
		}

		// Out!
		redirectexit('action=admin;area=postprefix;sa=prefixes;'.$status);
	}

	/**
	 * Prefix::delete()
	 * 
	 * Deletes the selected prefixes along with their boards and groups
	 * 
	 * @return void
	 */
	public static function delete() : void
	{
		global $context, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes'];
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_prefixes_desc'],
		];

		// Check if we have an id
		if (!isset($_REQUEST['delete']) || empty($_REQUEST['delete']))
			fatal_lang_error('PostPrefix_error_unable_tofind', false);

		// Make sure all IDs are numeric
		foreach ($_REQUEST['delete'] as $key => $value)
			$_REQUEST['delete'][$key] = (int) $value;

		// Delete all the prefixes
		Database::Delete('postprefixes', 'id', (array) $_REQUEST['delete'], '' , [], 'IN');

		// Delete the boards
		Database::Delete('postprefixes_boards', 'id_prefix', (array) $_REQUEST['delete'], '', 'IN');

		// Delete the groups
		Database::Delete('postprefixes_groups', 'id_prefix', (array) $_REQUEST['delete'], '', 'IN');
			
		// Back to the list
		redirectexit('action=admin;area=postprefix;sa=prefixes;deleted;');
	}

	/**
	 * Prefix::status
	 * 
	 * It updates the status of a prefix
	 * 
	 * @return void
	 */
	public static function status() : void
	{
		global $context, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_prefixes_edit'];
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_prefixes_edit_desc'],
		];

		// Verifiy
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('PostPrefix_error_unable_tofind', false);

		// Update the prefix status
		Database::Update('postprefixes',
			[
				'prefix' => (int) $_REQUEST['id'],
				'status' => isset($_REQUEST['ps']) && !empty($_REQUEST['ps']) ? 1 : 0,
			],
			'status = {int:status},',
			'WHERE id = {int:prefix}'
		);
		
		// Send him to the items list
		redirectexit('action=admin;area=postprefix;sa=prefixes');
	}

	/**
	 * Prefix::show_define
	 * 
	 * Displays the page with a list depending on the type (boards/groups)
	 * 
	 * @return void
	 */
	public function show_define(string $type = 'boards') : void
	{
		global $context, $txt;

		// Load the info
		$context[$context['admin_menu_name']]['current_subsection'] = 'prefixes';
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_prefix_' . $type],
			'description' => $txt['PostPrefix_prefix_' . $type. '_desc'],
		];
		$context['page_title'] = $context[$context['admin_menu_name']]['tab_data']['title'];
		$context['template_layers'][] = 'postprefix_show';
		$context['from_ajax'] = true;
		$context['sub_template'] = 'postprefix_show';
		$context['prefix']['show'] = $type;

		// Help language
		loadLanguage('Help');

		// Check if there's an id
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('PostPrefix_error_unable_tofind', false);

		// Obtain the prefix details
		if (($context['prefix']['details'] = cache_get_data('postprefix_p' . $_REQUEST['id'], 3600)) === null)
		{
			$context['prefix']['details'] = Database::Get('', '', '',
				'postprefixes AS pp',
				Database::$_prefix_columns,
				'WHERE pp.id = {int:prefix}', true, '',
				['prefix' => (int) $_REQUEST['id']]
			);
			cache_put_data('postprefix_p' . $_REQUEST['id'], $context['prefix']['details'], 3600);
		}

		// Update title
		$context[$context['admin_menu_name']]['tab_data']['title'] .= ' - ' . $context['prefix']['details']['prefix_name'];
		$context['page_title'] = $context[$context['admin_menu_name']]['tab_data']['title'];
	}

	/**
	 * Prefix::forumGroups()
	 * 
	 * Displays the list of groups in the forum
	 * 
	 * @return array The list of groups
	 */
	private function forumGroups() : array
	{
		global $txt;

		// Load Guests and Regular Member
		$groups = [
			-1 => [
				'id_group' => '-1',
				'group_name' => $txt['parent_guests_only'],
			],
			0 => [
				'id_group' => '0',
				'group_name' => $txt['parent_members_only'],
			]
		];

		// The rest of the groups, except admin
		$groups += Database::Get(0, 10000, 'm.min_posts, m.group_name',
			'membergroups AS m',
			$this->_groups_columns,
			'WHERE id_group != 3'
		);

		return $groups;
	}

	public function groups()
	{
		global $context;

		// Load extra language
		loadLanguage('ManageBoards');

		// Groups type
		$this->show_define('groups');

		// Get groups
		$context['forum_groups'] = $this->forumGroups();

		// Get the prefix groups
		$context['prefix']['details']['groups'] = Database::Get(0, 10000, 'ppg.id_group',
			'postprefixes_groups AS ppg',
			Database::$_groups_columns,
			'WHERE ppg.id_prefix = {int:prefix}', false, '',
			[
				'prefix' => $context['prefix']['details']['prefix_id']
			],
		);
		$context['prefix']['details']['groups'] = array_column($context['prefix']['details']['groups'], 'id_group');

		// Build the actual list
		$context['prefix']['get_type'] = [];
		foreach ($context['forum_groups'] as $id => $group)
		{
			if (in_array($group['id_group'], $context['prefix']['details']['groups']))
				$context['prefix']['get_type'][] = [
					'id' => $group['id_group'],
					'cat_name' => $group['group_name'],
					'online_color' => !empty($group['online_color']) ? $group['online_color'] : '',
				];
		}
	}

	public function boards()
	{
		global $context;

		// Boards type
		$this->show_define();

		// Get the prefix boards
		$context['prefix']['details']['boards'] = Database::Get(0, 10000, 'ppb.id_board',
			'postprefixes_boards AS ppb',
			Database::$_boards_columns,
			'WHERE ppb.id_prefix = {int:prefix}', false, '',
			[
				'prefix' => $context['prefix']['details']['prefix_id']
			],
		);
		$context['prefix']['details']['boards'] = array_column($context['prefix']['details']['boards'], 'id_board');

		// Get groups
		$context['prefix']['get_type'] = Database::bNested('b.board_order', 'boards AS b', 
			$this->_cats_columns, $this->_boards_columns, 'boards',
			'WHERE b.id_board IN ({array_int:boards})',
			'LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)',
			[
				'boards' => !empty($context['prefix']['details']['boards']) ? $context['prefix']['details']['boards'] : [0]
			]
		);

		// Sort the list of categories into the boards
		foreach ($context['prefix']['get_type'] as $category)
			// Include a list of boards per category for easy toggling.
			$context['prefix']['get_type'][$category['id_cat']]['child_ids'] = array_keys($category['boards']);
	}
}