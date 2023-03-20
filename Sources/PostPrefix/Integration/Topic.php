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

class Topic
{
	/**
	 * Topic::display_topic()
	 * 
	 * Add the prefix to the topic query
	 * 
	 * @param array $topic_selects The topic columns
	 * @param array $topic_tables The additional tables to join
	 * @return void
	 */
	public function display_topic(array &$topic_selects, array &$topic_tables)
	{
		// Prefix topic column
		$topic_selects[] = 't.id_prefix';

		// Prefix columns
		foreach (Database::$_prefix_columns as $column)
			$topic_selects[] = $column;

		// Add the table
		$topic_tables[] = 'LEFT JOIN {db_prefix}postprefixes AS pp ON (pp.id = t.id_prefix)';
	}

	/**
	 * Topic::view_topic()
	 * 
	 * Add the prefix to the topic subject
	 * 
	 * @return void
	 */
	public function view_topic()
	{
		global $context, $modSettings;

		// Topic has a prefix?
		if (empty($context['topicinfo']['id_prefix']) || empty($context['topicinfo']['prefix_status']))
			return;

		/// Add the prefix to the title without harming any other vital usage of this information
		addInlineJavaScript('
				var pp_subject = document.getElementById("top_subject");
				pp_subject.innerHTML = \'' . PostPrefix::format($context['topicinfo']) . '\' + " " + pp_subject.textContent;
			', true);

		// Add the prefix to the linktree
		if (!empty($modSettings['PostPrefix_prefix_linktree']))
			$context['linktree'][count($context['linktree'])-1]['extra_before'] = PostPrefix::format($context['topicinfo'], [
				'padding-block: 3px',
				'margin: 0',
				'display: inline-flex'
			]);
	}
}