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
	 * @var array Subactions array for each section/area of the shop.
	 */
	protected $_subactions = [];

	/**
	 * @var string The current area.
	 */
	protected $_sa;

	/**
	 * Settings::__construct()
	 *
	 * Call certain administrative hooks and load the language files
	 */
	function __construct()
	{
		// Load languages
		loadLanguage('PostPrefix/');

		// Permissions
		add_integration_function('integrate_load_permissions', __CLASS__.'::permissions', false);
		add_integration_function('integrate_load_illegal_guest_permissions', __CLASS__.'::illegal_guest_permissions', false);

		// Boards settings
		if (isset($_REQUEST['area']) && $_REQUEST['area'] == 'manageboards')
		{
			add_integration_function('integrate_edit_board', __CLASS__.'::edit_board', false);
			add_integration_function('integrate_modify_board', __CLASS__.'::modify_board', false);
		}

		// Array of sections
		$this->_subactions = [
			'prefixes' => 'Manage::prefixes#',
			'add' => 'Manage::set_prefix#',
			'edit' => 'Manage::set_prefix#',
			'save' => 'Manage::save#',
			'delete' => 'Manage::delete',
			'status' => 'Manage::status',
			'groups' => 'Manage::groups#',
			'boards' => 'Manage::boards#',
			'options' => 'Settings::options',
		];
		$this->_sa = isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'prefixes';
	}

	 /**
	 * Settings::hookAreas()
	 *
	 * Adding the admin section
	 * @param array $admin_areas An array with all the admin areas
	 * @return void
	 */
	public static function hookAreas(&$admin_areas)
	{
		global $txt;

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
					'function' => __NAMESPACE__ . '\Settings::index#',
					'permission' => ['postprefix_manage'],
					'subsections' => [
						'prefixes' => [$txt['PostPrefix_tab_prefixes']],
						'add' => [$txt['PostPrefix_tab_prefixes_add']],
						'options' => [$txt['PostPrefix_tab_options']],
					],
				],
			],
			array_slice($admin_areas['layout']['areas'], $counter)
		);
	}

	public static function permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
	{
		// Manage prefix
		$permissionList['membergroup']['postprefix_manage'] = [false, 'maintenance'];
		// Topic?
		$permissionList['board']['postprefix_set'] = [false, 'topic'];
	}
	
	public static function illegal_guest_permissions()
	{
		global $context;

		// Guests do not play nicely with this mod
		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], ['postprefix_manage']);
	}

	public static function edit_board()
	{
		global $context, $txt, $modSettings;

		// Enable filter
		if (!empty($modSettings['PostPrefix_enable_filter']))
			$context['custom_board_settings']['PostPrefix_enable_filter'] = [
				'dt' => '<strong>'. $txt['PostPrefix_enable_filter']. '</strong><br /><span class="smalltext">'. $txt['PostPrefix_enable_filter_desc']. '</span>',
				'dd' => '<input type="checkbox" name="PostPrefix_enable_filter" class="input_check"'. (in_array($context['board']['id'], explode(',', $modSettings['PostPrefix_filter_boards'])) ? ' checked="checked"' : ''). '>',
			];
		// Require prefix
		$context['custom_board_settings']['PostPrefix_prefix_boards_require'] = [
			'dt' => '<strong>'. $txt['PostPrefix_prefix_boards_require']. '</strong><br /><span class="smalltext">'. $txt['PostPrefix_prefix_boards_require_desc']. '</span>',
			'dd' => '<input type="checkbox" name="PostPrefix_prefix_boards_require" class="input_check"'. (in_array($context['board']['id'], explode(',', $modSettings['PostPrefix_prefix_boards_require'])) ? ' checked="checked"' : ''). '>',
		];
	}

	public static function modify_board($id, $boardOptions, &$boardUpdates, &$boardUpdateParameters)
	{
		global $modSettings;

		// Enable filter
		if (!empty($modSettings['PostPrefix_enable_filter']))
		{
			$boardOptions['PostPrefix_enable_filter'] = isset($_POST['PostPrefix_enable_filter']);
			if (isset($boardOptions['PostPrefix_enable_filter']))
			{
				if (!empty($boardOptions['PostPrefix_enable_filter']) && !in_array($id, explode(',', $modSettings['PostPrefix_filter_boards'])))
					updateSettings(['PostPrefix_filter_boards' => !empty($modSettings['PostPrefix_filter_boards']) ? implode(',', array_merge(explode(',', $modSettings['PostPrefix_filter_boards']), [$id])) : $id]);
				elseif (empty($boardOptions['PostPrefix_enable_filter']) && in_array($id, explode(',', $modSettings['PostPrefix_filter_boards'])))
					updateSettings(['PostPrefix_filter_boards' => implode(',', array_diff(explode(',', $modSettings['PostPrefix_filter_boards']), [$id]))]);
			}
		}

		// Require prefix
		$boardOptions['PostPrefix_prefix_boards_require'] = isset($_POST['PostPrefix_prefix_boards_require']);
		if (isset($boardOptions['PostPrefix_prefix_boards_require']))
		{
			if (!empty($boardOptions['PostPrefix_prefix_boards_require']) && !in_array($id, explode(',', $modSettings['PostPrefix_prefix_boards_require'])))
				updateSettings(['PostPrefix_prefix_boards_require' => !empty($modSettings['PostPrefix_prefix_boards_require']) ? implode(',', array_merge(explode(',', $modSettings['PostPrefix_prefix_boards_require']), [$id])) : $id]);
			elseif (empty($boardOptions['PostPrefix_prefix_boards_require']) && in_array($id, explode(',', $modSettings['PostPrefix_prefix_boards_require'])))
				updateSettings(['PostPrefix_prefix_boards_require' => implode(',', array_diff(explode(',', $modSettings['PostPrefix_prefix_boards_require']), [$id]))], true);
		}
	}

	public function index()
	{
		global $context, $txt;

		// Create the tabs for the template.
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_tab_prefixes'],
			'description' => $txt['PostPrefix_tab_prefixes_desc'],
			'tabs' => [
				'prefixes' => ['description' => $txt['PostPrefix_tab_prefixes_desc']],
				'add' => ['description' => $txt['PostPrefix_tab_prefixes_add_desc']],
				'options' => ['description' => $txt['PostPrefix_tab_options_desc']],
			],
		];
		call_helper(__NAMESPACE__ . '\\' . $this->_subactions[$this->_sa]);
	}

	public static function options($return_config = false)
	{
		global $context, $txt, $sourcedir;

		require_once($sourcedir . '/ManageServer.php');
		loadLanguage('ManageSettings');

		// Set all the page stuff
		$context['sub_template'] = 'show_settings';
		$context['page_title'] = $txt['PostPrefix_main']. ' - ' . $txt['PostPrefix_tab_options'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $context['page_title'];

		$config_vars = [
			['title', 'PostPrefix_tab_options'],
			['check', 'PostPrefix_enable_filter', 'subtext' => $txt['PostPrefix_enable_filter_desc']],
			['boards', 'PostPrefix_filter_boards', 'subtext' => $txt['PostPrefix_filter_boards_desc']],
			['select', 'PostPrefix_select_order', [
					$txt['PostPrefix_prefix_name'],
					$txt['PostPrefix_prefix_id'],
			],
				'subtext' => $txt['PostPrefix_select_order_desc']
			],
			'',
			['boards', 'PostPrefix_prefix_boards_require', 'subtext' => $txt['PostPrefix_prefix_boards_require_desc']],
			['permissions', 'postprefix_manage', 'subtext' => $txt['permissionhelp_postprefix_manage']],
			['permissions', 'postprefix_set', 'subtext' => $txt['permissionhelp_postprefix_set']],
		];

		// Save!
		Helper::Save($config_vars, $return_config, 'options');
	}
}