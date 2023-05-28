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

class Groups
{
	/**
	 * Groups::delete_group() : void
	 * 
	 * Drop the prefixes linked to these groups.
	 * 
	 * @param array $groups The groups that are being deleted
	 * @return void
	 */
	public static function delete_group(array $groups) : void
	{
		// Delete the prefixes linked to these groups
		Database::Delete(
			'postprefixes_groups', 
			'id_group',
			$groups,
			'',
			'IN',
		);
	}

	/**
	 * Groups::edit_group()
	 * 
	 * Add the list of prefixes when editing a group
	 * 
	 * @return void
	 */
	public static function edit_group() : void
	{
		global $context;

		// Need a group
		if (!isset($_REQUEST['group']) || empty($_REQUEST['group']))
			return;

		// Exclude admins and moderators
		if ($_REQUEST['group'] == 1 || $_REQUEST['group'] == 3)
			return;

		// Get the prefixes selection
		PostPrefix::board_group_prefixes(true, $_REQUEST['group']);

		if (empty($context['PostPrefix_prefixes_selection']))
			return;

		// Language
		loadLanguage('PostPrefix/');

		// toggle all
		addInlineJavaScript('
			function togglePrefixes()
			{
				let checkboxes = document.querySelectorAll(\'#prefixes_list_selection input[type="checkbox"]\');
				let selectAllCheckbox = document.getElementById(\'checkall_prefixes\');

				for (var i = 0; i < checkboxes.length; i++) {
					checkboxes[i].checked = selectAllCheckbox.checked;
				}
			}
		');

		// Form
		$context['prefixes_form'] = 'groupForm';

		// Layer
		$context['template_layers'][] = 'postprefix_board_group_list';
	}

	/**
	 * Groups::save_group()
	 * 
	 * Save the prefixes for the group
	 * 
	 * @param int $group_id The group id
	 * @return void
	 */
	public static function save_group(int $group_id) : void
	{
		// Need a group
		if (!isset($group_id) || empty($group_id))
			return;

		// Exclude admins and moderators
		if ($group_id == 1 || $group_id == 3)
			return;

		// Select the prefixes
		$_POST['postprefixSelect'] = isset($_POST['postprefixSelect']) ? array_values($_POST['postprefixSelect']) : [0];

		// Drop the prefixes
		Database::Delete('postprefixes_groups', 
			'id_prefix',
			(array) $_POST['postprefixSelect'],
			' AND id_group = {int:group}',
			'NOT IN',
			[
				'group' => $group_id,
			],
		);

		// Add them...
		if (!empty($_POST['postprefixSelect']))
		{
			// Set groups
			$prefixes = [];
			foreach ($_POST['postprefixSelect'] as $prefix_id)
				$prefixes[] = [
					'id_prefix' => $prefix_id,
					'id_group' => $group_id,
				];
			
			// Insert groups
			Database::Insert('postprefixes_groups',
				$prefixes,
				[
					'id_prefix' => 'int',
					'id_group' => 'int'
				],
				[
					'id_group',
					'id_prefix'
				],
				'ignore'
			);
		}

		// clean cache
		clean_cache();
	}
}