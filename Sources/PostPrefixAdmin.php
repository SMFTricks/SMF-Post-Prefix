<?php

/**
 * @package SMF Post Prefix
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2014, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */

if (!defined('SMF'))
	die('No direct access...');

class PostPrefixAdmin
{
	public static function main()
	{
		global $context, $sourcedir;

		// Set the title here
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_general');

		require_once($sourcedir . '/ManageSettings.php');

		$subActions = array(
			'general' => 'self::general',
			'prefixes' => 'self::prefixes',
			'showgroups' => 'self::showgroups',
			'showboards' => 'self::showboards',
			'add' => 'self::add',
			'add2' => 'self::add2',
			'edit' => 'self::edit',
			'edit2' => 'self::edit2',
			'delete' => 'self::delete',
			'ups' => 'self::updatestatus',
			'require' => 'self::require_boards',
			'require2' => 'self::require_boards2',
			'permissions' => 'self::permissions',
		);

		loadGeneralSettingParameters($subActions, 'general');

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'tabs' => array(
				'general' => array(),
				'prefixes' => array(),
				'add' => array(),
				'require' => array(),
				'permissions' => array(),
			),
		);

		$_REQUEST['sa'] = ((isset($_REQUEST['sa']) && !empty($_REQUEST['sa'])) ? $_REQUEST['sa'] : 'general');
		
		// Call the sub-action
		call_user_func($subActions[$_REQUEST['sa']]);

	}

	public static function general()
	{
		global $context, $user_info;

		$context['PostPrefix']['version'] = PostPrefix::$version;
		$context['PostPrefix']['credits'] = PostPrefix::credits();

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_general');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_general_desc'),
		);
		$context['sub_template'] = 'postprefix_general';
	}

	public static function prefixes()
	{
		global $context, $sourcedir, $modSettings, $scripturl, $txt;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_prefixes');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_desc'),
		);

		$context['prefix']['addup'] = '';
		if (isset($_REQUEST['added']) && !isset($_REQUEST['updated']) && !isset($_REQUEST['deleted']))
		{
			$id = (int) $_REQUEST['added'];
			$context['prefix']['addup'] .= '<div class="infobox">'.sprintf(PostPrefix::text('prefix_added'), PostPrefix::formatPrefix($id)).'</div>';
		}
		elseif (isset($_REQUEST['updated']) && !isset($_REQUEST['added']) && !isset($_REQUEST['deleted']))
		{
			$id = (int) $_REQUEST['updated'];
			$context['prefix']['addup'] .= '<div class="infobox">'.sprintf(PostPrefix::text('prefix_updated'), PostPrefix::formatPrefix($id)).'</div>';
		}
		elseif (isset($_REQUEST['deleted']) && !isset($_REQUEST['added']) && !isset($_REQUEST['updated']))
		{
			$context['prefix']['addup'] .= '<div class="infobox">'.PostPrefix::text('prefix_deleted').'</div>';
		}

		// We need this files
		require_once($sourcedir . '/Subs-List.php');

		// The entire list
		$listOptions = array(
			'id' => 'prefixes',
			'title' => PostPrefix::text('tab_prefixes'),
			'items_per_page' => 15,
			'base_href' => '?action=admin;area=postprefix;sa=prefixes',
			'default_sort_col' => 'added',
			'get_items' => array(
				'function' => 'PostPrefixAdmin::GetPrefixes#',
			),
			'get_count' => array(
				'function' => 'PostPrefixAdmin::CountPrefixes#',
			),
			'no_items_label' => PostPrefix::text('no_prefixes'),
			'no_items_align' => 'center',
			'columns' => array(
				'status' => array(
					'header' => array(
						'value' => PostPrefix::text('prefix_status'),
						'class' => 'centertext',
					),
					'data' => array(
						'function' => create_function('$row', 'global $scripturl; return ($row[\'status\'] == 1 ? \'<a href="\'. $scripturl. \'?action=admin;area=postprefix;sa=ups;id=\'. $row[\'id\']. \';status=0"><span class="generic_icons warning_watch"></span></a>\' : \'<a href="\'. $scripturl. \'?action=admin;area=postprefix;sa=ups;id=\'. $row[\'id\']. \';status=1"><span class="generic_icons warning_mute"></span></a>\');'),
						'style' => 'width: 2%',
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'status DESC',
						'reverse' => 'status',
					)
				),
				'item_name' => array(
					'header' => array(
						'value' => PostPrefix::text('prefix_name'),
					),
					'data' => array(
						'function' => create_function('$row', 'return PostPrefix::formatPrefix($row[\'id\']);'),
						'style' => 'width: 20%',
					),
					'sort' =>  array(
						'default' => 'name DESC',
						'reverse' => 'name',
					),
				),
				'boards' => array(
					'header' => array(
						'value' => PostPrefix::text('prefix_boards'),
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=showboards;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'board.png\');">'. PostPrefix::text('select_visible_boards'). '</a>',
							'params' => array(
								'id' => true,
								'name' => true,
							),
						),
						'class' => 'centertext',
						'style' => 'width: 4%',
					),
				),
				'groups' => array(
					'header' => array(
						'value' => PostPrefix::text('prefix_groups'),
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=showgroups;id=%1$d" onclick="return reqOverlayDiv(this.href, \'%2$s\', \'icons/members.png\');">'. PostPrefix::text('select_visible_groups'). '</a>',
							'params' => array(
								'id' => true,
								'name' => true,
							),
						),
						'class' => 'centertext',
						'style' => 'width: 4%',
					),
				),
				'added' => array(
					'header' => array(
						'value' => PostPrefix::text('prefix_date'),
						'class' => 'centertext',
					),
					'data' => array(
						'function' => create_function('$row', 'return timeformat($row[\'added\']);'),
						'class' => 'centertext',
						'style' => 'width: 8%',
					),
					'sort' => array(
						'default' => 'added DESC',
						'reverse' => 'added',
					)
				),
				'modify' => array(
					'header' => array(
						'value' => PostPrefix::text('prefix_modify'),
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'. $scripturl. '?action=admin;area=postprefix;sa=edit;id=%1$d">'. PostPrefix::text('prefix_modify'). '</a>',
							'params' => array(
								'id' => true,
							),
						),
						'style' => 'width: 5%',
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'id DESC',
						'reverse' => 'id',
					)
				),
				'delete' => array(
					'header' => array(
						'value' => $txt['delete']. ' <input type="checkbox" onclick="invertAll(this, this.form, \'delete[]\');" class="input_check" />',
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="delete[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'class' => 'centertext',
						'style' => 'width: 3%',
					),
				),
			),
			'form' => array(
				'href' => '?action=admin;area=postprefix;sa=delete',
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
				'include_sort' => true,
				'include_start' => true,
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'value' => '<input type="submit" size="18" value="'.$txt['delete']. '" class="button_submit" onclick="return confirm(\''.PostPrefix::text('prefix_delete_sure').'\');" />',
				),
				array(
					'position' => 'above_column_headers',
					'value' => $context['prefix']['addup'],
				),
			),
		);

		// Let's finishem
		createList($listOptions);
		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'prefixes';

	}

	public static function GetPrefixes($start, $items_per_page, $sort)
	{
		global $context, $smcFunc;

		// Get a list of all the item
		$result = $smcFunc['db_query']('', '
			SELECT id, name, status, color, added, member_groups, deny_member_groups, boards
			FROM {db_prefix}postprefixes
			ORDER by {raw:sort}
			LIMIT {int:start}, {int:maxindex}',
			array(
				'start' => $start,
				'maxindex' => $items_per_page,
				'sort' => $sort,
			)
		);
		// Return the data
		$context['prefix_list'] = array();
		while ($row = $smcFunc['db_fetch_assoc']($result))
			$context['prefix_list'][] = $row;
			$smcFunc['db_free_result']($result);
		return $context['prefix_list'];
	}

	public static function CountPrefixes()
	{
		global $smcFunc;

		// Count the items
		$items = $smcFunc['db_query']('', '
			SELECT COUNT(id)
			FROM {db_prefix}postprefixes'
		);
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

		$prefix = (int) $_REQUEST['id'];

		$request = $smcFunc['db_query']('', '
			SELECT id, member_groups
			FROM {db_prefix}postprefixes
			WHERE id = {int:id}',
			array(
				'id' => $prefix,
			)
		);
		$mg = $smcFunc['db_fetch_assoc']($request)['member_groups'];
		$groups = explode(',', $mg);

		$context['empty_groups'] = empty($mg) ? 1 : 0;

		if ($context['empty_groups'] == 0)
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

		$prefix = (int) $_REQUEST['id'];

		$request = $smcFunc['db_query']('', '
			SELECT id, boards
			FROM {db_prefix}postprefixes
			WHERE id = {int:id}',
			array(
				'id' => $prefix,
			)
		);
		$brd = $smcFunc['db_fetch_assoc']($request)['boards'];
		$boards = explode(',', $brd);

		$context['empty_boards'] = empty($brd) ? 1 : 0;

		if ($context['empty_boards'] == 0)
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

	public static function add()
	{
		global $context, $smcFunc, $modSettings, $txt, $user_info;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_prefixes_add');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_add_desc'),
		);
		$context['sub_template'] = 'postprefix_add';

		// Permissions
		if (!empty($modSettings['deny_boards_access']) || !empty($modSettings['permission_enable_deny']))
		{
			loadLanguage('ManagePermissions');
			loadLanguage('ManageBoards');
		}

		addInlineJavascript('
		$(document).ready(function (){
			$(\'#color\').colpick({
				layout:\'hex\',
				submit:0,
				colorScheme:\'light\',
				onChange:function(hsb,hex,rgb,el,bySetColor) {
					$(el).css(\'border-color\',\'#\'+hex);
					// Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
					if(!bySetColor) $(el).val(hex);
				}
			}).keyup(function(){
				$(this).colpickSetColor(this.value);
			});
		});', true);

		// Groups
		self::getGroups(false,false);

		// Boards
		self::getCategories(false);

	}

	public static function add2()
	{
		global $smcFunc, $context, $modSettings;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_prefixes_add');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_add_desc'),
		);

		// We need at least the 'name'
		if (!isset($_REQUEST['name']) || empty($_REQUEST['name']))
			fatal_error(PostPrefix::text('error_noprefix'));

		$name = $smcFunc['htmlspecialchars']($_REQUEST['name'], ENT_QUOTES);
		$status = isset($_REQUEST['status']) ? 1 : 0;
		$added = time();
		if (isset($_REQUEST['usecolor']) && ($_REQUEST['usecolor'] == 1))
		{
			$color = '#';
			$color .= str_replace('#', '', $_REQUEST['color']);
			$bgcolor = isset($_REQUEST['bgcolor']) ? 1 : 0;
		}
		else
		{
			$color = '';
			$bgcolor = 0;
		}
		$boards = (empty($_REQUEST['useboard']) ? '' : implode(',', $_REQUEST['useboard']));
		$member_groups = '';
		$deny_member_groups = '';

		// Enable deny permissions? Let's handle it
		if (!empty($modSettings['permission_enable_deny']))
		{
			foreach ($_POST['usegroup'] as $group => $action)
			{
				if ($action == 'allow')
					$allow[] = (int) $group;
				elseif ($action == 'deny')
					$deny[] = (int) $group;
			}
			if (!empty($allow))
			{
				$member_groups .= ((empty($_REQUEST['usegroup']) || !isset($_REQUEST['usegroup'])) ? '' : implode(',', $allow));
				unset($allow);
			}
			if (!empty($deny))
			{
				$deny_member_groups .= ((empty($_REQUEST['usegroup']) || !isset($_REQUEST['usegroup'])) ? '' : implode(',', $deny));;
				unset($deny);
			}
		}
		else
		{
			$member_groups .= ((empty($_REQUEST['usegroup']) || !isset($_REQUEST['usegroup'])) ? '' : implode(',', $_REQUEST['usegroup']));
			$deny_member_groups .= '';
		}

	

		// Insert the actual item
		$smcFunc['db_insert']('',
			'{db_prefix}postprefixes',
			array(
				'name' => 'string', 
				'status' => 'int',
				'added' => 'int',
				'color' => 'string',
				'bgcolor' => 'int',
				'member_groups' => 'string',
				'deny_member_groups' => 'string',
				'boards' => 'string',
			),
			array(
				'name' => $name,
				'status' => $status,
				'added' => $added,
				'color' => $color,
				'bgcolor' => $bgcolor,
				'member_groups' => $member_groups,
				'deny_member_groups' => $deny_member_groups,
				'boards' => $boards,
			),
			array()
		);

		// Get the new id
		$Getid = $smcFunc['db_insert_id']('{db_prefix}postprefixes', 'id');
		$id = (int) $Getid;
		
		// Send him to the items list
		redirectexit('action=admin;area=postprefix;sa=prefixes;added='. $id);


	}

	public static function edit()
	{
		global $context, $smcFunc, $modSettings, $txt, $user_info;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_prefixes_edit');
		$context[$context['admin_menu_name']]['current_subsection'] = 'prefixes';
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_edit_desc'),
		);
		$context['sub_template'] = 'postprefix_edit';

		// Permissions
		if (!empty($modSettings['deny_boards_access']) || !empty($modSettings['permission_enable_deny']))
		{
			loadLanguage('ManagePermissions');
			loadLanguage('ManageBoards');
		}

		$prefix = (int) $_REQUEST['id'];

		if (!isset($prefix) || empty($prefix))
			fatal_error(PostPrefix::text('error_unable_tofind'));
		// Does the prefix exist?
		$find = self::FindPrefix($prefix);
		if ($find == false)
			fatal_error(PostPrefix::text('error_unable_tofind'));

		$request = $smcFunc['db_query']('', '
			SELECT p.id, p.name, p.color, p.bgcolor, status, member_groups, deny_member_groups, boards
			FROM {db_prefix}postprefixes AS p
			WHERE p.id = {int:id}
			LIMIT 1',
			array(
				'id' => $prefix,
			)
		);

		$context['prefix'] = $smcFunc['db_fetch_assoc']($request);
		$context['prefix']['color'] = str_replace('#', '', $context['prefix']['color']);

		addInlineJavascript('
		$(document).ready(function (){
			$(\'#color\').colpick({
				layout:\'hex\',
				submit:0,
				colorScheme:\'light\',
				color:\''. $context['prefix']['color']. '\',
				onChange:function(hsb,hex,rgb,el,bySetColor) {
					$(el).css(\'border-color\',\'#\'+hex);
					// Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
					if(!bySetColor) $(el).val(hex);
				}
			}).keyup(function(){
				$(this).colpickSetColor(this.value);
			});
		});', true);

		// Groups
		self::getGroups(true, true);

		// Boards
		self::getCategories(true);

	}

	public static function edit2()
	{
		global $smcFunc, $context, $modSettings;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_prefixes_edit');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_edit_desc'),
		);

		$id = (int) $_REQUEST['id'];

		if (!isset($id) || empty($id))
			fatal_error(PostPrefix::text('error_unable_tofind'));
		// Does the prefix exist?
		$find = self::FindPrefix($id);
		if ($find == false)
			fatal_error(PostPrefix::text('error_unable_tofind'));

		// We need at least the 'name'
		if (!isset($_REQUEST['name']) || empty($_REQUEST['name']))
			fatal_error(PostPrefix::text('error_noprefix'));

		$name = $smcFunc['htmlspecialchars']($_REQUEST['name'], ENT_QUOTES);
		$status = isset($_REQUEST['status']) ? 1 : 0;
		if (isset($_REQUEST['usecolor']) && ($_REQUEST['usecolor'] == 1))
		{
			$color = '#';
			$color .= str_replace('#', '', $_REQUEST['color']);
			$bgcolor = isset($_REQUEST['bgcolor']) ? 1 : 0;
		}
		else
		{
			$color = '';
			$bgcolor = 0;
		}
		$boards = (empty($_REQUEST['useboard']) ? '' : implode(',', $_REQUEST['useboard']));
		$member_groups = '';
		$deny_member_groups = '';

		// Enable deny permissions? Let's handle it
		if (!empty($modSettings['permission_enable_deny']))
		{
			foreach ($_POST['usegroup'] as $group => $action)
			{
				if ($action == 'allow')
					$allow[] = (int) $group;
				elseif ($action == 'deny')
					$deny[] = (int) $group;
			}
			if (!empty($allow))
			{
				$member_groups .= ((empty($_REQUEST['usegroup']) || !isset($_REQUEST['usegroup'])) ? '' : implode(',', $allow));
				unset($allow);
			}
			if (!empty($deny))
			{
				$deny_member_groups .= ((empty($_REQUEST['usegroup']) || !isset($_REQUEST['usegroup'])) ? '' : implode(',', $deny));;
				unset($deny);
			}
		}
		else
		{
			$member_groups .= ((empty($_REQUEST['usegroup']) || !isset($_REQUEST['usegroup'])) ? '' : implode(',', $_REQUEST['usegroup']));
			$deny_member_groups .= '';
		}

		// Update the item information
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}postprefixes
			SET
				name = {string:name}, 
				status = {int:status},
				color = {string:color},
				bgcolor = {int:bgcolor},
				member_groups = {string:member_groups},
				deny_member_groups = {string:deny_member_groups},
				boards = {string:boards}
			WHERE id = {int:id}
			LIMIT 1',
			array(
				'name' => $name,
				'status' => $status,
				'color' => $color,
				'bgcolor' => $bgcolor,
				'member_groups' => $member_groups,
				'deny_member_groups' => $deny_member_groups,
				'boards' => $boards,
				'id' => $id,
			)
		);
		
		// Send him to the items list
		redirectexit('action=admin;area=postprefix;sa=prefixes;updated='. $id);

	}

	public static function delete()
	{
		global $context, $smcFunc;

		// If nothing was chosen to delete (shouldn't happen, but meh)
		if (!isset($_REQUEST['delete']))
			fatal_error(PostPrefix::text('error_unable_tofind'));
				
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

	public static function FindPrefix($id)
	{
		global $smcFunc;

		$id = (int) $id;

		$result = $smcFunc['db_query']('', '
			SELECT id
			FROM {db_prefix}postprefixes
			WHERE id = {int:id}',
			array(
				'id' => $id,
			)
		);
		$row = $smcFunc['db_fetch_assoc']($result);
		if (empty($row))
			return false;
		else
			return true;

	}

	public static function updatestatus()
	{
		global $smcFunc, $context, $modSettings;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_prefixes_edit');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_edit_desc'),
		);

		$id = (int) $_REQUEST['id'];
		$status = (int) (!isset($_REQUEST['status']) || empty($_REQUEST['status']) ? 0 : $_REQUEST['status']);

		if (!isset($id) || empty($id))
			fatal_error(PostPrefix::text('error_unable_tofind'));
		// Does the prefix exist?
		$find = self::FindPrefix($id);
		if ($find == false)
			fatal_error(PostPrefix::text('error_unable_tofind'));

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

	public static function getCategories($allow = true)
	{
		global $context, $smcFunc;

		if ($allow)
		{
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
		}

		// Get the boards and categories
		$request = $smcFunc['db_query']('', '
			SELECT b.id_cat, c.name AS cat_name, b.id_board, b.name, b.child_level, b.member_groups, b.require_prefix
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)'. (allowedTo('manage_boards') ? '' :
			'WHERE {query_see_board}'). '
			ORDER BY board_order',
			array(
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
				'allow' => ($allow ? (in_array($row['id_board'], $boards) ? true : '') : ''),
				'require' => $row['require_prefix'] == 1 ? true : '',
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

	public static function require_boards()
	{
		global $context;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_require');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_require_desc'),
		);
		$context['sub_template'] = 'require_prefix';

		$context['required']['updated'] = '';
		if (isset($_REQUEST['updated']))
			$context['required']['updated'] .= '<div class="infobox">'.PostPrefix::text('required_updated').'</div>';

		// Boards
		self::getCategories(false);
	}

	public static function require_boards2()
	{
		global $smcFunc, $context, $modSettings;

		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_require');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_prefixes_require_desc'),
		);

		// Make sure all IDs are numeric
		foreach ($_REQUEST['requireboard'] as $key => $value)
			$_REQUEST['requireboard'][$key] = (int) $value;

		// Update the item information
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}boards
			SET
				require_prefix = CASE WHEN id_board IN ({array_int:ids}) THEN 1 ELSE 0 END',
			array(
				'ids' => empty($_REQUEST['requireboard']) ? array(0) : $_REQUEST['requireboard'],
			)
		);
		
		// Send him to the items list
		redirectexit('action=admin;area=postprefix;sa=require;updated');
	}

	public static function permissions($return_config = false)
	{
		global $context, $scripturl, $sourcedir, $txt;
		
		// Set all the page stuff
		$context['page_title'] = PostPrefix::text('main') . ' - '. PostPrefix::text('tab_permissions');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => PostPrefix::text('tab_permissions_desc'),
		);

		require_once($sourcedir . '/ManageServer.php');

		// PostPrefix mod do not play nice with guests. Permissions are already hidden for them, let's exterminate any hint of them in this section.
		$context['permissions_excluded'] = array(-1);
		$config_vars = array(
			array('permissions', 'manage_prefixes', 'subtext' => $txt['permissionhelp_manage_prefixes']),
			'',
			array('permissions', 'set_prefix', 'subtext' => $txt['permissionhelp_set_prefix']),
		);

		if ($return_config)
			return $config_vars;

		$context['post_url'] = $scripturl . '?action=admin;area=postprefix;sa=permissions;save';

		// Saving?
		if (isset($_GET['save']))
		{
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=postprefix;sa=permissions');
		}

		prepareDBSettingContext($config_vars);
	}
}