<?php

/**
 * @package SMF Post Prefix
 * @version 4.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix\Integration;

use PostPrefix\PostPrefix;
use PostPrefix\Helper\Database;

if (!defined('SMF'))
	die('No direct access...');

class MessageIndex
{
	/**
	 * @var array The total number of topics per prefix
	 */
	private $_total_topics = [];

	/**
	 * @var array The sort methods
	 */
	private $_sort_methods = [];

	/**
	 * @var array Last messages id's from the boardindex and the recent posts
	 */
	private  $_last_messages = [];

	/**
	 * @var array Topics for the messages
	 */
	private $_topics = [];

	/**
	 * @var array The first messages and their prefixes (if any)
	 */
	private $_first_messages = [];

	/**
	 * MessageIndex::topic_list()
	 * 
	 * Insert the topic prefixes in the topic list query
	 * 
	 * @param array $message_index_selects The message index selects
	 * @param array $message_index_tables The message index tables
	 * @param array $message_index_parameters The message index parameters
	 * @param array $message_index_wheres The message index where's
	 * @param array $message_index_topic_wheres The message index topic where's
	 * @return void
	 */
	public function topics_list(array &$message_index_selects, array &$message_index_tables, array &$message_index_parameters, array &$message_index_wheres, array &$topic_ids, array &$message_index_topic_wheres) : void
	{
		// Prefix ID
		$message_index_selects[] = 't.id_prefix';

		// Prefix Columns
		foreach (Database::$_prefix_columns as $column)
			$message_index_selects[] = $column;

		// Prefix Table
		$message_index_tables[] = 'LEFT JOIN {db_prefix}postprefixes AS pp ON (t.id_prefix = pp.id)';

		// Filtering Prefixes?
		$this->buildFilter($message_index_parameters, $message_index_wheres, $message_index_topic_wheres);
	}

	/**
	 * MessageIndex::buildFilter()
	 * 
	 * Build the prefix filter
	 * 
	 * @param array $message_index_parameters The message index parameters
	 * @param array $message_index_wheres The message index where's
	 * @param array $message_index_topic_wheres The message index topic where's
	 * @return void
	 */
	private function buildFilter(array &$message_index_parameters, array &$message_index_wheres, array &$message_index_topic_wheres) : void
	{
		global $board, $modSettings, $context, $scripturl, $txt;

		// // Is the board in the filter?
		if (!in_array($board, explode(',', $modSettings['PostPrefix_filter_boards'])) || !isset($_REQUEST['prefix']) || empty($this->_total_topics[$_REQUEST['prefix']]))
			return;

		// Parameters
		$message_index_parameters['prefix'] = (int) $_REQUEST['prefix'];

		// Prefix ID for topics
		$message_index_wheres[] = 't.id_prefix = {int:prefix}';

		// Topic query
		$message_index_topic_wheres[] = 't.id_prefix = {int:prefix}';

		// Max
		$context['maxindex'] = isset($_REQUEST['all']) && !empty($modSettings['enableAllMessages']) ? $this->_total_topics[$_REQUEST['prefix']] : $context['topics_per_page'];

		// They didn't pick one, default to by last post descending.
		if (!isset($_GET['sort']) || !isset($this->_sort_methods[$_GET['sort']]))
		{
			$context['sort_by'] = 'last_post';
			$_REQUEST['sort'] = 'id_last_msg';

		}
		// Otherwise default to ascending.
		else
		{
			$context['sort_by'] = $_GET['sort'];
			$_REQUEST['sort'] = $this->_sort_methods[$_GET['sort']];
		}

		// Insert it in the topic headers
		foreach ($this->_sort_methods as $key => $val)
		{
			$context['topics_headers'][$key] = '
				<a href="' . $scripturl . '?board=' . $context['current_board'] . '.' . $context['start'] . ';sort=' . $key . ($context['sort_by'] == $key && $context['sort_direction'] == 'up' ? ';desc' : '') . ';prefix=' . $_REQUEST['prefix'] . '">
					' . $txt[$key] . ($context['sort_by'] == $key ? '<span class="main_icons sort_' . $context['sort_direction'] . '"></span>' : '') . '
				</a>';
		}

		// Page Index
		$context['page_index'] = constructPageIndex($scripturl . '?board=' . $board . '.%1$d;prefix=' . $_REQUEST['prefix'], $_REQUEST['start'], $this->_total_topics[$_REQUEST['prefix']], $context['maxindex'], true);
	}

	/**
	 * MessageIndex::topic_count()
	 * 
	 * Set the total topics count using the filter
	 * 
	 * @param array $sort_methods The sort methods for the messages
	 * @return void
	 */
	public function topic_count(&$sort_methods) : void
	{
		global $board_info, $modSettings, $user_info;

		// Filtering a prefix?
		if (!in_array($board_info['id'], explode(',', $modSettings['PostPrefix_filter_boards'])) || !isset($_REQUEST['prefix']) || empty($board_info['total_topics']))
			return;

		// Make sure it's an int
		$_REQUEST['prefix'] = (int) $_REQUEST['prefix'];

		// Update the total topics
		if (($this->_total_topics[$_REQUEST['prefix']] = cache_get_data('board_totaltopics_b' . $board_info['id'] . '_p' . $_REQUEST['prefix'], 1800)) == null)
		{
			// Total topics
			$this->_total_topics[$_REQUEST['prefix']] = Database::Count('topics',
				['id_board', 'id_prefix', 'approved', 'id_member_started'],
				'WHERE id_prefix = {int:prefix}
					AND id_board = {int:board}' . (!$modSettings['postmod_active'] || allowedTo('approve_posts') ? '' : '
					AND (approved = 1
						OR (id_member_started != 0
						AND id_member_started = {int:current_member}))'), '',
				[
					'prefix' => $_REQUEST['prefix'],
					'board' => $board_info['id'],
					'current_member' => $user_info['id'],
				]
			);

			// Cache the total topics
			cache_put_data('board_totaltopics_b' . $board_info['id'] . '_p' . $_REQUEST['prefix'], $this->_total_topics[$_REQUEST['prefix']], 1800);
		}

		// Replace the total topics with the filter
		$board_info['total_topics'] = $this->_total_topics[$_REQUEST['prefix']];

		// Get the sort methods somewhere
		$this->_sort_methods = $sort_methods;
	}

	/**
	 * MessageIndex:topics_prefixes()
	 * 
	 * Add the prefix before the topic title.
	 * Insert the filter box and filter the messages by prefix.
	 * 
	 * @return void
	 */
	public function topics_prefixes() : void
	{
		global $context, $modSettings, $board, $txt;

		// More prefixes?
		if (!empty($modSettings['PostPrefix_prefix_boardindex']))
			$this->get_last_messages();

		// We have any messages in the boards?
		if (!empty($this->_first_messages) && !empty($context['boards']))
		{
			// Add the prefixes to the last messages in the messageindex
			foreach ($context['boards'] as $p_board)
			{
				// Are we displaying a prefix?
				if (empty($p_board['last_post']['id']) || !isset($this->_first_messages[$p_board['last_post']['topic']]) || empty($modSettings['PostPrefix_prefix_all_msgs']) && $this->_first_messages[$p_board['last_post']['topic']]['id_first_msg'] != $p_board['last_post']['id'])
					continue;

				// First the subject
				$context['boards'][$p_board['id']]['last_post']['subject'] = PostPrefix::format($this->_first_messages[$p_board['last_post']['topic']]) . $p_board['last_post']['subject'];

				// Then the link
				$context['boards'][$p_board['id']]['last_post']['link'] = PostPrefix::format($this->_first_messages[$p_board['last_post']['topic']]) . $p_board['last_post']['link'];

				// And the last post message
				if (!empty($p_board['last_post']['last_post_message']))
					$context['boards'][$p_board['id']]['last_post']['last_post_message'] = sprintf($txt['last_post_message'], $p_board['last_post']['member']['link'], PostPrefix::format($this->_first_messages[$p_board['last_post']['topic']]) . $p_board['last_post']['link'], !empty($p_board['last_post']['time']) ? timeformat($p_board['last_post']['timestamp']) : $txt['not_applicable']);
			}
		}

		// First, add the prefix to the topic title.
		foreach ($context['topics'] as $id_topic => $pp_topic)
		{
			// Check for a prefix and if it's enabled
			if (empty($pp_topic['id_prefix']) || empty($pp_topic['prefix_status']))
				continue;

			// Set the prefix before the topic title.
			$context['topics'][$id_topic]['first_post']['subject'] = PostPrefix::format($pp_topic) . $pp_topic['first_post']['subject'];

			// Add the prefix to the link too
			$context['topics'][$id_topic]['first_post']['link'] = PostPrefix::format($pp_topic) . $pp_topic['first_post']['link'];
		}

		// Need to do anything else?
		if (empty($modSettings['PostPrefix_enable_filter']) || !in_array($board, explode(',', $modSettings['PostPrefix_filter_boards'])))
			return;

		// If there's a prefix filtered, pre-select it for a new topic.
		if (isset($_REQUEST['prefix']) && !empty($_REQUEST['prefix']))
			$context['normal_buttons']['new_topic']['url'] .= ';prefix=' . $_REQUEST['prefix'];

		// Okay, search the prefixes
		if (($context['prefixes']['filter'] = cache_get_data('prefix_filter_b' . $board, 3600)) === null)
		{
			$context['prefixes']['filter'] = Database::Get(0, 10000,
				'pp.' . (!empty($modSettings['PostPrefix_select_order']) ? 'id' : 'name'),
				'topics AS t',
				array_merge(['DISTINCT t.id_prefix'], Database::$_prefix_columns),
				'WHERE pp.status = {int:status}
					AND t.id_board = {int:board}', false,
				'LEFT JOIN {db_prefix}postprefixes AS pp ON (pp.id = t.id_prefix)',
				[
					'status' => 1,
					'board' => $board,
				]
			);
			cache_put_data('prefix_filter_b' . $board, $context['prefixes']['filter'], 3600);
		}

		// Check if we have any prefixes
		if (empty($context['prefixes']['filter']))
			return;

		// Format the prefixes
		foreach ($context['prefixes']['filter'] as $id_prefix => $prefix)
			$context['prefixes']['filter'][$id_prefix]['real_prefix'] = PostPrefix::format($prefix);

		// Language file
		Permissions::language();

		// Template
		loadTemplate('PostPrefix');

		// Template layer
		$context['template_layers'][] = 'postprefix_filter';
	}

	/**
	 * Boards::get_last_messages()
	 * 
	 * Query the last first messages from both the boardindex and the recent posts
	 * 
	 * @return void
	 */
	private function get_last_messages() : void
	{
		global $context, $modSettings, $user_info;

		// For boards in the messageindex?
		if (empty($context['boards']))
			return;

		// Get the last messages
		foreach ($context['boards'] as $p_board)
		{
			if (!empty($p_board['last_post']['id']))
			{
				// Last message
				$this->_last_messages[] = $p_board['last_post']['id'];

				// Get the topic?
				if (!empty($modSettings['PostPrefix_prefix_all_msgs']))
					$this->_topics[] = $p_board['last_post']['topic'];
			}
		}

		// Query these messages to get the prefixes if they are id_first_msg
		if (!empty($this->_last_messages) && (($this->_first_messages = cache_get_data('pp_messageindex_lastmessages_u' . $user_info['id'], 120)) === null))
		{
			$this->_first_messages = Database::Get(0, count($this->_last_messages), 't.id_topic',
				(!empty($modSettings['PostPrefix_prefix_all_msgs']) ? 'messages AS m' : 'topics AS t'),
				array_merge(
					['t.id_first_msg', 't.id_last_msg', 't.id_prefix', 't.id_topic'],
					Database::$_prefix_columns
				),
				'WHERE ' . (empty($modSettings['PostPrefix_prefix_all_msgs']) ? 't.id_first_msg IN ({array_int:messages})' : 'm.id_topic IN ({array_int:topics})') . '
					AND t.id_prefix > {int:prefix_zero}
					AND pp.status = {int:status}', false, (!empty($modSettings['PostPrefix_prefix_all_msgs']) ? 
				'LEFT JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)' : '') . 
				'LEFT JOIN {db_prefix}postprefixes AS pp ON (pp.id = t.id_prefix)',
				[
					'messages' => $this->_last_messages,
					'topics' => $this->_topics,
					'prefix_zero' => 0,
					'status' => 1,
				]
			);
			// Make the topic the key
			$this->_first_messages = array_column($this->_first_messages, null, 'id_topic');

			cache_put_data('pp_messageindex_lastmessages_u' . $user_info['id'], $this->_first_messages, 120);
		}
	}
}