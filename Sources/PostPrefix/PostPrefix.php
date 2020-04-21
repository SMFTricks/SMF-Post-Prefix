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

class PostPrefix
{
	public static $version = '3.0';

	public static function initialize()
	{
		self::defineHooks();
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

	public static function defineHooks()
	{
		$hooks = [
			'autoload',
			'actions',
			'message_index',
			'pre_messageindex',
			'messageindex_buttons',
			'display_topic',
			'display_message_list',
			'load_theme',
		];
		foreach ($hooks as $point )
			add_integration_function('integrate_' . $point, __CLASS__ . '::'.$point, false);
	}

	public static function autoload(&$classMap)
	{
		$classMap['PostPrefix\\'] = 'PostPrefix/';
	}

	public static function load_theme()
	{
		loadCSSFile('postprefix.css', ['default_theme' => true]);
	}

	public static function actions(&$actions)
	{
		switch ($_REQUEST['action'])
		{
			case 'admin':
				add_integration_function('integrate_admin_areas', __NAMESPACE__ . '\Settings::hookAreas', false, '$sourcedir/PostPrefix/Settings.php');
				break;
			case 'post':
			case 'post2':
				add_integration_function('integrate_before_create_topic', __CLASS__ . '::before_create_topic', false);
				add_integration_function('integrate_create_post', __CLASS__ . '::create_post', false);
				add_integration_function('integrate_modify_post', __CLASS__ . '::modify_post', false);
				add_integration_function('integrate_post2_start', __CLASS__ . '::post2_start', false);
				add_integration_function('integrate_post_errors', __CLASS__ . '::post_errors', false);
				add_integration_function('integrate_post_end', __CLASS__ . '::post_end', false);
				break;
		}
	}

	public static function before_create_topic(&$msgOptions, &$topicOptions, &$posterOptions, &$topic_columns, &$topic_parameters)
	{		
		$topic_columns = array_merge($topic_columns, ['id_prefix' => 'int']);
		$topic_parameters = array_merge($topic_parameters, [$topicOptions['id_prefix'] === null ? 0 : $topicOptions['id_prefix']]);
	}

	public static function create_post(&$msgOptions, &$topicOptions, &$posterOptions, &$message_columns, &$message_parameters)
	{
		$topicOptions = array_merge($topicOptions, ['id_prefix' => isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null]);
	}

	public static function modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		$topicOptions['id_prefix'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null;

		// It should have a prefix
		if ($topicOptions['id_prefix'] != null)
			Helper::Update('topics', ['id_prefix' => $topicOptions['id_prefix'] === null ? 0 : (int) $topicOptions['id_prefix'], 'id_topic' => $topicOptions['id']], 'id_prefix = {int:id_prefix}','WHERE id_topic = {int:id_topic}');
	}

	public static function post2_start(&$post_errors)
	{
		global $board, $modSettings;

		// Check if the topic needs prefix
		if ((!isset($_POST['id_prefix']) || $_POST['id_prefix'] === 0 || empty($_POST['id_prefix'])) && (in_array($board, explode(',', $modSettings['PostPrefix_prefix_boards_require']))) && isset($_REQUEST['prefix_istopic']))
			$post_errors[] = 'no_prefix';
	}

	public static function post_errors(&$post_errors, &$minor_errors, $form_message, $form_subject)
	{
		global $context, $topic, $board, $modSettings, $user_info;

		if (isset($_REQUEST['message']) || isset($_REQUEST['quickReply']) || !empty($context['post_error']))
			$context['prefix_data']['id_prefix'] = isset($_REQUEST['id_prefix']) ? $_REQUEST['id_prefix'] : 0;
		elseif (isset($_REQUEST['msg']) && !empty($topic))
		{
			$_REQUEST['msg'] = (int) $_REQUEST['msg'];

			// Get the existing message. Editing.
			$context['prefix_data'] = Helper::Get('', '', '', 'messages AS m', ['m.id_msg', 't.id_prefix'], 'WHERE m.id_msg = ' . $_REQUEST['msg'], true, 'INNER JOIN {db_prefix}topics AS t ON (t.id_topic = '. $topic . ')');

			// The message they were trying to edit was most likely deleted.
			if (empty($context['prefix_data']))
				fatal_lang_error('no_message', false);
		}
		else
			$context['prefix_data']['id_prefix'] = 0;

		// Require prefix?
		$minor_errors = array_merge($minor_errors, ['no_prefix']);
		$_SESSION['require_prefix'] = (in_array($board, explode(',', $modSettings['PostPrefix_prefix_boards_require'])) ? 1 : 0);

		// Can the user set prefixes
		if (allowedTo('postprefix_set'))
		{
			// Language file
			loadLanguage('PostPrefix/');

			// Load the prefixes
			$context['prefix']['post'] = Helper::Get(0, 10000, (!empty($modSettings['PostPrefix_select_order']) ? 'pp.id' : 'pp.name'), Manage::$table . ' AS pp', Manage::$columns, 'WHERE pp.status = 1 AND FIND_IN_SET('.$board.', pp.boards)'. (allowedTo('postprefix_manage') ? '' : ' AND (FIND_IN_SET(' . implode(', pp.groups) OR FIND_IN_SET('. $user_info['groups']) . ', pp.groups))'));
		}
	}

	public static function post_end()
	{
		global $txt, $context;

		if (!empty($context['prefix']['post']) && $context['is_first_post'])
		{
			$context['posting_fields']['topic_prefix'] = [
				'label' => [
					'text' => $txt['PostPrefix_select_prefix'],
					'class' => isset($context['post_error']['no_prefix']) ? 'error' : '',
				],
				'input' => [
					'type' => 'select',
					'attributes' => [
						'name' => 'id_prefix',
					],
					'options' => [
						'PostPrefix_select_prefix' => [
							'label' => $txt['PostPrefix_select_prefix'],
							'options' => [
								'none' => [
									'label' => $txt['PostPrefix_prefix_none'],
									'value' => 0,
									'selected' => $context['prefix_data']['id_prefix'] == 0 ? true : false,
								],
							],
						],
					],
				],
			];
			foreach ($context['prefix']['post'] as $prefix)
				$context['posting_fields']['topic_prefix']['input']['options']['PostPrefix_select_prefix']['options'][$prefix['id']] = [
					'label' => $prefix['name'],
					'value' => $prefix['id'],
					'selected' => $prefix['id'] == $context['prefix_data']['id_prefix'] ? true : false,
				];
			$context['posting_fields']['prefix_istopic'] = [
				'label' => [
					'html' => '',
					'text' => '',
				],
				'input' => [
					'html' => '<input type="hidden" name="prefix_istopic" value="1">',
					'type' => '',
				],
			];
		}
	}

	public static function format($prefix, $styles = '')
	{
		if (empty($prefix['icon_url']))
		{
			$format = '<span id="postprefix-'. $prefix['id']. '" class="postprefix-all';
			if (!empty($prefix['bgcolor']) || !empty($prefix['color']))
			{
				if (!empty($prefix['bgcolor']) && !empty($prefix['color']))
					$format .= ' text-'. (!empty($prefix['invert_color']) ? 'inverted' : 'default'). '" style="background-color:'. $prefix['color'];
				elseif (!empty($prefix['color']) && empty($prefix['bgcolor']))
					$format .= '" style="color:'. $prefix['color'];
			}
			$format .= $styles . '">' . $prefix['name'] . '</span>';
		}
		else
			$format = '<img class="postprefix-all" id="postprefix-'. $prefix['id']. '" src="'. $prefix['icon_url']. '" alt="'. $prefix['name']. '" title="'. $prefix['name']. '" />';

		return $format;
	}

	public static function messageindex_buttons()
	{
		global $modSettings, $context, $user_info, $board;

		// Rewrite the biach
		$context['postprefix_topics'] = [];
		foreach ($context['topics'] as $topic => $value)
		{
			$context['postprefix_topics'][$topic] = $context['topics'][$topic];

			// Topic has a prefix?
			if (!empty($context['topics'][$topic]['id_prefix']) && !empty($context['postprefix_topics'][$topic]['postprefix_status']))
			{
				$context['postprefix_topics'][$topic]['prefix'] = self::prefix_array(Manage::$columns);
				foreach ($context['postprefix_topics'][$topic]['prefix'] as $prefix)
					$context['postprefix_topics'][$topic]['prefix'][$prefix] = $context['postprefix_topics'][$topic]['postprefix_'.$prefix];
		
				$context['postprefix_topics'][$topic]['first_post']['link'] = self::format($context['postprefix_topics'][$topic]['prefix']) . ' ' . $context['topics'][$topic]['first_post']['link'];
			}
		}
		// Yo wassup :P
		$context['topics'] = $context['postprefix_topics'];

		if (!empty($modSettings['PostPrefix_enable_filter']) && allowedTo('postprefix_set') && in_array($board, explode(',', $modSettings['PostPrefix_filter_boards'])))
		{
			// Get a list of prefixes
			$context['prefix']['post'] = Helper::Get(0, 10000, (!empty($modSettings['PostPrefix_select_order']) ? 'pp.id' : 'pp.name'), Manage::$table . ' AS pp', Manage::$columns, 'WHERE pp.status = 1 AND FIND_IN_SET('.$board.', pp.boards)'. (allowedTo('postprefix_manage') ? '' : ' AND (FIND_IN_SET(' . implode(', pp.groups) OR FIND_IN_SET('. $user_info['groups']) . ', pp.groups))'));

			// Load language
			loadLanguage('PostPrefix/');

			// Load our template as well
			loadTemplate('PostPrefix');

			// Load the sub-template
			$context['template_layers'][] = 'prefixfilter';
		}
	}

	public static function prefix_alias($columns)
	{
		$alias = [];
		foreach (Manage::$columns as $index => $column)
			$alias[$index] = $column . ' AS postprefix_' . str_replace('pp.', '', $column);

		return $alias;
	}

	public static function prefix_array($columns)
	{
		$array_of_the_pps = [];
		foreach (Manage::$columns as $index => $column)
			$array_of_the_pps[$index] = str_replace('pp.', '', $column);

		return $array_of_the_pps;
	}

	public static function pre_messageindex(&$sort_methods, &$sort_methods_table)
	{
		global $board_info, $context, $modSettings;

		// How many topics do we have in total?
		if (isset($_REQUEST['prefix']) && in_array($board_info['id'], explode(',', $modSettings['PostPrefix_filter_boards'])))
			$board_info['total_topics'] =  Helper::Count('topics', ['id_board', 'id_prefix', 'approved', 'id_member_started'], 'WHERE id_prefix = ' . $_REQUEST['prefix'] . ' AND id_board = ' . $board_info['id'] . (!$modSettings['postmod_active'] || $context['can_approve_posts'] ? '' : (' AND (approved = 1' . ($user_info['is_guest'] ? '' : ' OR id_member_started = ' . $user_info['id'])) . ')'));
	}

	public static function message_index(&$message_index_selects, &$message_index_tables, &$message_index_parameters, &$message_index_wheres, &$topic_ids, &$message_index_topic_wheres)
	{
		global $board_info, $scripturl, $context, $scripturl, $board, $txt, $modSettings;

		// Add the prefix
		$message_index_selects = array_merge($message_index_selects, array_merge(['t.id_prefix'], self::prefix_alias(Manage::$columns)));
		$message_index_tables = array_merge($message_index_tables, ['LEFT JOIN {db_prefix}postprefixes AS pp ON (t.id_prefix = pp.id)']);

		// Filtering prefixes?
		if (isset($_REQUEST['prefix']) && in_array($board, explode(',', $modSettings['PostPrefix_filter_boards'])))
		{
			$message_index_topic_wheres = array_merge($message_index_topic_wheres, ['t.id_prefix = {int:topic_prefix}']);
			$message_index_wheres = array_merge($message_index_wheres, ['t.id_prefix = {int:topic_prefix}']);
			$message_index_parameters = array_merge($message_index_parameters, ['topic_prefix' => $_REQUEST['prefix']]);
			
			// Add the prefix to the pageindex
			if (!empty($board_info['total_topics']) && isset($_REQUEST['prefix']))
			{
				// They didn't pick one, default to by last post descending.
				if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
				{
					$context['sort_by'] = 'last_post';
					$_REQUEST['sort'] = 'id_last_msg';
					$ascending = isset($_REQUEST['asc']);
					$_REQUEST['desc'] = 'desc';
				}

				// Another trick for our amusement
				$context['prefix_headers'] = [];
				foreach ($context['topics_headers'] as $key => $val)
					$context['prefix_headers'][$key] = '<a href="' . $scripturl . '?board=' . $context['current_board'] . '.' . $context['start'] . ';sort=' . $key . ($context['sort_by'] == $key && $context['sort_direction'] == 'up' ? ';desc' : '') . ';prefix=' . $_REQUEST['prefix'] . '">' . $txt[$key] . ($context['sort_by'] == $key ? '<span class="main_icons sort_' . $context['sort_direction'] . '"></span>' : '') . '</a>';
				$context['topics_headers'] = $context['prefix_headers'];

				$context['maxindex'] = isset($_REQUEST['all']) && !empty($modSettings['enableAllMessages']) ? $board_info['total_topics'] : $context['topics_per_page'];
				$context['page_index'] = constructPageIndex($scripturl . '?board=' . $board . '.%1$d;prefix=' . $_REQUEST['prefix'] . ';sort=' . $_REQUEST['sort'] . (isset($_REQUEST['desc']) ? ';desc' : ''), $_REQUEST['start'], $board_info['total_topics'], $context['maxindex'], true);
			}
		}
	}

	public static function display_topic(&$topic_selects, &$topic_tables, &$topic_parameters)
	{
		$topic_selects = array_merge($topic_selects, array_merge(['t.id_prefix'], self::prefix_alias(Manage::$columns)));
		$topic_tables = array_merge($topic_tables, ['LEFT JOIN {db_prefix}postprefixes AS pp ON (t.id_prefix = pp.id)']);
	}

	public static function display_message_list(&$messages, &$posters)
	{
		global $context, $topic, $scripturl;

		// This topic has a prefix?
		if (!empty($context['topicinfo']['id_prefix']) && !empty($context['topicinfo']['postprefix_status']))
		{
			// Sort it?
			$context['topicinfo']['prefix'] = self::prefix_array(Manage::$columns);
			foreach ($context['topicinfo']['prefix'] as $prefix)
				$context['topicinfo']['prefix'][$prefix] = $context['topicinfo']['postprefix_'.$prefix];

			// Add the prefix to the title
			$context['subject'] = self::format($context['topicinfo']['prefix']) . ' ' . $context['topicinfo']['subject'];

			// Add the prefix to the linktree
			$context['linktree'][count($context['linktree'])-1]['extra_before'] = self::format($context['topicinfo']['prefix'], ';text-shadow:none;padding-top:0;padding-bottom:0;');
		}
	}
}

