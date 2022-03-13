<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix\Integration;

use PostPrefix\Helper\Database;
use PostPrefix\PostPrefix;

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
	public static function before_create_topic(array &$msgOptions, array &$topicOptions, array &$posterOptions, array &$topic_columns, array &$topic_parameters) : void
	{
		global $context, $board;

		// Is the user allowed to post with a prefix?
		if (empty($context['user_prefixes']['post']) || !allowedTo('postprefix_set'))
			return;

		// Set the prefix
		$topicOptions['id_prefix'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : null;

		// Is there an actual prefix there?
		if (!isset($topicOptions['id_prefix']) || !isset($context['user_prefixes']['post'][$topicOptions['id_prefix']]['boards']) || !in_array($board, $context['user_prefixes']['post'][$topicOptions['id_prefix']]['boards']))
			return;

		// Add the column
		$topic_columns['id_prefix'] = 'int';

		// Add the parameter
		$topic_parameters[] = $topicOptions['id_prefix'] === null ? 0 : $topicOptions['id_prefix'];
	}

	/**
	 * Posting::modify_post()
	 * 
	 * @return void
	 */
	public static function modify_post() : void
	{
		global $topic, $context, $board;

		// Is there a prefix and a topic?
		if (!isset($_POST['id_prefix']) || !isset($topic) || empty($topic) || !allowedTo('postprefix_set') || empty($context['user_prefixes']['post']) || !isset($context['user_prefixes']['post'][$_POST['id_prefix']]['boards']) || !in_array($board, $context['user_prefixes']['post'][$_POST['id_prefix']]['boards']))
			return;

		// Always update the prefix, we don't know the current one
		// SADGE
		Database::Update('topics', [
				'id_prefix' => empty($_POST['id_prefix']) ? 0 : (int) $_POST['id_prefix'],
				'id_topic' => (int) $topic
			],
			'id_prefix = {int:id_prefix}',
			'WHERE id_topic = {int:id_topic}'
		);
	}

	/**
	 * Posting::post2_start()
	 * 
	 * @param array $post_errors The post errors
	 * @return void
	 */
	public static function post2_start(array &$post_errors) : void
	{
		global $board, $modSettings, $context;

		// Get the real board, user might be posting from the post action only
		$real_board = !empty($board) ? (int) $board : (isset($_POST['board']) ? (int) $_POST['board'] : 0);

		// With a prefix set, there's nothing to do here, or if this board isn't in the settings for requiring prefixes
		if ((isset($_POST['id_prefix']) && !empty($_POST['id_prefix'])) || !in_array($real_board, explode(',', $modSettings['PostPrefix_prefix_boards_require'])) || !isset($_REQUEST['prefix_istopic']) || !allowedTo('postprefix_set'))
			return;

		// Verify that the user can't set a prefix
		foreach ($context['user_prefixes']['post'] as $prefix)
		{
			if (in_array($board, $prefix['boards']))
			{
				// This user can set a prefix on this board and will be getting punishment
				$post_errors[] = 'no_prefix';
				break;
			}
		}
	}

	/**
	 * Posting::post_errors()
	 * 
	 * @param array $post_errors The post errors
	 * @return void
	 */
	public static function post_errors(array &$post_errors, array &$minor_errors) : void
	{
		global $context, $topic, $board;

		// Can the user set a prefix?
		if (!allowedTo('postprefix_set'))
			return;

		// Language file
		loadLanguage('PostPrefix/');

		// Require prefix is just a minor error...
		$minor_errors[] = 'no_prefix';

		// Set the default prefix
		$context['post_prefix_id'] = isset($_POST['id_prefix']) ? (int) $_POST['id_prefix'] : 0;

		// When editing a topic, get the current prefix
		if (!empty($topic) && empty($context['post_prefix_id']))
		{
			// Search this topic for the current prefix
			$topic_prefix = Database::Get(0, '', 'id_prefix', 'topics',
				['id_prefix'],
				'WHERE id_topic = {int:id_topic}', true, '',
				[
					'id_topic' => (int) $topic
				]
			);

			// Set the prefix, if we got one
			if (!empty($topic_prefix) && isset($context['user_prefixes']['post'][$topic_prefix['id_prefix']]['boards']) && in_array($board, $context['user_prefixes']['post'][$topic_prefix['id_prefix']]['boards']))
				$context['post_prefix_id'] = $topic_prefix['id_prefix'];
		}
	}

	/**
	 * Posting::list_post_prefixes()
	 * 
	 * Obtain the list of prefixes the user can access, even if they are disabled for the board
	 * 
	 * @return void
	 */

	/**
	 * Posting::post_end()
	 * 
	 * @return void
	 */
	public static function post_end() : void
	{
		global $txt, $context, $board, $topic, $modSettings;

		// Only first posts can have prefixes
		if (empty($context['user_prefixes']['post']) || !$context['is_first_post'] || !allowedTo('postprefix_set'))
			return;

		// Add some sorcery
		if ((empty($board) || !isset($_REQUEST['board'])) && empty($topic))
		{
			// Get the first board
			foreach ($context['posting_fields']['board']['input']['options'] as $category)
			{
				foreach ($category['options'] as $board => $values)
				{
					$first_board = $values['value'];
					break;
				}
				break;
			}

			// Set the prefixes depending on the selected board.
			addJavaScriptVar('post_first_board', isset($first_board) ? $first_board : 0);
			addJavaScriptVar('prefixes_radio_select', !empty($modSettings['PostPrefix_post_selecttype']) ? 'true' : 'false');
			loadJavaScriptFile('postprefix.js', ['defer' => true, 'default_theme' => true], 'PostPrefix');
		}
		// Remove those that don't have this board, just for convenience sake.
		else
		{
			foreach ($context['user_prefixes']['post'] as $key => $prefix)
			{
				if (!in_array($board, $prefix['boards']))
					unset($context['user_prefixes']['post'][$key]);
			}
		}

		// If there are no prefixes, don't bother
		if (empty($context['user_prefixes']['post']) && !empty($board))
			return;

		// Add the prefix input
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
								'id' => 'prefix_0',
								'selected' => $context['post_prefix_id'] == 0 ? true : false,
							],
						],
					],
				],
			],
		];

		// Add the list of prefixes to the options
		if (!empty($context['user_prefixes']['post']))
		{
			foreach ($context['user_prefixes']['post'] as $prefix_id => $prefix)
			{
				$context['posting_fields']['topic_prefix']['input']['options']['PostPrefix_select_prefix']['options'][$prefix_id] = [
					'label' => !empty($modSettings['PostPrefix_post_selecttype']) ? PostPrefix::format($prefix) : $prefix['name'],
					'value' => $prefix_id,
					'id' => 'prefix_' . $prefix_id,
					'selected' => $prefix_id == $context['post_prefix_id'] ? true : false,
					'data-boards' => '' . implode(',', $prefix['boards']) . '',
				];
			}
		}

		// Do we want to use radio instead?
		if (!empty($modSettings['PostPrefix_post_selecttype']))
		{
			$context['posting_fields']['topic_prefix']['input']['type'] = 'radio_select';
			$context['posting_fields']['topic_prefix']['input']['options'] = $context['posting_fields']['topic_prefix']['input']['options']['PostPrefix_select_prefix']['options'];
		}

		// Remove "No Prefix" if prefixes are required on this board
		if (!empty($board) && !empty($modSettings['PostPrefix_prefix_boards_require']) && in_array($board, explode(',', $modSettings['PostPrefix_prefix_boards_require'])))
		{
			unset($context['posting_fields']['topic_prefix']['input']['options']['PostPrefix_select_prefix']['options']['none']);
			unset($context['posting_fields']['topic_prefix']['input']['options']['none']);
		}

		// Additional hidden input, so we now that this is a topic
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