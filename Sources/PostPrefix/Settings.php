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

class Settings
{
	/**
	 * Manage::admin_areas()
	 *
	 * Add our new section and load the language and template
	 * @param array $admin_menu An array with all the admin settings buttons
	 * @return
	 */
	public static function hookAreas(&$admin_areas)
	{
		global $scripturl, $txt;
		
		loadLanguage('PostPrefix/');

		$insert = 'postsettings';
		$counter = 0;

		foreach ($admin_areas['layout']['areas'] as $area => $dummy)
			if (++$counter && $area == $insert )
				break;

		$admin_areas['layout']['areas'] = array_merge(
			array_slice($admin_areas['layout']['areas'], 0, $counter),
			[
				'postprefix' => [
					'label' => $txt['PostPrefix_main'],
					'icon' => 'reports',
					'function' => __NAMESPACE__ . '\Settings::index',
					'permission' => ['manage_prefixes'],
					'subsections' => [
						'prefixes' => [$txt['PostPrefix_tab_prefixes']],
						'add' => [$txt['PostPrefix_tab_prefixes_add']],
						'require' => [$txt['PostPrefix_tab_require']],
						'settings' => [$txt['PostPrefix_tab_settings']],
					],
				],
			],
			array_slice($admin_areas['layout']['areas'], $counter)
		);

		// Permissions
		add_integration_function('integrate_load_permissions', __CLASS__.'::permissions', false);
		add_integration_function('integrate_load_illegal_guest_permissions', __CLASS__.'::illegal_guest_permissions', false);
	}

	/**
	 * Manage::permissions()
	 *
	 * Permissions for manage prefixes and a global permission for use the prefixes
	 * @param array $permissionGroups An array containing all possible permissions groups.
	 * @param array $permissionList An associative array with all the possible permissions.
	 * @return
	 */
	public static function permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
	{
		// Manage prefix
		$permissionList['membergroup']['manage_prefixes'] = [false, 'maintenance'];
		// Topic?
		$permissionList['board']['set_prefix'] = [false, 'topic'];
	}
	
	public static function illegal_guest_permissions()
	{
		global $context;

		// Guests do not play nicely with this mod
		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], array('manage_prefixes'));
	}

	public static function index()
	{
		global $context, $txt;

		loadtemplate('PostPrefix');

		$subactions = [
			'prefixes' => 'Manage::prefixes',
			'showgroups' => 'showgroups',
			'showboards' => 'showboards',
			'add' => 'Manage::set_prefix',
			'edit' => 'Manage::set_prefix',
			'save' => 'Manage::save',
			'delete' => 'Manage::delete',
			'status' => 'Manage::status',
			//'ups' => 'updatestatus',
			//'require' => 'require_boards',
			//'require2' => 'require_boards2',
			//'settings' => 'settings',
		];
		$sa = isset($_GET['sa'], $subactions[$_GET['sa']]) ? $_GET['sa'] : 'prefixes';

		// Create the tabs for the template.
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_tab_prefixes'],
			'description' => $txt['PostPrefix_tab_prefixes_desc'],
			'tabs' => [
				'prefixes' => ['description' => $txt['PostPrefix_tab_prefixes_desc']],
				'add' => ['description' => $txt['PostPrefix_tab_prefixes_add_desc']],
				'require' => ['description' => $txt['PostPrefix_tab_require_desc']],
				'settings' => ['description' => $txt['PostPrefix_tab_settings_desc']],
			],
		];
		call_helper(__NAMESPACE__ . '\\' . $subactions[$sa]);
	}

	public static function require_boards()
	{
		global $context, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_require'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_require_desc'],
		);
		$context['sub_template'] = 'require_prefix';

		// Boards
		self::getCategories(false);
	}

	public static function require_boards2()
	{
		global $smcFunc, $context, $modSettings, $txt;

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_require'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_require_desc'],
		);

		if (empty($_REQUEST['requireboard']) || !isset($_REQUEST['requireboard']))
			$_REQUEST['requireboard'] = array();
		
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


	public static function permissions2($return_config = false)
	{
		global $context, $scripturl, $sourcedir, $txt;
		
		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_permissions'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_permissions_desc'],
		);
		loadTemplate('Admin');
		$context['sub_template'] = 'show_settings';
		require_once($sourcedir . '/ManageServer.php');

		// PostPrefix mod do not play nice with guests. Permissions are already hidden for them, let's exterminate any hint of them in this section.
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

		$permissions = array(
			-1 => array(
				'manage_prefixes',
			),
		);
		foreach ($permissions as $group => $perm_list)
			foreach ($perm_list as $perm)
				unset ($context[$perm][$group]);
	}

	public static function settings($return_config = false)
	{
		global $context, $scripturl, $sourcedir, $txt;

		require_once($sourcedir . '/ManageServer.php');
		loadTemplate('Admin');
		$context['sub_template'] = 'show_settings';

		// Set all the page stuff
		$context['page_title'] = $txt['PostPrefix_main'] . ' - '. $txt['PostPrefix_tab_settings'];
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $context['page_title'],
			'description' => $txt['PostPrefix_tab_settings_desc'],
		);

		$config_vars = array(
			array('check', 'PostPrefix_enable_filter', 'subtext' => $txt['PostPrefix_enable_filter_desc']),
			array('select', 'PostPrefix_select_order', array(
					$txt['PostPrefix_prefix_name'],
					$txt['PostPrefix_prefix_id'],
					$txt['PostPrefix_prefix_date'],
				),
				'subtext' => $txt['PostPrefix_select_order_desc']
			),
			array('select', 'PostPrefix_select_order_dir', array(
					$txt['PostPrefix_DESC'],
					$txt['PostPrefix_ASC'],
				),
				'subtext' => $txt['PostPrefix_select_order_dir_desc']
			),
		);

		if ($return_config)
			return $config_vars;

		$context['post_url'] = $scripturl . '?action=admin;area=postprefix;sa=settings;save';

		// Saving?
		if (isset($_GET['save']))
		{
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=postprefix;sa=settings');
		}

		prepareDBSettingContext($config_vars);
	}
}