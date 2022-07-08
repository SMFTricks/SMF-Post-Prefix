<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix\Integration;

use PostPrefix\PostPrefix;
use PostPrefix\Helper\Database;

if (!defined('SMF'))
	die('No direct access...');

class Boards
{
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
	 * @var int The original total number of messages
	 */
	private $_total_messages = 0;

	/**
	 * Boards::edit_board()
	 * 
	 * Modify a specific board...
	 * 
	 * @return void
	 */
	public static function edit_board() : void
	{
		global $context, $txt, $modSettings;

		// Require prefix
		$context['custom_board_settings']['PostPrefix_prefix_boards_require'] = [
			'dt' => '<strong>'. $txt['PostPrefix_prefix_boards_require']. '</strong><br /><span class="smalltext">'. $txt['PostPrefix_prefix_boards_require_desc']. '</span>',
			'dd' => '<input type="checkbox" name="PostPrefix_prefix_boards_require" class="input_check"'. (in_array($context['board']['id'], explode(',', $modSettings['PostPrefix_prefix_boards_require'])) ? ' checked="checked"' : ''). '>',
		];

		// Enable filter
		if (!empty($modSettings['PostPrefix_enable_filter']))
			$context['custom_board_settings']['PostPrefix_enable_filter'] = [
				'dt' => '<strong>'. $txt['PostPrefix_enable_filter']. '</strong><br /><span class="smalltext">'. $txt['PostPrefix_enable_filter_desc']. '</span>',
				'dd' => '<input type="checkbox" name="PostPrefix_enable_filter" class="input_check"'. (in_array($context['board']['id'], explode(',', $modSettings['PostPrefix_filter_boards'])) ? ' checked="checked"' : ''). '>',
			];
	}

	/**
	 * Boards::modify_board()
	 * 
	 * @param int $id The board ID
	 * @param array $boardOptions An array of options related to the board
	 * @return void
	 */
	public static function modify_board(int $id, array $boardOptions) : void
	{
		global $modSettings;

		// Require prefix
		$boardOptions['PostPrefix_prefix_boards_require'] = isset($_POST['PostPrefix_prefix_boards_require']);
		if (isset($boardOptions['PostPrefix_prefix_boards_require']))
		{
			// Add the board to the boards that require prefixes, if it's not there already
			if (!empty($boardOptions['PostPrefix_prefix_boards_require']) && !in_array($id, explode(',', $modSettings['PostPrefix_prefix_boards_require'])))
				updateSettings(['PostPrefix_prefix_boards_require' => !empty($modSettings['PostPrefix_prefix_boards_require']) ? implode(',', array_merge(explode(',', $modSettings['PostPrefix_prefix_boards_require']), [$id])) : $id]);
			// Remove the board from the required boards, if it's there
			elseif (empty($boardOptions['PostPrefix_prefix_boards_require']) && in_array($id, explode(',', $modSettings['PostPrefix_prefix_boards_require'])))
				updateSettings(['PostPrefix_prefix_boards_require' => implode(',', array_diff(explode(',', $modSettings['PostPrefix_prefix_boards_require']), [$id]))], true);
		}

		// Enable filter
		if (!empty($modSettings['PostPrefix_enable_filter']))
		{
			$boardOptions['PostPrefix_enable_filter'] = isset($_POST['PostPrefix_enable_filter']);
			if (isset($boardOptions['PostPrefix_enable_filter']))
			{
				// Add the board to the filter boards, if it's not there already
				if (!empty($boardOptions['PostPrefix_enable_filter']) && !in_array($id, explode(',', $modSettings['PostPrefix_filter_boards'])))
					updateSettings(['PostPrefix_filter_boards' => !empty($modSettings['PostPrefix_filter_boards']) ? implode(',', array_merge(explode(',', $modSettings['PostPrefix_filter_boards']), [$id])) : $id]);
				// Remove the board from the filter boards, if it's there
				elseif (empty($boardOptions['PostPrefix_enable_filter']) && in_array($id, explode(',', $modSettings['PostPrefix_filter_boards'])))
					updateSettings(['PostPrefix_filter_boards' => implode(',', array_diff(explode(',', $modSettings['PostPrefix_filter_boards']), [$id]))]);
			}
		}
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
		global $context, $board, $modSettings, $user_info;

		// Get the last messages from the boardindex.
		if (!empty($context['categories']))
		{
			foreach ($context['categories'] as $category)
			{
				if (!empty($category['boards']))
				{
					foreach ($category['boards'] as $board)
					{
						if (!empty($board['last_post']['id']))
						{
							// Last message
							$this->_last_messages[] = $board['last_post']['id'];

							// Topic?
							if (!empty($modSettings['PostPrefix_prefix_all_msgs']))
								$this->_topics[] = $board['last_post']['topic'];
						}
					}
				}
			}
		}

		// Get the last messages from the recent posts.
		if (!empty($context['latest_posts']))
		{
			foreach ($context['latest_posts'] as $post)
			{
				// Obtain the id_msg from the href
				if (preg_match('~#msg(\d+)~', $post['href'], $matches))
				{
					// Store the last message id
					$this->_last_messages[] = $matches[1];

					// Topic?
					if (!empty($modSettings['PostPrefix_prefix_all_msgs']))
						$this->_topics[] = $post['topic'];
				}
			}
		}

		// Now, remove any duplicates.
		$this->_total_messages = count($this->_topics) + count($this->_last_messages);
		$this->_last_messages = array_unique($this->_last_messages);
		$this->_topics = array_unique($this->_topics);

		// Query these messages to get the prefixes if they are id_first_msg
		if (!empty($this->_last_messages) && (($this->_first_messages = cache_get_data('pp_boardindex_lastmessages_u' . $user_info['id'], 120)) === null))
		{
			$this->_first_messages = Database::Get(0, $this->_total_messages, 't.id_topic DESC',
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

			cache_put_data('pp_boardindex_lastmessages_u' . $user_info['id'], $this->_first_messages, 120);
		}
	}

	/**
	 * Boards::recentPosts()
	 * 
	 * Will add prefixes to the last messages in the boardindex and recent posts
	 * 
	 * @return void
	 */
	public function recentPosts() : void
	{
		global $context, $txt, $modSettings, $scripturl;

		// Is this enabled?
		if (empty($modSettings['PostPrefix_prefix_boardindex']))
			return;

		// Get the messages?
		$this->get_last_messages();

		// We have any messages?
		if (empty($this->_first_messages))
			return;

		// Add the prefixes to the last messages in the boardindex.
		if (!empty($context['categories']))
		{
			foreach ($context['categories'] as $category)
			{
				if (!empty($category['boards']))
				{
					foreach ($category['boards'] as $board)
					{
						// Are we displaying a prefix?
						if (empty($board['last_post']['id']) || !isset($this->_first_messages[$board['last_post']['topic']]) || empty($modSettings['PostPrefix_prefix_all_msgs']) && $this->_first_messages[$board['last_post']['topic']]['id_first_msg'] != $board['last_post']['id'])
							continue;

						// First the subject
						$context['categories'][$category['id']]['boards'][$board['id']]['last_post']['subject'] = PostPrefix::format($this->_first_messages[$board['last_post']['topic']]) . $board['last_post']['subject'];

						// Then the link
						$context['categories'][$category['id']]['boards'][$board['id']]['last_post']['link'] = PostPrefix::format($this->_first_messages[$board['last_post']['topic']]) . $board['last_post']['link'];

						// And the last post message
						if (!empty($board['last_post']['last_post_message']))
							$context['categories'][$category['id']]['boards'][$board['id']]['last_post']['last_post_message'] = sprintf($txt['last_post_message'], $board['last_post']['member']['link'], PostPrefix::format($this->_first_messages[$board['last_post']['topic']]) . $board['last_post']['link'], !empty($board['last_post']['time']) ? timeformat($board['last_post']['timestamp']) : $txt['not_applicable']);
					}
				}
			}
		}

		// Add the prefixes to the recent posts
		if (!empty($context['latest_posts']))
		{
			foreach ($context['latest_posts'] as $key => $post)
			{
				// Obtain the id_msg from the href
				if (preg_match('~#msg(\d+)~', $post['href'], $matches))
				{
					// Are we displaying a prefix?
					if (!isset($this->_first_messages[$post['topic']]) || empty($modSettings['PostPrefix_prefix_all_msgs']) && $this->_first_messages[$post['topic']]['id_first_msg'] != $matches[1])
						continue;

					// First the subject
					$context['latest_posts'][$key]['subject'] = PostPrefix::format($this->_first_messages[$post['topic']]) . $post['subject'];

					// Then the short subject
					$context['latest_posts'][$key]['short_subject'] = PostPrefix::format($this->_first_messages[$post['topic']]) . $post['short_subject'];

					// And finally, the link
					$context['latest_posts'][$key]['link'] = PostPrefix::format($this->_first_messages[$post['topic']]) . '<a href="' . $scripturl . '?topic=' . $post['topic'] . '.msg' . $matches[1] . ';topicseen#msg' . $matches[1] . '" rel="nofollow">' . $post['subject'] . '</a>';
				}
			}
		}
	}
}