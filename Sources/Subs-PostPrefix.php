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

class PostPrefix
{
	public static $version = '3.0';

	public static function initialize()
	{
		self::setDefaults();
		self::defineHooks();
	}

		/**
	 * Shop::setDefaults()
	 *
	 * Sets almost every setting to a default value
	 * @return void
	 * @author Peter Spicer (Arantor)
	 */
	public static function setDefaults()
	{
		global $modSettings;

		$defaults = array(
			'PostPrefix_enable_filter' => 0,
			'PostPrefix_select_order' => 1,
			'PostPrefix_select_order_dir' => 0,
		);
		$modSettings = array_merge($defaults, $modSettings);
	}

	/**
	 * Shop::defineHooks()
	 *
	 * Load hooks quietly
	 * @return void
	 * @author Peter Spicer (Arantor)
	 */
	public static function defineHooks()
	{
		$hooks = array(
			'admin_areas' => 'PostPrefix::admin_areas',
			'load_permissions' => 'PostPrefix::permissions',
			'load_illegal_guest_permissions' => 'PostPrefix::illegal_guest_permissions',
			'load_board_info' => 'PostPrefix::load_board_info',
			'before_create_topic' => 'PostPrefix::before_create_topic',
			'create_post' => 'PostPrefix::create_post',
			'modify_post' => 'PostPrefix::modify_post',
			'post2_start' => 'PostPrefix::post2_start',
			'post_end' => 'PostPrefix::post_end',
			'post_errors' => 'PostPrefix::post_errors',
			'pre_messageindex' => 'PostPrefix::pre_messageindex',
			'message_index' => 'PostPrefix::message_index',
			'messageindex_buttons' => 'PostPrefix::filter',
		);
		foreach ($hooks as $point => $callable)
			add_integration_function('integrate_' . $point, $callable, false);
	}

	/**
	 * PostPrefix::permissions()
	 *
	 * Permissions for manage prefixes and a global permission for use the prefixes
	 * @param array $permissionGroups An array containing all possible permissions groups.
	 * @param array $permissionList An associative array with all the possible permissions.
	 * @return
	 */
	public static function permissions(&$permissionGroups, &$permissionList)
	{
		// We gotta load our language file.
		loadLanguage('PostPrefix');

		// Manage prefix
		$permission = array('manage_prefixes');
		foreach ($permission as $p)
			$permissionList['membergroup'][$p] = array(false,'maintenance');

		// Topic?
		$topic_permission = array('set_prefix');
		foreach ($topic_permission as $p)
			$permissionList['board'][$p] = array(false,'topic');
	}
	
	public static function illegal_guest_permissions()
	{
		global $context;

		// Guests do not play nicely with this mod
		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], array('manage_prefixes'));
	}

	/**
	 * PostPrefix::admin_areas()
	 *
	 * Add our new section and load the language and template
	 * @param array $admin_menu An array with all the admin settings buttons
	 * @global $scripturl, $context
	 * @return
	 */
	public static function admin_areas(&$admin_areas)
	{
		global $scripturl, $context, $txt;
		
		loadtemplate('PostPrefix');
		loadLanguage('PostPrefix');

		$insert = 'postsettings';
		$counter = 0;

		foreach ($admin_areas['layout']['areas'] as $area => $dummy)
			if (++$counter && $area == $insert )
				break;

		$admin_areas['layout']['areas'] = array_merge(
			array_slice($admin_areas['layout']['areas'], 0, $counter),
			array(
				'postprefix' => array(
					'label' => $txt['PostPrefix_main'],
					'icon' => 'reports',
					'file' => 'PostPrefixAdmin.php',
					'function' => 'PostPrefixAdmin::main#',
					'permission' => array('manage_prefixes'),
					'subsections' => array(
						'general' => array($txt['PostPrefix_tab_general']),
						'prefixes' => array($txt['PostPrefix_tab_prefixes']),
						'add' => array($txt['PostPrefix_tab_prefixes_add']),
						'require' => array($txt['PostPrefix_tab_require']),
						'permissions' => array($txt['PostPrefix_tab_permissions']),
						'settings' => array($txt['PostPrefix_tab_settings']),
					),
				),
			),
			array_slice($admin_areas['layout']['areas'], $counter)
		);

		// Post Prefix copyright :)
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'admin' && isset($_REQUEST['area']) && $_REQUEST['area'] == 'postprefix')
		{
			$context['template_layers'][] = 'postprefix';
			$context['copyright'] = self::copyright();
		}

	}

	public static function before_create_topic(&$msgOptions, &$topicOptions, &$posterOptions, &$topic_columns, &$topic_parameters)
	{
		$topic_columns = array_merge($topic_columns, array('id_prefix' => 'int'));
		$topic_parameters = array_merge($topic_parameters, array($topicOptions['id_prefix'] == null ? 0 : $topicOptions['id_prefix']));
	}

	public static function modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		global $smcFunc;

		$topicOptions['id_prefix'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null;

		// Lock and or sticky the post.
		if ($topicOptions['id_prefix'] !== null)
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}topics
				SET
					id_prefix = {raw:id_prefix}
				WHERE id_topic = {int:id_topic}',
				array(
					'id_prefix' => $topicOptions['id_prefix'] === null ? 'id_prefix' : (int) $topicOptions['id_prefix'],
					'id_topic' => $topicOptions['id'],
				)
			);
		}
	}

	public static function create_post(&$msgOptions, &$topicOptions, &$posterOptions, &$message_columns, &$message_parameters)
	{
		$topicOptions['id_prefix'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null;
	}

	public static function post_end()
	{
		global $txt, $context;

		if (!empty($context['prefix']['post']) && $context['is_first_post'])
		{
			$context['posting_fields']['topic_prefix'] = array(
				'label' => array(
					'text' => $txt['PostPrefix_select_prefix'], // required
					'class' => isset($context['post_error']['no_prefix']) ? 'error' : '',
				),
				'input' => array(
					'type' => 'select', // required
					'attributes' => array(
						'name' => 'id_prefix', // optional, defaults to posting field's key
					),
					'options' => array(
						'PostPrefix_select_prefix' => array(
							'label' => $txt['PostPrefix_select_prefix'],
							'options' => array(
								'none' => array(
									'label' => $txt['PostPrefix_prefix_none'],
									'value' => 0,
									'selected' => $context['id_prefix'] == 0 ? true : false,
								),
							),
						),
					),
				),
			);
			foreach ($context['prefix']['post'] as $prefix)
				$context['posting_fields']['topic_prefix']['input']['options']['PostPrefix_select_prefix']['options'][$prefix['id']] = array(
					'label' => $prefix['name'],
					'value' => $prefix['id'],
					'selected' => $prefix['id'] == $context['id_prefix'] ? true : false,
				);

			$context['posting_fields']['prefix_istopic'] = array(
				'label' => array(
					'html' => '',
					'text' => '',
				),
				'input' => array(
					'html' => '<input type="hidden" name="prefix_istopic" value="1">',
					'type' => '',
				),
			);
		}
	}

	public static function post2_start(&$post_errors)
	{
		global $board, $board_info, $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT b.id_board, b.require_prefix
			FROM {db_prefix}boards AS b
			WHERE b.id_board = {int:board}
			LIMIT 1',
			array(
				'board' => $board,
			)
		);
		$board_info['require_prefix'] = $smcFunc['db_fetch_assoc']($request)['require_prefix'];
		$smcFunc['db_free_result']($request);

		if ((!isset($_POST['id_prefix']) || $_POST['id_prefix'] === 0 || empty($_POST['id_prefix'])) && (!empty($board_info['require_prefix'])) && isset($_REQUEST['prefix_istopic']))
		{
			$post_errors[] = 'no_prefix';
		}
	}

	public static function post_errors(&$post_errors, &$minor_errors)
	{
		global $context, $board_info, $topic, $modSettings, $smcFunc;

		if (isset($_REQUEST['message']) || isset($_REQUEST['quickReply']) || !empty($context['post_error']))
			$context['id_prefix'] = isset($_REQUEST['id_prefix']) ? $_REQUEST['id_prefix'] : 0;
		elseif (isset($_REQUEST['msg']) && !empty($topic))
		{
			$_REQUEST['msg'] = (int) $_REQUEST['msg'];
			// Get the existing message. Editing.
			$request = $smcFunc['db_query']('', '
				SELECT
					m.id_msg, t.id_prefix
				FROM {db_prefix}messages AS m
					INNER JOIN {db_prefix}topics AS t ON (t.id_topic = {int:current_topic})
				WHERE m.id_msg = {int:id_msg}
					AND m.id_topic = {int:current_topic}',
				array(
					'current_topic' => $topic,
					'id_msg' => $_REQUEST['msg'],
				)
			);
			// The message they were trying to edit was most likely deleted.
			if ($smcFunc['db_num_rows']($request) == 0)
				fatal_lang_error('no_message', false);
			$row = $smcFunc['db_fetch_assoc']($request);
			$smcFunc['db_free_result']($request);
			// Finally the information that we really need
			$context['id_prefix'] = $row['id_prefix'];
		}
		else
			$context['id_prefix'] = 0;

		// Require prefix?
		$minor_errors = array_merge($minor_errors,array('no_prefix'));

		// Get the prefixes
		self::getPrefix($context['current_board']);
		$_SESSION['require_prefix'] = (!empty($board_info['require_prefix']) ? $board_info['require_prefix'] : 0);
	}

	/**
	 * PostPrefix::filter()
	 *
	 * Add the filter topics by prefix box on messageindex
	 * @global $topic, $board, $modSettings, $context
	 * @return
	 */
	public static function filter()
	{
		global $topic, $board, $modSettings, $context;

		if (empty($_REQUEST['action']) && !empty($modSettings['PostPrefix_enable_filter']))
		{
			// Topic is empty, and action is empty.... MessageIndex!
			if (!empty($board) && empty($topic))
			{
				// Get a list of prefixes
				self::getPrefix($context['current_board']);
				// Load our template as well
				loadTemplate('PostPrefix');
				// Load the sub-template
				$context['template_layers'][] = 'prefixfilter';
			}
		}
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
		global $board_info, $scripturl, $context, $scripturl, $board;

		// Make sure the starting place makes sense and construct the page index.
		if (isset($_REQUEST['sort']))
			$context['page_index'] = constructPageIndex($scripturl . '?board=' . $board . '.%1$d;sort=' . $_REQUEST['sort'] . (isset($_REQUEST['desc']) ? ';desc' : ''.(isset($_REQUEST['prefix']) ? ';prefix='.$_REQUEST['prefix'] : '')), $_REQUEST['start'], $board_info['total_topics'], $context['maxindex'], true);
		else
			$context['page_index'] = constructPageIndex($scripturl . '?board=' . $board . '.%1$d'.(isset($_REQUEST['prefix']) ? ';prefix='.$_REQUEST['prefix'] : ''), $_REQUEST['start'], $board_info['total_topics'], $context['maxindex'], true);
		$context['start'] = &$_REQUEST['start'];

		print_r($context['maxindex']);
		print_r(' ');
		print_r($_REQUEST['start']);

		// Select
		$message_index_selects += array('t.id_prefix');
		if (isset($_REQUEST['prefix']))
		{
			$message_index_topic_wheres += array('t.id_prefix = {int:topic_prefix}');
			//$message_index_wheres += array('t.id_prefix = {int:topic_prefix}');
			$message_index_parameters += array(
				'topic_prefix' => $_REQUEST['prefix'],
			);

		}

	}

	/**
	 * PostPrefix::formatPrefix()
	 *
	 * Styling the prefix.
	 * @param int $prefix the prefix id.
	 * @global $smcFunc, $topic
	 * @return
	 * @author Diego Andrés <diegoandres_cortes@outlook.com>
	 */
	public static function formatPrefix($prefix, $topicF = true)
	{
		global $smcFunc, $topic;

		$prefix = (int) $prefix;

		$request = $smcFunc['db_query']('', '
			SELECT p.id, p.name, p.color, p.bgcolor, p.icon, p.icon_url
			FROM {db_prefix}postprefixes AS p
			WHERE p.id = {int:id}
			LIMIT 1',
			array(
				'id' => $prefix,
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);

		$format = '';
		if (!empty($row))
		{
			if (empty($row['icon']))
			{
				$format .= '<span class="postprefix-all" id="postprefix-'. $row['id']. '" ';
				if (!empty($topic) || $row['bgcolor'] == 1 || !empty($row['color']))
				{
					$format .= 'style="';
					if ($row['bgcolor'] == 1 && !empty($row['color']))
						$format .= 'padding: 4px; border-radius: 2px; color: #f5f5f5; background-color: '. $row['color'];
					elseif (!empty($row['color']) && empty($row['bgcolor']))
						$format .= 'color: '. $row['color'];
					$format .= '"';
				}
				$format .= '>';
				$format .= $row['name'];
				$format .= '</span>';
			}
			else
			{
				$format = '<img class="postprefix-all" id="postprefix-'. $row['id']. '" style="vertical-align: middle;" src="'. $row['icon_url']. '" alt="'. $row['name']. '" title="'. $row['name']. '" />';
			}
		}

		return $format;
	}

	/**
	 * PostPrefix::getPrefix()
	 *
	 * It will return the list of prefixes.
	 * @param int $board The board id.
	 * @global $smcFunc, $context, $user_info, $memberContext, $user_settings, $modSettings
	 * @return
	 * @author Diego Andrés <diegoandres_cortes@outlook.com>
	 */
	public static function getPrefix($board, $all = false)
	{
		global $smcFunc, $context, $user_info, $memberContext, $user_settings, $modSettings;

		loadLanguage('PostPrefix');

		$board = (int) $board;

		$temp = loadMemberData($user_info['id'], false, 'profile');
		if (empty($temp))
		{
			$group = '';
			$postg = '';
		}
		else
		{
			loadMemberContext($user_info['id']);
			$group = (int) $memberContext[$user_info['id']]['group_id'];
			$postg = (int) $user_settings['id_post_group'];
		}

		// Order by thing
		$orderby = $modSettings['PostPrefix_select_order'];
		if ($orderby == 0)
			$order = 'name';
		elseif ($orderby == 1)
			$order = 'id';
		elseif ($orderby == 2)
			$order = 'added';
		// Direction
		$direction = $modSettings['PostPrefix_select_order_dir'];
		if ($direction == 0)
			$dir = 'DESC';
		else
			$dir = 'ASC';

		$context['prefix']['post'] = array();
		if (allowedTo('set_prefix'))
		{
			$request = $smcFunc['db_query']('', '
				SELECT p.id, p.status, p.name, p.added, p.boards, p.member_groups, p.deny_member_groups
				FROM {db_prefix}postprefixes AS p
				WHERE p.status = 1'. ($user_info['is_admin'] || allowedTo('manage_prefixes') ? '' : ('
					AND (FIND_IN_SET({int:id_group}, p.member_groups) OR FIND_IN_SET({int:post_group}, p.member_groups))' . (!empty($modSettings['permission_enable_deny']) ? ('
					AND (NOT FIND_IN_SET({int:id_group}, p.deny_member_groups) AND NOT FIND_IN_SET({int:post_group}, p.deny_member_groups))') : '') . '')) .
					($all == true ? '' : '
					AND FIND_IN_SET({int:board}, p.boards)
				ORDER by p.{raw:order} {raw:dir}'),
				array(
					'id_group' => $group,
					'post_group' => $postg,
					'board' => $board,
					'order' => $order,
					'dir' => $dir,
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$context['prefix']['post'][] = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'boards' => explode(',', $row['boards']),
					'groups' => explode(',', $row['member_groups']),
					'deny_groups' => explode(',', $row['deny_member_groups']),
				);
			$smcFunc['db_free_result']($request);
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

	public static function copyright()
	{
		$copy = '<div class="centertext"><a href="https://smftricks.com" target="_blank">Powered by SMF Post Prefix &copy; '. date('Y') . ' SMF Tricks</a></div>';
		return $copy;
	}

	/**
	 * @return array
	 */
	public static function credits()
	{
		// Dear contributor, please feel free to add yourself here.
		$credits = array(
			'dev' => array(
				'name' => 'Developer(s)',
				'users' => array(
					'diego' => array(
						'name' => 'Diego Andr&eacute;s',
						'site' => 'https://smftricks.com',
					),
				),
			),
			'scripts' => array(
				'name' => 'Third Party Scripts',
				'users' => array(
					'jquery' => array(
						'name' => 'jQuery',
						'site' => 'http://jquery.com',
					),
					'colpick' => array(
						'name' => 'ColPick',
						'site' => 'http://colpick.com/plugin',
					),
				),
			),
		);

		return $credits;
	}
}

