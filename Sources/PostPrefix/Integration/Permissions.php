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

class Permissions
{
	/**
	 * Permissions::load_permissions()
	 *
	 * @param array $permissionGroups An array containing all possible permissions groups.
	 * @param array $permissionList An associative array with all the possible permissions.
	 * @return void
	 */
	public static function load_permissions(array &$permissionGroups, array &$permissionList) : void
	{
		// Manage prefix
		$permissionList['membergroup']['postprefix_manage'] = [false, 'maintenance'];
		// Topic?
		$permissionList['board']['postprefix_set'] = [false, 'topic'];
	}

	/**
	 * Permissions::illegal_guest()
	 *
	 * Remove the permission from guests
	 * 
	 * @return void
	 */
	public static function illegal_guest() : void
	{
		global $context;

		// Guests can't manage the prefixes
		$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], ['postprefix_manage']);
	}

	/**
	 * Permissions::language()
	 *
	 * Loads the admin language file for the help popups in the permissions page
	 * 
	 * @return void
	 */
	public static function language() : void
	{
		loadLanguage('PostPrefix/');
	}
}