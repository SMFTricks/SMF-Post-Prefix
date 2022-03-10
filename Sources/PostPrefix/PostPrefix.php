<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix;

if (!defined('SMF'))
	die('No direct access...');

class PostPrefix
{
	public static $version = '4.0';

	public static function initialize()
	{
		// Main Hooks
		self::essentialHooks();

		// Board Hooks
		self::boardHooks();

		// Default settings
		self::setDefaults();
	}

	public static function setDefaults()
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

	public static function essentialHooks()
	{
		$hooks = [
			'autoload',
			'actions',
			'pre_css_output',
			'menu_buttons',
		];
		foreach ($hooks as $point )
			add_integration_function('integrate_' . $point, __CLASS__ . '::' . $point, false);
	}

	public static function boardHooks()
	{
		// No actions...
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
	public static function autoload(array &$classMap)
	{
		$classMap['PostPrefix\\'] = 'PostPrefix/';
	}

	/**
	 * PostPrefix::pre_css_output()
	 * 
	 * Insert the css file
	 * @return void
	 */
	public static function pre_css_output()
	{
		// Postprefix CSS file
		loadCSSFile('postprefix.css', ['default_theme' => true, 'minimize' => false], 'smf_postprefix');
	}

	/**
	 * PostPrefix::actions()
	 * 
	 * Add a few more hooks depending on the actions
	 * @return void
	 */
	public static function actions()
	{
		switch ($_REQUEST['action'])
		{
			case 'admin':
				add_integration_function('integrate_admin_areas', __NAMESPACE__ . '\Settings::hookAreas#', false);
				break;
			case 'post':
			case 'post2':
				add_integration_function('integrate_before_create_topic', __NAMESPACE__ . '\Integration\Posting::before_create_topic', false);
				add_integration_function('integrate_create_post', __NAMESPACE__ . '\Integration\Posting::create_post', false);
				add_integration_function('integrate_modify_post', __NAMESPACE__ . '\Integration\Posting::modify_post', false);
				add_integration_function('integrate_post2_start', __NAMESPACE__ . '\Integration\Posting::post2_start', false);
				add_integration_function('integrate_post_errors', __NAMESPACE__ . '\Integration\Posting::post_errors', false);
				add_integration_function('integrate_post_end', __NAMESPACE__ . '\Integration\Posting::post_end', false);
				break;
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
		static $format;

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