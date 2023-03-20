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

class Recent
{
	/**
	 * @var array Topics for the messages
	 */
	private $_topics = [];

	/**
	 * @var array The topic prefixes (if any)
	 */
	private $_prefixes = [];

	/**
	 * Unread:recent_posts()
	 * 
	 * Add the prefix before the topic title.
	 * 
	 * @return void
	 */
	public function recent_posts() : void
	{
		global $context, $modSettings;

		// Any topics?
		if (empty($context['posts']) || empty($modSettings['PostPrefix_prefix_recent_page']))
			return;

		// More prefixes?
		$this->get_last_messages();

		// We have any prefixes?
		if (!empty($this->_prefixes))
		{
			// Add the prefixes to the last messages in the messageindex
			foreach ($context['posts'] as $post)
			{
				// Are we displaying a prefix?
				if (!isset($this->_prefixes[$post['topic']]) || (empty($modSettings['PostPrefix_prefix_all_msgs']) && $post['id'] != $this->_prefixes[$post['topic']]['id_first_msg']))
					continue;

				// First the subject
				$context['posts'][$post['id']]['subject'] = PostPrefix::format($this->_prefixes[$post['topic']]) . $context['posts'][$post['id']]['subject'];

				// Then the link
				$context['posts'][$post['id']]['link'] = PostPrefix::format($this->_prefixes[$post['topic']]) . $context['posts'][$post['id']]['link'];
			}
		}
	}

	/**
	 * Boards::get_last_messages()
	 * 
	 * Query the last messages for the recent posts pages
	 * 
	 * @return void
	 */
	private function get_last_messages() : void
	{
		global $context;

		// Get the last messages
		foreach ($context['posts'] as $post)
			$this->_topics[] = $post['id'];

		// Query ALL of the messages to get the prefixes.
		// For the recent posts, we don't know which ones are first messages, so the query will just get all of them.
		if (!empty($this->_topics))
		{
			$this->_prefixes = Database::Get(0, count($this->_topics), 't.id_topic DESC',
				'messages AS m',
				array_merge(
					['t.id_first_msg', 't.id_last_msg', 't.id_prefix', 't.id_topic'],
					Database::$_prefix_columns
				),
				'WHERE m.id_msg IN ({array_int:messages})
					AND t.id_prefix > {int:prefix_zero}
					AND pp.status = {int:status}', false,
				'LEFT JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)
				LEFT JOIN {db_prefix}postprefixes AS pp ON (pp.id = t.id_prefix)',
				[
					'messages' => $this->_topics,
					'prefix_zero' => 0,
					'status' => 1,
				]
			);
			// Make the topic the key
			$this->_prefixes = array_column($this->_prefixes, null, 'id_topic');
		}
	}
}