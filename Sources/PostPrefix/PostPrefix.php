<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

namespace PostPrefix;

use PostPrefix\Helper\Database;

if (!defined('SMF'))
	die('No direct access...');

class PostPrefix
{
	/**
	 * PostPrefix::initialize()
	 * 
	 * Loads the mod hooks and some default settings
	 * 
	 * @return void
	 */
	public static function initialize() : void
	{
		// Main Hooks
		self::essentialHooks();

		// Board Hooks
		self::boardHooks();

		// Default settings
		self::setDefaults();
	}

	/**
	 * PostPrefix::setDefaults()
	 * 
	 * Set default settings
	 * 
	 * @return void
	 */
	public static function setDefaults() : void
	{
		global $modSettings;

		$defaults = [
			'PostPrefix_enable_filter' => 0,
			'PostPrefix_filter_boards' => '',
			'PostPrefix_select_order' => 0,
			'PostPrefix_prefix_boards_require' => '',
		];
		$modSettings = array_merge($defaults, $modSettings);
	}

	/**
	 * PostPrefix::essentialHooks()
	 * 
	 * Essential hooks for the mod that should be loaded regardless of anything.
	 * 
	 * @return void
	 */
	public static function essentialHooks() : void
	{
		$hooks = [
			'autoload',
			'actions',
			'pre_css_output',
			'menu_buttons',
			'load_theme',
		];
		foreach ($hooks as $point )
			add_integration_function('integrate_' . $point, __CLASS__ . '::' . $point, false);
	}

	/**
	 * PostPrefix::boardHooks()
	 * 
	 * These hooks are also essential, but are only needed on boards and topics
	 * 
	 * @return void
	 */
	public static function boardHooks() : void
	{
		// Nothing or you get nada.
		if (isset($_REQUEST['action']) || !empty($_REQUEST['action']))
			return;

		// Message Index
		add_integration_function('integrate_pre_messageindex', __NAMESPACE__ . '\Integration\MessageIndex::topic_count#', false);
		add_integration_function('integrate_message_index', __NAMESPACE__ . '\Integration\MessageIndex::topics_list#', false);
		add_integration_function('integrate_messageindex_buttons', __NAMESPACE__ . '\Integration\MessageIndex::topics_prefixes#', false);

		// Display
		add_integration_function('integrate_display_topic', __NAMESPACE__ . '\Integration\Topic::display_topic#', false);
		add_integration_function('integrate_display_message_list', __NAMESPACE__ . '\Integration\Topic::view_topic#', false);
	}

	/**
	 * PostPrefix::autoload()
	 * 
	 * Add the PostPrefix into the autoloader
	 * 
	 * @param array $classMap
	 * @return void
	 */
	public static function autoload(array &$classMap) : void
	{
		$classMap['PostPrefix\\'] = 'PostPrefix/';
	}

	/**
	 * PostPrefix::pre_css_output()
	 * 
	 * Insert the css file
	 * @return void
	 */
	public static function pre_css_output() : void
	{
		// Postprefix CSS file
		loadCSSFile('postprefix.css', ['default_theme' => true, 'minimize' => false], 'smf_postprefix');
	}

	/**
	 * PostPrefix::actions()
	 * 
	 * Add a new action for something special later
	 * And add a few more hooks depending on the actions
	 * @return void
	 */
	public static function actions() : void
	{
		// Need to be somewhere
		if (!isset($_REQUEST['action']) || empty($_REQUEST['action']))
			return;

		// Hooks per action
		switch ($_REQUEST['action'])
		{
			case 'helpadmin':
				add_integration_function('integrate_helpadmin', __NAMESPACE__ .'\Integration\Permissions::language', false);
				break;
			case 'admin':
				add_integration_function('integrate_admin_areas', __NAMESPACE__ . '\Manage\Settings::hookAreas#', false);
				break;
			case 'post':
			case 'post2':
				add_integration_function('integrate_before_create_topic', __NAMESPACE__ . '\Integration\Posting::before_create_topic', false);
				add_integration_function('integrate_modify_post', __NAMESPACE__ . '\Integration\Posting::modify_post', false);
				add_integration_function('integrate_post2_start', __NAMESPACE__ . '\Integration\Posting::post2_start', false);
				add_integration_function('integrate_post_errors', __NAMESPACE__ . '\Integration\Posting::post_errors', false);
				add_integration_function('integrate_post_end', __NAMESPACE__ . '\Integration\Posting::post_end', false);
				break;
		}
	}

	/**
	 * PostPrefix::load_prefixes()
	 * 
	 * Loads the list of prefixes
	 */
	public static function load_theme()
	{
		global $user_info, $context, $modSettings;

		// It's only for post pages really...
		if (!isset($_REQUEST['action']) && empty($_REQUEST['action']) || ($_REQUEST['action'] !== 'post' && $_REQUEST['action'] !== 'post2'))
			return;

		// Load the prefixes
		if (($context['user_prefixes']['post'] = cache_get_data('user_postprefixes_u' . $user_info['id'], 3600)) === null)
		{
			$context['user_prefixes']['post'] = Database::pNested(
				'pp.' . (!empty($modSettings['PostPrefix_select_order']) ? 'id' : 'name'), 'postprefixes AS pp',
				array_merge(array_merge(Database::$_prefix_normal, Database::$_boards_columns), Database::$_groups_columns), ['b.id_board'], 'boards',
				'WHERE pp.status = 1' . (allowedTo('postprefix_manage') ? '' : '
					AND ppg.id_group ' . ($user_info['is_guest'] ? '= {int:guest}' : 'IN ({array_int:groups})')
				), 
				'LEFT JOIN {db_prefix}postprefixes_groups AS ppg ON (ppg.id_prefix = pp.id)
				LEFT JOIN {db_prefix}postprefixes_boards AS ppb ON (ppb.id_prefix = pp.id)
				LEFT JOIN {db_prefix}boards AS b ON (b.id_board = ppb.id_board)',
				[
					'groups' => array_unique(array_merge($user_info['groups'], [0])),
					'guest' => -1,
				]
			);
			cache_put_data('user_postprefixes_u' . $user_info['id'], $context['user_prefixes']['post'], 3600);
		}
	}

	/**
	 * PostPrefix::format()
	 * 
	 * Add styles and format to the prefix
	 * 
	 * @param array $prefix The prefix data
	 * @param array $styles Any additional styles for the prefix
	 * @return string $format The formatted prefix
	 */
	public static function format(array $prefix, array $styles = []) : string
	{
		// Is the array what we expect?
		if (empty($prefix))
			return '';
		elseif (!isset($prefix['prefix_name']) && isset($prefix['name']))
		{
			$prefix['prefix_id'] = $prefix['id'];
			$prefix['prefix_name'] = $prefix['name'];
			$prefix['prefix_color'] = $prefix['color'];
			$prefix['prefix_bgcolor'] = $prefix['bgcolor'];
			$prefix['prefix_invert_color'] = $prefix['invert_color'];
			$prefix['prefix_icon_url'] = $prefix['icon_url'];
		}

		// Check for no icon
		if (empty($prefix['prefix_icon_url']))
		{
			// Prefix
			$format = '<span class="postprefix-'. $prefix['prefix_id']. ' postprefix-all';

			// Background color or color
			if (!empty($prefix['prefix_bgcolor']) || !empty($prefix['prefix_color']))
			{
				// Check if it's inverted when using both color and background color
				if (!empty($prefix['prefix_bgcolor']) && !empty($prefix['prefix_color']))
					$format .= ' text-'. (!empty($prefix['prefix_invert_color']) ? 'inverted' : 'default'). '" style="background-color:'. $prefix['prefix_color'];
				// With no background, just use the color provided
				elseif (!empty($prefix['prefix_color']) && empty($prefix['prefix_bgcolor']))
					$format .= '" style="color:'. $prefix['prefix_color'];
			}
			// Prefix name
			$format .= ';' . (!empty($styles) ? implode(';', $styles) : '') . '">' . $prefix['prefix_name'] . '</span>';
		}
		// Provide just an icon
		else
		{
			$format = '<img class="postprefix-all" id="postprefix-'. $prefix['prefix_id']. '" src="'. $prefix['prefix_icon_url']. '" alt="'. $prefix['prefix_name']. '" title="'. $prefix['prefix_name']. '" />';
		}

		return $format;
	}

	public static function menu_buttons(array &$buttons)
	{
		// Add the prefix permission to the admin button
		$buttons['admin']['show'] = $buttons['admin']['show']  || allowedTo('postprefix_manage');
	}
}