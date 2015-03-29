<?php

/**
 * @package SMF Post Prefix
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2014, Diego Andrés
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */
 
	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	// So... looking for something new
	$hooks = array(
		'integrate_pre_include' => '$sourcedir/Subs-PostPrefix.php',
		'integrate_admin_areas' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::admin_areas#',
		'integrate_load_permissions' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::permissions#',
		'integrate_load_illegal_guest_permissions' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::illegal_guest_permissions#',
		'integrate_load_theme' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::load_theme#',
		'integrate_create_post' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::create_post#',
		'integrate_modify_post' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::modify_post#',
		'integrate_modify_topic' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::modify_topic#',
		'integrate_before_create_topic' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::before_create_topic#',
		'integrate_post_errors' => '$sourcedir/Subs-PostPrefix.php|PostPrefix::post_errors#',
	);

	foreach ($hooks as $hook => $function)
		remove_integration_function($hook, $function);