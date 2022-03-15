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

class Unread
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
	 * Unread:unread_topics()
	 * 
	 * Add the prefix before the topic title.
	 * 
	 * @return void
	 */
	public function unread_topics() : void
	{
		global $context, $modSettings;

		// Any topics?
		if (empty($context['topics']) || empty($modSettings['PostPrefix_prefix_recent_page']))
			return;

		// More prefixes?
		$this->get_last_messages();

		// We have any prefixes?
		if (!empty($this->_prefixes))
		{
			// Add the prefixes to the last messages in the messageindex
			foreach ($context['topics'] as $topic)
			{
				// Are we displaying a prefix?
				if (!isset($this->_prefixes[$topic['id']]))
					continue;

				// First the subject
				$context['topics'][$topic['id']]['first_post']['subject'] = PostPrefix::format($this->_prefixes[$topic['id']]) . $context['topics'][$topic['id']]['first_post']['subject'];

				// Then the link
				$context['topics'][$topic['id']]['first_post']['link'] = PostPrefix::format($this->_prefixes[$topic['id']]) . $topic['first_post']['link'];
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
		global $context;

		// Get the last messages
		foreach ($context['topics'] as $topic)
			$this->_topics[] = $topic['id'];

		// Query these messages to get the prefixes if they are id_first_msg
		if (!empty($this->_topics))
		{
			$this->_prefixes = Database::Get(0, count($this->_topics), 't.id_topic',
				'topics AS t',
				array_merge(
					['t.id_prefix', 't.id_topic'],
					Database::$_prefix_columns
				),
				'WHERE t.id_topic IN ({array_int:topics})
					AND t.id_prefix > {int:prefix_zero}', false, 
				'LEFT JOIN {db_prefix}postprefixes AS pp ON (pp.id = t.id_prefix)',
				[
					'topics' => $this->_topics,
					'prefix_zero' => 0,
				]
			);
			// Make the topic the key
			$this->_prefixes = array_column($this->_prefixes, null, 'id_topic');
		}
	}
}