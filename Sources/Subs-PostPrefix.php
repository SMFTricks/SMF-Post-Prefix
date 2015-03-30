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
	public static $name = 'PostPrefix';
	public static $version = '1.0';

	public static function load_theme()
	{
		// Color picker
		if ((isset($_REQUEST['action']) && ($_REQUEST['action'] == 'admin')) && (isset($_REQUEST['area']) && ($_REQUEST['area'] == 'postprefix')) && (isset($_REQUEST['sa']) && (($_REQUEST['sa'] == 'add') || ($_REQUEST['sa'] == 'edit'))))
		{
			loadCSSFile('colpick.css', array('default_theme' => true));
			loadJavascriptFile('colpick.js', array('default_theme' => true));
		}
	}

	public static function permissions(&$permissionGroups, &$permissionList)
	{
		// We gotta load our language file.
		loadLanguage(self::$name);

		// Manage prefix
		$permission = array('manage_prefixes');
		foreach ($permission as $p)
			$permissionList['membergroup'][$p] = array(false,'maintenance');

		// Topic?
		$permission2 = array('set_prefix');
		foreach ($permission2 as $p)
			$permissionList['board'][$p] = array(false,'topic');
	}
	
	public static function illegal_guest_permissions()
	{
		global $context;

		// Guests do not play nicely with this mod
		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'],array('manage_prefixes','set_prefix'));
	}

	public static function admin_areas(&$admin_areas)
	{
		global $scripturl;
		
		loadtemplate(self::$name);
		loadLanguage(self::$name);

		$insert = 'postsettings';
		$counter = 0;

		foreach ($admin_areas['layout']['areas'] as $area => $dummy)
			if (++$counter && $area == $insert )
				break;

		$admin_areas['layout']['areas'] = array_merge(
			array_slice($admin_areas['layout']['areas'], 0, $counter),
			array(
				'postprefix' => array(
					'label' => self::text('main'),
					'icon' => 'reports',
					'file' => 'PostPrefixAdmin.php',
					'function' => 'PostPrefixAdmin::main#',
					'permission' => array('manage_prefixes'),
					'subsections' => array(
						'general' => array(self::text('tab_general')),
						'prefixes' => array(self::text('tab_prefixes')),
						'add' => array(self::text('tab_prefixes_add')),
						'require' => array(self::text('tab_require')),
						'permissions' => array(self::text('tab_permissions')),
					),
				),
			),
			array_slice($admin_areas['layout']['areas'], $counter)
		);

	}

	public static function create_post($msgOptions, $topicOptions, $posterOptions, $message_columns, $message_parameters)
	{
		$topicOptions['id_prefix'] = isset($topicOptions['id_prefix']) ? $topicOptions['id_prefix'] : 0;
	}

	public static function modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		$topicOptions['id_prefix'] = isset($topicOptions['id_prefix']) ? $topicOptions['id_prefix'] : null;
	}

	public static function modify_topic(&$topics_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions)
	{
		$update_parameters = array_merge($update_parameters,array('id_prefix' => $topicOptions['id_prefix']));
	}

	public static function before_create_topic(&$msgOptions, &$topicOptions, &$posterOptions, &$topic_columns, &$topic_parameters)
	{
		$topic_columns = array_merge($topic_columns,array('id_prefix' => 'int'));
		$topic_parameters = array_merge($topic_parameters,array($topicOptions['id_prefix'] == null ? 0 : $topicOptions['id_prefix']));
	}

	public static function post_errors(&$post_errors, &$minor_errors)
	{
		global $context, $topic, $modSettings;

		if (isset($_REQUEST['message']) || isset($_REQUEST['quickReply']) || !empty($context['post_error']))
			$context['id_prefix'] = isset($_REQUEST['id_prefix']) ? $_REQUEST['id_prefix'] : 0;
		elseif (isset($_REQUEST['msg']) && !empty($topic))
			$context['id_prefix'] = $context['id_prefix'];
		else
			$context['id_prefix'] = 0;

		// Require prefix?
		$minor_errors = array_merge($minor_errors,array('no_prefix'));

		// Get the prefixes
		self::getPrefix($context['current_board']);
	}

	public static function formatPrefix($prefix)
	{
		global $smcFunc, $topic;

		$prefix = (int) $prefix;

		$request = $smcFunc['db_query']('', '
			SELECT p.id, p.name, p.color, p.bgcolor
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
			$format .= '<span ';

			if (!empty($topic) || $row['bgcolor'] == 1 || !empty($row['color']))
			{
				$format .= 'style="';

				if (!empty($topic))
					$format .= 'line-height: 35px;';
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

		return $format;
	}

	public static function getPrefix($board)
	{
		global $smcFunc, $context, $user_info, $memberContext, $user_settings, $modSettings;

		loadLanguage(PostPrefix::$name);

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

		$context['prefix']['post'] = array();
		if (allowedTo('set_prefix'))
		{
			$request = $smcFunc['db_query']('', '
				SELECT p.id, p.status, p.name, p.boards, p.member_groups, p.deny_member_groups
				FROM {db_prefix}postprefixes AS p
				WHERE p.status = 1'. ($user_info['is_admin'] || allowedTo('manage_prefixes') ? '' : ('
					AND (FIND_IN_SET({int:id_group}, p.member_groups) OR FIND_IN_SET({int:post_group}, p.member_groups))' . (!empty($modSettings['permission_enable_deny']) ? ('
					AND (NOT FIND_IN_SET({int:id_group}, p.deny_member_groups) AND NOT FIND_IN_SET({int:post_group}, p.deny_member_groups))') : '') . '')) . '
					AND FIND_IN_SET({int:board}, p.boards)',
				array(
					'id_group' => $group,
					'post_group' => $postg,
					'board' => $board,
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

	public static function integrate_credits()
	{
		global $context;
		
		$context['copyrights']['mods']['postprefix'] = '<a href="http://smftricks.com" title="SMF Themes & Mods">SMF Post Prefix &copy Diego Andr&eacute;s & SMF Tricks</a>';
	}

	/**
	 * PostPrefix::text()
	 *
	 * Gets a string key, and returns the associated text string.
	 * @param string $var The text string key.
	 * @global $txt
	 * @return string|boolean
	 * @author Jessica González <suki@missallsunday.com>
	 */
	public static function text($var)
	{
		global $txt;

		if (empty($var))
			return false;

		// Load the mod's language file.
		loadLanguage(self::$name);

		if (!empty($txt[self::$name. '_' .$var]))
			return $txt[self::$name. '_' .$var];

		else
			return false;
	}

	/**
	 * @return array
	 */
	public function credits()
	{
		// Dear contributor, please feel free to add yourself here.
		$credits = array(
			'dev' => array(
				'name' => 'Developer(s)',
				'users' => array(
					'diego' => array(
						'name' => 'Diego Andr&eacute;s',
						'site' => 'http://smftricks.com',
					),
				),
			),
			'scripts' => array(
				'name' => 'Third Party Scripts',
				'users' => array(
					'feed' => array(
						'name' => 'ColPick',
						'site' => 'http://colpick.com/plugin',
					),
				),
			),
		);

		// Oh well, one can dream...
		call_integration_hook('integrate_postprefix_credits', array(&$credits));

		return $credits;
	}
}

