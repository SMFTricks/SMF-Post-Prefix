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

class Posting
{
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
			$context['prefix']['post'] = Helper::Get(0, 10000, (!empty($modSettings['PostPrefix_select_order']) ? 'pp.id' : 'pp.name'), 'postprefixes AS pp', Helper::$columns, 'WHERE pp.status = 1 AND FIND_IN_SET('.$board.', pp.boards)'. (allowedTo('postprefix_manage') ? '' : ' AND (FIND_IN_SET(' . implode(', pp.groups) OR FIND_IN_SET('. $user_info['groups']) . ', pp.groups))'));
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
}

