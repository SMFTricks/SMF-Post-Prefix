<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

namespace PostPrefix\Integration;

use PostPrefix\Helper\Database;

if (!defined('SMF'))
	die('No direct access...');

class Posting
{
	/**
	 * Posting::before_create_topic()
	 * 
	 * @param array $topicOptions The topic options
	 * @param array $topic_columns The topic columns
	 * @param array $topic_parameters The topic parameters
	 * @return void
	 */
	public static function before_create_topic(&$msgOptions, &$topicOptions, &$posterOptions, &$topic_columns, &$topic_parameters)
	{		
		$topic_columns['id_prefix'] = 'int';
		$topic_parameters[] = $topicOptions['id_prefix'] === null ? 0 : $topicOptions['id_prefix'];
	}

	/**
	 * Posting::create_post()
	 * 
	 * @param array $topicOptions The topic options
	 * @return void
	 */
	public static function create_post(&$msgOptions, &$topicOptions)
	{		
		$topicOptions['id_prefix'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null;
	}

	/**
	 * Posting::modify_post()
	 * 
	 * @param array $topicOptions The topic options
	 * @return void
	 */
	public static function modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions)
	{
		$topicOptions['id_prefix'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null;

		// It should have a prefix
		if ($topicOptions['id_prefix'] !== null)
		{
			Database::Update('topics',
				[
					'id_prefix' => empty($topicOptions['id_prefix']) ? 0 : (int) $topicOptions['id_prefix'],
					'id_topic' => $topicOptions['id']
				],
				'id_prefix = {int:id_prefix}',
				'WHERE id_topic = {int:id_topic}'
			);
		}
	}

	/**
	 * Posting::post2_start()
	 * 
	 * @param array $post_errors The post errors
	 * @return void
	 */
	public static function post2_start(&$post_errors)
	{
		global $board, $modSettings;

		// Check if the topic needs prefix
		if ((!isset($_POST['id_prefix']) || empty($_POST['id_prefix'])) && (in_array($board, explode(',', $modSettings['PostPrefix_prefix_boards_require']))) && isset($_REQUEST['prefix_istopic']))
			$post_errors[] = 'no_prefix';
	}

	/**
	 * Posting::post_errors()
	 * 
	 * @param array $post_errors The post errors
	 * @return void
	 */
	public static function post_errors(&$post_errors, &$minor_errors)
	{
		global $context, $topic, $board, $modSettings, $user_info;

		if (isset($_REQUEST['message']) || isset($_REQUEST['quickReply']) || !empty($context['post_error']))
			$context['prefix_data']['id_prefix'] = isset($_REQUEST['id_prefix']) ? $_REQUEST['id_prefix'] : 0;
		elseif (isset($_REQUEST['msg']) && !empty($topic))
		{
			$_REQUEST['msg'] = (int) $_REQUEST['msg'];

			// Get the existing message. Editing.
			$context['prefix_data'] = Database::Get('', '', '',
				'messages AS m',
				['m.id_msg', 't.id_prefix'],
				'WHERE m.id_msg = ' . $_REQUEST['msg'], true,
				'INNER JOIN {db_prefix}topics AS t ON (t.id_topic = '. $topic . ')'
			);

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
			$context['prefix']['post']  = Database::Nested('pp.id', 'postprefixes AS pp',
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
		}
	}

	/**
	 * Posting::post_end()
	 * 
	 * @return void
	 */
	public static function post_end()
	{
		global $txt, $context, $board, $topic;

		// // Only first posts can have prefixes
		if (empty($context['prefix']['post']) || !$context['is_first_post'])
			return;

		// Add some sorcery
		if ((empty($board) || !isset($_REQUEST['board'])) && empty($topic))
		{
			// Get the first board
			foreach ($context['posting_fields']['board']['input']['options'] as $category)
			{
				// print_r($category);
				foreach ($category['options'] as $board => $values)
				{
					$first_board = $values['value'];
					break;
				}
				break;
			}

			// Set the prefixes depending on the selected board.
			addJavaScriptVar('post_first_board', isset($first_board) ? $first_board : 0);
			loadJavaScriptFile('postprefix.js', ['defer' => true, 'default_theme' => true], 'PostPrefix');
		}
		// Remove those that don't have this board :)
		else
		{
			foreach ($context['prefix']['post'] as $key => $prefix)
			{
				if (!in_array($board, $prefix['boards']))
					unset($context['prefix']['post'][$key]);
			}
		}

		$context['posting_fields']['topic_prefix'] = [
			'label' => [
				'text' => $txt['PostPrefix_select_prefix'],
				'class' => isset($context['post_error']['no_prefix']) ? 'error' : '',
			],
			'input' => [
				'type' => 'select',
				'attributes' => [
					'name' => 'id_prefix',
					'id' => 'select_prefixes',
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
		foreach ($context['prefix']['post'] as $prefix_id => $prefix)
			$context['posting_fields']['topic_prefix']['input']['options']['PostPrefix_select_prefix']['options'][$prefix_id] = [
				'label' => $prefix['name'],
				'value' => $prefix_id,
				'id' => 'prefix_' . $prefix_id,
				'selected' => $prefix_id == $context['prefix_data']['id_prefix'] ? true : false,
				'data-boards' => '' . implode(',', $prefix['boards']) . '',
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