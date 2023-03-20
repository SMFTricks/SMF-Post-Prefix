<?php

/**
 * @package SMF Post Prefix
 * @version 4.2
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2023, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

// Admin Tabs
$txt['PostPrefix_main'] = 'Topic Prefixes';
$txt['PostPrefix_tab_general'] = 'General';
$txt['PostPrefix_tab_general_desc'] = 'General information about Post Prefixes mod';
$txt['PostPrefix_tab_prefixes'] = 'Prefixes List';
$txt['PostPrefix_tab_prefixes_desc'] = 'The list of the available prefixes';
$txt['PostPrefix_tab_prefixes_add'] = 'Add Prefix';
$txt['PostPrefix_tab_prefixes_add_desc'] = 'Add a new prefix, you can set the name, color and select the allowed boards and groups.';
$txt['PostPrefix_tab_prefixes_edit_desc'] = 'Edit the current prefix, you can set the name, color and select the allowed boards and groups.';
$txt['PostPrefix_tab_options'] = 'Settings';
$txt['PostPrefix_tab_options_desc'] = 'Settings for Post Prefix mod.';
$txt['PostPrefix_tab_prefixes_edit'] = 'Edit Prefix';
$txt['PostPrefix_tab_permissions'] = 'Post Prefix permissions';

// Prefixes main
$txt['PostPrefix_no_prefixes'] = 'No prefixes have been added yet!';
$txt['PostPrefix_prefix_name'] = 'Name';
$txt['PostPrefix_prefix_modify'] = 'Modify';
$txt['PostPrefix_prefix_status'] = 'Status';
$txt['PostPrefix_prefix_groups'] = 'Groups';
$txt['PostPrefix_prefix_boards'] = 'Boards';
$txt['PostPrefix_prefix_id'] = 'ID';
$txt['prefix'] = 'Prefix';

// Add/Edit
$txt['PostPrefix_prefix_enable'] = 'Enable prefix?';
$txt['PostPrefix_prefix_color'] = 'Use color?';
$txt['PostPrefix_add_prefix'] = 'Add prefix';
$txt['PostPrefix_save_prefix'] = 'Save prefix';
$txt['PostPrefix_select_visible_groups'] = 'Show groups';
$txt['PostPrefix_prefix_groups_desc'] = 'Groups allowed to use the prefix.<br>Admin is not listed here because the group has access to all prefixes.';
$txt['PostPrefix_select_visible_boards'] = 'Show boards';
$txt['PostPrefix_prefix_boards_desc'] = 'Boards where the prefix can be used';
$txt['PostPrefix_use_bgcolor'] = 'Use color as background color?';
$txt['PostPrefix_invert_color'] = 'Invert color';
$txt['PostPrefix_invert_color_desc'] = 'Default color with background is white, inverted is black.';
$txt['PostPrefix_use_iconClass'] = 'Use Icon Class?';
$txt['PostPrefix_icon_class'] = 'Icon Class';
$txt['PostPrefix_prefix_added'] = 'The prefix was added successfully.';
$txt['PostPrefix_prefix_updated'] = 'The prefix was updated successfully.';
$txt['PostPrefix_prefix_delete_sure'] = 'Are you sure you want to delete the selected prefixes?';
$txt['PostPrefix_prefix_deleted'] = 'The prefix was deleted successfully.';

// Settings
$txt['PostPrefix_filter_settings'] = 'Prefix Filter Settings';
$txt['PostPrefix_enable_filter'] = 'Enable Filter Box';
$txt['PostPrefix_enable_filter_desc'] = 'Display a filter box inside the boards, allowing users to filter topics by prefix.';
$txt['PostPrefix_filter_boards'] = 'Boards to display filter';
$txt['PostPrefix_filter_boards_desc'] = 'Select the boards where you want to display the filter box';
$txt['PostPrefix_select_order'] = 'Order by';
$txt['PostPrefix_select_order_desc'] = 'Select default order for the prefixes. This applies to every list of prefixes.';
$txt['PostPrefix_prefix_boards_require'] = 'Require prefixes';
$txt['PostPrefix_prefix_boards_require_desc'] = 'Boards where a prefix is required when posting/editing a topic';
$txt['PostPrefix_post_selecttype'] = 'Selection style';
$txt['PostPrefix_post_selecttype_desc1'] = 'This is the style of prefix selection when posting/editing a new topic.';
$txt['PostPrefix_post_selecttype_desc2'] = '- Select: It will only display prefix names<br>- Radio: It will display each prefix with the corresponding style.';
$txt['PostPrefix_post_selecttype_select'] = 'Select';
$txt['PostPrefix_post_selecttype_radio'] = 'Radio';
$txt['PostPrefix_no_prefix_remove'] = 'Remove \'No prefix\' from the list when the board requires a prefix.';
$txt['PostPrefix_no_prefix_remove_desc'] = 'This setting is not recommended. Users that are careless will submit topics with the first prefix listed/selected.';

// Error
$txt['PostPrefix_error_noprefix'] = 'You need to specify a name for the prefix.';
$txt['PostPrefix_error_unable_tofind'] = 'Unable to find a prefix';
$txt['error_no_prefix'] = 'No prefix was selected.';
$txt['PostPrefix_empty_groups'] = 'No groups for this prefix.';
$txt['PostPrefix_empty_boards'] = 'No boards for this prefix.';

// Permissions
$txt['PostPrefix_permissions'] = 'Permissions for Prefixes';
$txt['permissiongroup_postprefix'] = 'Topic Prefixes';
$txt['permissionname_postprefix_manage'] = 'Manage Prefixes';
$txt['permissionhelp_postprefix_manage'] = 'If the user can manage prefixes.';
$txt['cannot_postprefix_manage'] = 'You\'re not allowed to manage the prefixes.';
$txt['permissionname_postprefix_set'] = 'Use Prefixes';
$txt['permissionhelp_postprefix_set'] = 'If the user can set prefixes.';
$txt['cannot_postprefix_set'] = 'You\'re not allowed to use prefixes.';

// Post
$txt['PostPrefix_select_prefix'] = 'Select Prefix';
$txt['PostPrefix_prefix'] = 'Prefix';
$txt['PostPrefix_prefix_none'] = '[No Prefix]';

// Filter by prefix
$txt['PostPrefix_filter'] = 'Filter by prefix';
$txt['PostPrefix_filter_noprefix'] = 'No prefix';
$txt['PostPrefix_filter_all'] = 'Show all topics';

// Display Prefixes
$txt['PostPrefix_prefix_display_settings'] = 'Displaying Prefixes';
$txt['PostPrefix_prefix_linktree'] = 'Add prefix to linktree';
$txt['PostPrefix_prefix_linktree_desc'] = 'When viewing a topic, include the prefix in the linktree.';
$txt['PostPrefix_prefix_boardindex'] = 'Enable prefixes in boards and last posts';
$txt['PostPrefix_prefix_boardindex_desc'] = 'It will display the prefixes in the boards and the recent posts in the info center.';
$txt['PostPrefix_prefix_all_msgs'] = 'Display prefix for replies';
$txt['PostPrefix_prefix_all_msgs_desc'] = 'It will display the corresponding prefix for each message and their topic. By default, only first posts display a prefix.';
$txt['PostPrefix_prefix_unread_page'] = 'Display prefixes in unread posts/topics page';
$txt['PostPrefix_prefix_recent_page'] = 'Display prefixes in recent posts page';
$txt['PostPrefix_prefix_not_cached'] = 'Prefixes on this page are never cached.';