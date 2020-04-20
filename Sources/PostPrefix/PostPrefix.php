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

	/**
	 * PostPrefix::setDefaults()
	 *
	 * Sets almost every setting to a default value
	 * @return void
	 */
	public static function setDefaults()
	{
		global $modSettings;

		$defaults = [
			'PostPrefix_enable_filter' => 0,
			'PostPrefix_select_order' => 1,
			'PostPrefix_select_order_dir' => 0,
		];
		$modSettings = array_merge($defaults, $modSettings);
	}

	/**
	 * PostPrefix::defineHooks()
	 *
	 * Load hooks quietly
	 * @return void
	 * @author Peter Spicer (Arantor)
	 */
	public static function defineHooks()
	{
		$hooks = [
			'autoload',
			'actions',
			'message_index',
			//'load_board_info',
			//'pre_messageindex',
			'messageindex_buttons',
			'display_topic',
			'display_message_list',
		];
		foreach ($hooks as $point )
			add_integration_function('integrate_' . $point, __CLASS__ . '::'.$point, false);
	}

	/**
	 * PostPrefix::autoload()
	 *
	 * @param array $classMap
	 * @return void
	 */
	public static function autoload(&$classMap)
	{
		$classMap['PostPrefix\\'] = 'PostPrefix/';
	}

	/**
	 * PostPrefix::actions()
	 *
	 * Insert the actions needed by this mod
	 * @param array $actions An array containing all possible SMF actions. This includes loading different hooks for certain areas.
	 * @return void
	 * @author Peter Spicer (Arantor)
	 */
	public static function actions(&$actions)
	{
		// Add some hooks by action
		switch ($_REQUEST['action']) {
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
			$context['prefix']['post'] = Helper::Get(0, 10000, (!empty($modSettings['PostPrefix_select_order']) ? 'pp.id' : 'pp.name'), Manage::$table . ' AS pp', Manage::$columns, 'WHERE pp.status = 1 AND FIND_IN_SET('.$board.', pp.boards) AND (FIND_IN_SET(' . implode(', pp.groups) OR FIND_IN_SET(', $user_info['groups']) . ', pp.groups))');
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
			$format = '<span class="postprefix-all" id="postprefix-'. $prefix['id']. '"';
			if (!empty($prefix['bgcolor']) || !empty($prefix['color']))
			{
				if ($prefix['bgcolor'] == 1 && !empty($prefix['color']))
					$format .= ' style="display:inline-block;padding:2px 5px;border-radius: 3px;color: #f5f5f5;background-color:'. $prefix['color'] . $styles . '">';
				elseif (!empty($prefix['color']) && empty($prefix['bgcolor']))
					$format .= ' style="color:'. $prefix['color'] . $styles . ';">';
			}
			else
				$format .= '>';
			$format .= $prefix['name']. '</span> ';
		}
		else
			$format = '<img class="postprefix-all" id="postprefix-'. $prefix['id']. '" style="vertical-align: middle;" src="'. $prefix['icon_url']. '" alt="'. $prefix['name']. '" title="'. $prefix['name']. '" /> ';

		return $format;
	}

	public static function messageindex_buttons()
	{
		global $modSettings, $context;

		// Rewrite the biach
		$context['postprefix_topics'] = [];
		foreach ($context['topics'] as $topic => $value)
		{
			$context['postprefix_topics'][$topic] = $context['topics'][$topic];

			// Topic has a prefix?
			if (!empty($context['topics'][$topic]['id_prefix']))
			{
				$context['postprefix_topics'][$topic]['prefix'] = [
					'id' => $context['postprefix_topics'][$topic]['postprefix_id'],
					'name' => $context['postprefix_topics'][$topic]['postprefix_name'],
					'color' => $context['postprefix_topics'][$topic]['postprefix_color'],
					'bgcolor' => $context['postprefix_topics'][$topic]['postprefix_bgcolor'],
					'icon_url' => $context['postprefix_topics'][$topic]['postprefix_icon_url'],
				];
				$context['postprefix_topics'][$topic]['first_post']['link'] = self::format($context['postprefix_topics'][$topic]['prefix']) . $context['topics'][$topic]['first_post']['link'];
			}
		}
		// Yo wassup :P
		$context['topics'] = $context['postprefix_topics'];

		/*if (empty($_REQUEST['action']) && !empty($modSettings['PostPrefix_enable_filter']))
		{
			// Get a list of prefixes
			self::getPrefix($context['current_board']);
			// Load our template as well
			loadTemplate('PostPrefix');
			// Load the sub-template
			$context['template_layers'][] = 'prefixfilter';
		}*/
	}

	public static function prefix_alias($columns)
	{
		$alias = [];
		foreach (Manage::$columns as $index => $column)
			$alias[$index] = $column . ' AS postprefix_' . str_replace('pp.', '', $column);

		return $alias;
	}

	public static function pre_messageindex(&$sort_methods, &$sort_methods_table)
	{
		global $board_info, $context;

		// How many topics do we have in total?
		if (!isset($_REQUEST['prefix']))
			$board_info['total_topics'] = allowedTo('approve_posts') ? $board_info['num_topics'] + $board_info['unapproved_topics'] : $board_info['num_topics'] + $board_info['unapproved_user_topics'];
		else
			$board_info['total_topics'] = allowedTo('approve_posts') ? PostPrefix::countTopics($board_info['id'], $_REQUEST['prefix']) + $board_info['unapproved_topics'] : PostPrefix::countTopics($board_info['id'], $_REQUEST['prefix']) + $board_info['unapproved_user_topics'];
	}

	public static function message_index(&$message_index_selects, &$message_index_tables, &$message_index_parameters, &$message_index_wheres, &$topic_ids, &$message_index_topic_wheres)
	{
		global $board_info, $scripturl, $context, $scripturl;

		$message_index_selects = array_merge($message_index_selects, array_merge(['t.id_prefix'], self::prefix_alias(Manage::$columns)));
		$message_index_tables = array_merge($message_index_tables, ['LEFT JOIN {db_prefix}postprefixes AS pp ON (t.id_prefix = pp.id)']);
		
		/*if (isset($_REQUEST['prefix']))
		{
			$message_index_topic_wheres += array('t.id_prefix = {int:topic_prefix}');
			//$message_index_wheres += array('t.id_prefix = {int:topic_prefix}');
			$message_index_parameters += array(
				'topic_prefix' => $_REQUEST['prefix'],
			);

		}*/
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
		if (!empty($context['topicinfo']['id_prefix']))
		{
			// Sort it?
			$context['topicinfo']['prefix'] = [
				'id' => $context['topicinfo']['postprefix_id'],
				'name' => $context['topicinfo']['postprefix_name'],
				'color' => $context['topicinfo']['postprefix_color'],
				'bgcolor' => $context['topicinfo']['postprefix_bgcolor'],
				'icon_url' => $context['topicinfo']['postprefix_icon_url'],
			];

			// Add the prefix to the title
			$context['subject'] = self::format($context['topicinfo']['prefix']) . $context['topicinfo']['subject'];

			// Add the prefix to the linktree
			$context['linktree'][count($context['linktree'])-1]['extra_before'] = self::format($context['topicinfo']['prefix'], ';text-shadow:none;padding-top:0;padding-bottom:0;');
		}
	}

	/**
	 * PostPrefix::countTopics()
	 *
	 * It will return the number of topics in X board
	 * @param $board, $prefix
	 * @global $smcFunc, $context, $user_info, $memberContext, $user_settings, $modSettings
	 * @return
	 * @author Diego Andrés <diegoandres_cortes@outlook.com>
	 */
	public static function countTopics($board, $prefix)
	{
		global $smcFunc;

		if (isset($_REQUEST['prefix']))
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_board, id_prefix, approved
				FROM {db_prefix}topics
				WHERE id_prefix = {int:topic_prefix} 
					AND id_board = {int:board} 
					AND approved = 1',
				array(
					'topic_prefix' => $prefix,
					'board' => $board,
				)
			);
			return $smcFunc['db_num_rows']($request);
		}
	}
}

