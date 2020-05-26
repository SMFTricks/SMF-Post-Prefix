<?php

/**
 * @package SMF Post Prefix
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

use PostPrefix\PostPrefix;

function template_prefixfilter_above()
{
	global $context, $modSettings, $scripturl, $txt;

	// Prefix
	if (!empty($context['prefix']['filter']))
	{
		echo'
			<div class="cat_bar">
				<h3 class="catbg">
					', $txt['PostPrefix_filter'],'
				</h3>
			</div>
			<div class="windowbg">
				<div class="content">';

			// Show all the prefixes for this board.
			foreach ($context['prefix']['filter'] as $prefix)
				echo'
					<a href="' . $scripturl . '?board=' . $context['current_board'] . '.0;prefix=' . $prefix['id'] . '">' . PostPrefix::format($prefix) . '</a>,';

				echo '
					<a href="', $scripturl, '?board=', $context['current_board'], '.0;prefix=0">', $txt['PostPrefix_filter_noprefix'], '</a>, 
					<a href="', $scripturl, '?board=', $context['current_board'], '.0">', $txt['PostPrefix_filter_all'], '</a>
				</div>
			</div>';
	}
}

function template_prefixfilter_below(){}

function template_postprefix()
{
	global $context, $txt, $scripturl;

	echo '
	<div class="windowbg">
		<form name="set_prefix" id="set_prefix" method="post" action="', $scripturl, '?action=admin;area=postprefix;sa=save">
			', isset($_REQUEST['id']) && !empty($context['prefix']['id']) ? '<input type="hidden" name="id" value="'.$context['prefix']['id'].'">' : '', '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
			<dl class="settings">
				<dt>
					<a id="setting_name"></a>
					<span><label for="name">', $txt['PostPrefix_prefix_name'], ':</label></span>
				</dt>
				<dd>
					<input class="input_text" name="name" id="name" type="text" value="', !empty($context['prefix']['name']) ? $context['prefix']['name'] : '', '" style="width: 100%">
				</dd>

				<dt>
					<a id="setting_status"></a>
					<span><label for="status">', $txt['PostPrefix_prefix_enable'], ':</label></span>
				</dt>
				<dd>
					<input class="input_check" type="checkbox" name="status" id="status"', !empty($context['prefix']['status']) ? ' checked' : '', ' value="1">
				</dd>
				<dt>
					<a id="setting_usecolor"></a>
					<span><label for="usecolor">', $txt['PostPrefix_prefix_color'], ':</label></span>
				</dt>
				<dd>
					<input class="input_check" type="checkbox" name="usecolor" value="1"', !empty($context['prefix']['color']) ? ' checked' : '', ' onclick="document.getElementById(\'color\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'bgcolor1\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'bgcolor2\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'invert1\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'invert2\').style.display = this.checked ? \'block\' : \'none\';">
					<input class="input_text" name="color" id="color" type="text" style="border-width: 1px 1px 1px 25px;', empty($context['prefix']['color']) ? 'display:none;"' : 'display:block;border-color:'.$context['prefix']['color'].';" value="'.$context['prefix']['color'].'"', '>
				</dd>
				<dt id="bgcolor1"', empty($context['prefix']['color']) ? 'style="display:none;"' : '', '>
					<a id="setting_bgcolor"></a>
					<span><label for="bgcolor">', $txt['PostPrefix_use_bgcolor'], ':</label></span>
				</dt>
				<dd id="bgcolor2"', empty($context['prefix']['color']) ? 'style="display:none;"' : '', '>
					<input class="input_check" name="bgcolor" id="bgcolor"', !empty($context['prefix']['bgcolor']) ? ' checked' : '', ' type="checkbox" value="1">
				</dd>
				<dt id="invert1"', empty($context['prefix']['color']) ? 'style="display:none;"' : '', '>
					<a id="setting_invert"></a>
					<span><label for="invert">', $txt['PostPrefix_invert_color'], ':</label></span><br>
					<span class="smalltext">', $txt['PostPrefix_invert_color_desc'], '</span>
				</dt>
				<dd id="invert2"', empty($context['prefix']['color']) ? 'style="display:none;"' : '', '>
					<input class="input_check" name="invert" id="invert"', !empty($context['prefix']['invert_color']) ? ' checked' : '', ' type="checkbox" value="1">
				</dd>
				<dt>
					<a id="setting_icon"></a>
					<span><label for="icon">', $txt['PostPrefix_use_icon'], ':</label></span>
					<span><label for="icon_url" id="lab_icon_url"', empty($context['prefix']['icon_url']) ? 'style="display:none;"' : 'style="display:block;"', '>', $txt['PostPrefix_icon_url'], ':</label></span>
				</dt>
				<dd>
					<input class="input_check" type="checkbox" name="icon" value="1"', !empty($context['prefix']['icon_url']) ? ' checked' : '', ' onclick="document.getElementById(\'icon_url\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'lab_icon_url\').style.display = this.checked ? \'block\' : \'none\';">
					<input class="input_text" name="icon_url" id="icon_url" type="text" style="width:100%;', empty($context['prefix']['icon_url']) ? 'display:none;' : 'display:block;', '" value="', !empty($context['prefix']['icon_url']) ? $context['prefix']['icon_url'] : '', '">
				</dd>
			</dl>
			<hr>
			<dl class="settings">
				<dt>
					<a id="setting_groups"></a>
					<span><label for="groups">', $txt['PostPrefix_prefix_groups'], ':</label></span>
				</dt>
				<dd>
					', groups_list(), '
				</dd>
				<dt>
					<a id="setting_boards"></a>
					<span><label for="boards">', $txt['PostPrefix_prefix_boards'], ':</label></span>
				</dt>
				<dd>
					', boards_list(), '
				</dd>
			</dl>
			<input class="button" type="submit" value="', $txt['PostPrefix_save_prefix'], '">
		</form>
	</div>';
}

/**
 * The template for determining which groups can access a board.
 * 
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2020 Simple Machines and individual contributors
 */

function groups_list()
{
	global $context, $txt;

	echo '
				<fieldset id="visible_groups">
					<legend>', $txt['PostPrefix_prefix_groups'], '</legend>
					<ul class="padding floatleft">';

	// List all the membergroups so the user can choose who may access this board.
	foreach ($context['forum_groups'] as $group)
	{
		$group['allow'] = in_array($group['id_group'], $context['prefix']['groups']) ? true : false;
			
		echo '
						<li class="group">
							<input type="checkbox" name="groups[', $group['id_group'], ']" value="', $group['id_group'], '" id="groups_', $group['id_group'], '"', $group['allow'] ? ' checked' : '', '> <label for="groups_', $group['id_group'], '">', $group['group_name'], '</label>
						</li>';
	}
				echo '
					</ul>
					<br class="clear">
					<input type="checkbox" id="checkall_check" onclick="invertAll(this, this.form, \'groups\');">
					<label for="checkall_check"><em>', $txt['check_all'], '</em></label>
				</fieldset>
				<a href="javascript:void(0);" onclick="document.getElementById(\'visible_groups\').classList.remove(\'hidden\'); document.getElementById(\'visible_groups_link\').classList.add(\'hidden\'); return false;" id="visible_groups_link" class="hidden">[ ', $txt['PostPrefix_select_visible_groups'], ' ]</a>
				<script>
					document.getElementById("visible_groups_link").classList.remove(\'hidden\');
					document.getElementById("visible_groups").classList.add(\'hidden\');
				</script>';
}

/**
 * The template for determining which boards a group has access to.
 * 
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2020 Simple Machines and individual contributors
 * @param bool $collapse Whether to collapse the list by default
 */
function boards_list($collapse = true, $form_id = 'set_prefix')
{
	global $context, $txt;

	echo '
							<fieldset id="visible_boards">
								<legend>', $txt['PostPrefix_prefix_boards'], '</legend>
								<ul class="padding floatleft">';

	foreach ($context['forum_categories'] as $category)
	{
		echo '
									<li class="category">
										<a href="javascript:void(0);" onclick="selectBoards([', implode(', ', $category['child_ids']), '], \''.$form_id.'\'); return false;"><strong>', $category['cat_name'], '</strong></a>
										<ul>';

		foreach ($category['boards'] as $board)
		{
			$board['allow'] = in_array($board['id_board'], $context['prefix']['boards']) ? true : false;
			$board['deny'] = false;

			echo '
											<li class="board" style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $board['child_level'], 'em;">
												<input type="checkbox" name="boardset[', $board['id_board'], ']" id="brd', $board['id_board'], '" value="', $board['id_board'], '"', $board['allow'] ? ' checked' : '', '> <label for="brd', $board['id_board'], '">', $board['name'], '</label>
											</li>';
		}
		echo '
										</ul>
									</li>';
	}
	echo '
								</ul>
								<br class="clear">
								<input type="checkbox" id="checkall_check" onclick="invertAll(this, this.form, \'boardset\');">
								<label for="checkall_check"><em>', $txt['check_all'], '</em></label>
							</fieldset>';

	if ($collapse)
		echo '
							<a href="javascript:void(0);" onclick="document.getElementById(\'visible_boards\').classList.remove(\'hidden\'); document.getElementById(\'visible_boards_link\').classList.add(\'hidden\'); return false;" id="visible_boards_link" class="hidden">[ ', $txt['PostPrefix_select_visible_boards'], ' ]</a>
							<script>
								document.getElementById("visible_boards_link").classList.remove(\'hidden\');
								document.getElementById("visible_boards").classList.add(\'hidden\');
							</script>';
}

function template_postprefix_show_above()
{
	global $context, $modSettings, $settings, $txt;

	echo '
	<!DOCTYPE html>
	<html', $context['right_to_left'] ? ' dir="rtl"' : '', !empty($txt['lang_locale']) ? ' lang="' . str_replace("_", "-", substr($txt['lang_locale'], 0, strcspn($txt['lang_locale'], "."))) . '"' : '', '>
		<head>
			<meta charset="', $context['character_set'], '">
			<meta name="robots" content="noindex">
			<title>', $context['page_title'], '</title>
			<link rel="stylesheet" type="text/css" href="', $settings['theme_url'], '/css/index', $context['theme_variant'], '.css', $modSettings['browser_cache'] ,'">
			<script src="', $settings['default_theme_url'], '/scripts/script.js', $modSettings['browser_cache'] ,'"></script>
		</head>';
}

function template_postprefix_show()
{
	global $context, $txt;

	echo '
		<body id="postprefix_show' . $context['prefix']['show'] . '_popup">
			<div class="cat_bar">
				<h3 class="catbg">
					', $txt['PostPrefix_prefix_' . $context['prefix']['show']], '
				</h3>
			</div>
			<div class="windowbg">';

		// We got results?
		if (empty($context['prefix']['get_type']))
			echo '
				<div class="roundframe">
					', $txt['PostPrefix_empty_' . $context['prefix']['show']], '
				</div>';
		else
		{
			echo '
				<ul>';

			// Loop through the items
			foreach($context['prefix']['get_type'] as $type)
			{
				echo '
					<li', isset($type['cat_order']) ? '' : ' class="windowbg"' , '>
						<div', isset($type['cat_order']) ? ' class="title_bar"' : '' , '>
							<h4', isset($type['cat_order']) ? ' class="titlebg"' : ' style="font-weight:normal;', !empty($type['online_color']) ? 'color:' . $type['online_color'] . ';"' : '"', '>
								', $type['cat_name'], '
							</h4>
						</div>';

				// Boards?
				if (!empty($type['boards']))
				{
					echo '
						<ul>';

					foreach ($type['boards'] as $board)
					{
						echo '
								<li class="windowbg board" style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $board['child_level'], 'em;">
									', $board['name'], '
								</li>';
					}
					echo '
							</ul>';
				}
				echo '
					</li>';
			}
			echo '
				</ul>';
		}
			echo '
				<br class="clear">
				<a href="javascript:self.close();">', $txt['close_window'], '</a>
			</div>';
}

function template_postprefix_show_below()
{
	echo '
		</body>
	</html>';
}