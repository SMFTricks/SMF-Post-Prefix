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
}