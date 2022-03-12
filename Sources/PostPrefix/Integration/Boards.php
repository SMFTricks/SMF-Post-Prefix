<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

namespace PostPrefix\Integration;

if (!defined('SMF'))
	die('No direct access...');

class Boards
{
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
}