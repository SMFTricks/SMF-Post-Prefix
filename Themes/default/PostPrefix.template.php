<?php

/**
 * @package SMF Post Prefix
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

function template_prefixfilter_above()
{
	global $context, $modSettings, $scripturl, $txt;

	// Prefix
	if (!empty($modSettings['PostPrefix_enable_filter']) && !empty($context['prefix']['post']))
	{
		echo'
			<div class="cat_bar">
				<h3 class="catbg">
					', $txt['PostPrefix_filter'],'
				</h3>
			</div>
			<div class="windowbg">
				<span class="topslice"><span></span></span>
					<div class="content">';

				// Show all the prefixes for this board.
				foreach ($context['prefix']['post'] as $prefix)
					echo'
						<a href="' . $scripturl . '?board=' . $context['current_board'] . '.0;prefix=' . $prefix['id'] . '">' . PostPrefix::formatPrefix($prefix['id']) . '</a>, ';
			
			echo'
						<a href="', $scripturl, '?board=', $context['current_board'], '.0;prefix=0">', $txt['PostPrefix_filter_noprefix'], '</a>, 
						<a href="', $scripturl, '?board=', $context['current_board'], '.0">', $txt['PostPrefix_filter_all'], '</a>
					</div>
				<span class="botslice"><span></span></span>
			</div>
			<br class="clear" />';
	}
}

function template_prefixfilter_below(){}

function template_postprefix()
{
	global $context, $txt, $scripturl, $modSettings, $boardurl;

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
					<input class="input_check" type="checkbox" name="usecolor" value="1"', !empty($context['prefix']['color']) ? ' checked' : '', ' onclick="document.getElementById(\'color\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'bgcolor1\').style.display = this.checked ? \'block\' : \'none\'; document.getElementById(\'bgcolor2\').style.display = this.checked ? \'block\' : \'none\';">
					<input class="input_text" name="color" id="color" type="text" style="border-width: 1px 1px 1px 25px;', empty($context['prefix']['color']) ? 'display:none;"' : 'display:block;border-color:'.$context['prefix']['color'].';" value="'.$context['prefix']['color'].'"', '>
				</dd>
				<dt id="bgcolor1"', empty($context['prefix']['color']) ? 'style="display:none;"' : '', '>
					<a id="setting_bgcolor"></a>
					<span><label for="bgcolor">', $txt['PostPrefix_use_bgcolor'], ':</label></span>
				</dt>
				<dd id="bgcolor2"', empty($context['prefix']['color']) ? 'style="display:none;"' : '', '>
					<input class="input_check" name="bgcolor" id="bgcolor"', !empty($context['prefix']['bgcolor']) ? ' checked' : '', ' type="checkbox" value="1">
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
	global $context, $txt, $modSettings;

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
	global $context, $txt, $modSettings;

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

function template_postprefix_showgroups()
{
	global $context, $boardurl, $settings, $modSettings, $txt;

	echo '
<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', '>
	<head>
		<meta charset="', $context['character_set'], '">
		<meta name="robots" content="noindex">
		<title>', $context['page_title'], '</title>
		<link rel="stylesheet" type="text/css" href="', $settings['theme_url'], '/css/index', $context['theme_variant'], '.css', $modSettings['browser_cache'] ,'">
		<script src="', $settings['default_theme_url'], '/scripts/script.js', $modSettings['browser_cache'] ,'"></script>
	</head>
	<body id="postprefix_showgroups_popup">
		<div class="windowbg">
			<table class="table_grid clear">
				<thead>
					<tr class="title_bar">
						<th>
							', $txt['PostPrefix_prefix_groups'], '
						</th>
					</tr>
				</thead>
				<tbody>';

	if (empty($context['empty_groups']))
	{
		echo '
					<tr class="up_contain">
						<td>
							', $txt['PostPrefix_empty_groups'], '
						</td>
					</tr>';
	}
	else
	{
		// We're going to list all the groups...
		foreach ($context['member_groups'] as $group)
		{
			echo '
					<tr class="stripes">
						<td>
							<span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted #000; cursor: help;" title="' . $txt['mboards_groups_post_group'] . '"' : ($group['id'] == 0 ? ' style="border-bottom: 1px dotted #000; cursor: help;" title="' . $txt['mboards_groups_regular_members'] . '"' : ''), '>
								', $group['name'], '
							</span>
						</td>
					</tr>';
		}
	}

	echo '
				</tbody>
			</table>
			<br class="clear">
			<a href="javascript:self.close();">', $txt['close_window'], '</a>
		</div>
	</body>
</html>';
}

function template_postprefix_showboards()
{
	global $context, $settings, $modSettings, $txt, $scripturl;

	echo '
<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', '>
	<head>
		<meta charset="', $context['character_set'], '">
		<meta name="robots" content="noindex">
		<title>', $context['page_title'], '</title>
		<link rel="stylesheet" type="text/css" href="', $settings['theme_url'], '/css/index', $context['theme_variant'], '.css', $modSettings['browser_cache'] ,'">
		<script src="', $settings['default_theme_url'], '/scripts/script.js', $modSettings['browser_cache'] ,'"></script>
	</head>
	<body id="postprefix_showboards_popup">
		<div class="windowbg">
			<div class="title_bar">
				<h4 class="titlebg">
					', $txt['PostPrefix_prefix_boards'], '
				</h4>
			</div>';

	if (empty($context['empty_boards']))
	{
		echo '
			<div class="up_contain">
					', $txt['PostPrefix_empty_boards'], '
			</div>';
	}
	else
	{
		// We're going to list all the boards...
		foreach ($context['categories'] as $category)
		{
			echo '
			<div class="cat_bar">
				<h3 class="catbg">
					<a href="', $scripturl, '/index.php#c', $category['id'], '">', $category['name'], '</a>
				</h3>
			</div>';

			foreach ($category['boards'] as $board)
			{
				echo '
				<div class="up_contain" style="min-height: 15px;">
					<span style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $board['child_level'], 'em;"><a class="subject" href="', $scripturl, '?board=', $board['id'], '.0">', $board['name'], '</a></span>
				</div>';
			}
		}
	}

	echo '
			<br class="clear">
			<a href="javascript:self.close();">', $txt['close_window'], '</a>
		</div>
	</body>
</html>';
}

function template_require_prefix()
{
	global $context, $txt, $scripturl;

	// Updated?
	if (isset($_REQUEST['updated']))
		echo '
	<div class="infobox">'.$txt['PostPrefix_required_updated'].'</div>';

	echo '
	<div class="windowbg">
		<form name="PostPrefixRequire" id="PostPrefixRequire" method="post" action="', $scripturl, '?action=admin;area=postprefix;sa=require2">

			<dl class="settings">
				<dt>
					<a id="setting_boards"></a>
					<span><label for="boards">', $txt['PostPrefix_prefix_boards_require'], ':</label></span>
				</dt>
				<dd>
					<fieldset id="visible_boards">
						<legend>', $txt['PostPrefix_prefix_boards_require_desc'], '</legend>
						<ul class="padding floatleft">';

	foreach ($context['categories'] as $category)
	{
			echo '
							<li class="category">
								<a href="javascript:void(0);" onclick="selectBoards([', implode(', ', $category['child_ids']), '], \'PostPrefixRequire\'); return false;"><strong>', $category['name'], '</strong></a>
								<ul style="width:100%">';

		foreach ($category['boards'] as $board)
		{
				echo '
									<li class="board" style="margin-', $context['right_to_left'] ? 'right' : 'left', ': ', $board['child_level'], 'em;">
										<input type="checkbox" id="brd', $board['id'], '" name="requireboard[', $board['id'], ']" value="', $board['id'], '"', $board['require'] ? ' checked' : '', ' class="input_check"> <label for="requireboard[', $board['id'], ']">', $board['name'], '</label>
									</li>';
		}

		echo '
								</ul>
							</li>';
	}

		echo '
						</ul>
						<br class="clear"><br>
						<input type="checkbox" id="checkall_check" class="input_check" onclick="invertAll(this, this.form, \'requireboard\');"> <label for="checkall_check"><em>', $txt['check_all'], '</em></label>
					</fieldset>
					<a href="javascript:void(0);" onclick="document.getElementById(\'visible_boards\').style.display = \'block\'; document.getElementById(\'visible_boards_link\').style.display = \'none\'; return false;" id="visible_boards_link" style="display: none;">[ ', $txt['PostPrefix_select_visible_boards'], ' ]</a>
					<script><!-- // --><![CDATA[
						document.getElementById("visible_boards_link").style.display = "";
						document.getElementById("visible_boards").style.display = "none";
					// ]]></script>
				</dd>
			</dl>
			<input class="button floatleft" type="submit" value="', $txt['save'], '">
		</form>
	</div>';
}