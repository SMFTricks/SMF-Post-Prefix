<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

// Admin Tabs
$txt['PostPrefix_main'] = 'Префиксы тем';
$txt['PostPrefix_tab_general'] = 'Общие';
$txt['PostPrefix_tab_general_desc'] = 'Общая информация о моде Post Prefix';
$txt['PostPrefix_tab_prefixes'] = 'Список';
$txt['PostPrefix_tab_prefixes_desc'] = 'Список доступных префиксов';
$txt['PostPrefix_tab_prefixes_add'] = 'Добавить префикс';
$txt['PostPrefix_tab_prefixes_add_desc'] = 'Добавьте новый префикс: вы можете задать название, цвет и выбрать разрешенные разделы и группы.';
$txt['PostPrefix_tab_prefixes_edit_desc'] = 'Редактирование текущего префикса: вы можете задать название, цвет и выбрать разрешенные разделы и группы.';
$txt['PostPrefix_tab_options'] = 'Настройки';
$txt['PostPrefix_tab_options_desc'] = 'Настройки мода Post Prefix.';
$txt['PostPrefix_tab_prefixes_edit'] = 'Редактировать';
$txt['PostPrefix_tab_require'] = 'Обязателен';
$txt['PostPrefix_tab_require_desc'] = 'Здесь можно выбрать разделы, в которых префикс будет обязательным.';
$txt['PostPrefix_tab_permissions'] = 'Права доступа Post Prefix';

// Prefixes main
$txt['PostPrefix_no_prefixes'] = 'Префиксы ещё не добавлены!';
$txt['PostPrefix_prefix_name'] = 'Название';
$txt['PostPrefix_prefix_modify'] = 'Изменить';
$txt['PostPrefix_prefix_status'] = 'Статус';
$txt['PostPrefix_prefix_groups'] = 'Группы';
$txt['PostPrefix_prefix_boards'] = 'Разделы';
$txt['PostPrefix_prefix_id'] = '№';
$txt['prefix'] = 'Префикс';

// Add/Edit
$txt['PostPrefix_prefix_enable'] = 'Включить префикс?';
$txt['PostPrefix_prefix_color'] = 'Использовать цвет?';
$txt['PostPrefix_add_prefix'] = 'Добавить префикс';
$txt['PostPrefix_save_prefix'] = 'Сохранить префикс';
$txt['PostPrefix_select_visible_groups'] = 'Разрешённые группы';
$txt['PostPrefix_prefix_groups_desc'] = 'Группы, которым можно использовать этот префикс';
$txt['PostPrefix_select_visible_boards'] = 'Разрешённые разделы';
$txt['PostPrefix_prefix_boards_desc'] = 'Разделы, в которых можно использовать этот префикс';
$txt['PostPrefix_use_bgcolor'] = 'Использовать цвет в качестве фонового?';
$txt['PostPrefix_invert_color'] = 'Инвертировать цвет';
$txt['PostPrefix_invert_color_desc'] = 'Цвет фона по умолчанию - белый, инвертированного - черный.';
$txt['PostPrefix_use_iconClass'] = '';
$txt['PostPrefix_icon_class'] = '';
$txt['PostPrefix_prefix_added'] = 'Префикс был успешно добавлен.';
$txt['PostPrefix_prefix_updated'] = 'Префикс был успешно обновлён.';
$txt['PostPrefix_prefix_delete_sure'] = 'Хотите удалить выбранные префиксы?';
$txt['PostPrefix_prefix_deleted'] = 'Выбранные префиксы были успешно удалены.';

// Settings
$txt['PostPrefix_enable_filter'] = 'Фильтр по префиксам';
$txt['PostPrefix_enable_filter_desc'] = 'Включите эту опцию, чтобы отображать блок с фильтром тем по префиксу.';
$txt['PostPrefix_filter_boards'] = 'Разделы для отображения фильтра';
$txt['PostPrefix_filter_boards_desc'] = 'Выберите разделы, в которых будет отображаться блок с фильтром';
$txt['PostPrefix_select_order'] = 'Сортировка';
$txt['PostPrefix_select_order_desc'] = 'Выберите сортировку по умолчанию';
$txt['PostPrefix_prefix_boards_require'] = 'Обязательные префиксы';
$txt['PostPrefix_prefix_boards_require_desc'] = 'Разделы, где потребуется указывать префикс при размещении/редактировании тем';
$txt['PostPrefix_post_selecttype'] = '';
$txt['PostPrefix_post_selecttype_desc1'] = '';
$txt['PostPrefix_post_selecttype_desc2'] = '';
$txt['PostPrefix_post_selecttype_select'] = '';
$txt['PostPrefix_post_selecttype_radio'] = '';
$txt['PostPrefix_prefix_linktree'] = '';
$txt['PostPrefix_prefix_linktree_desc'] = '';
$txt['PostPrefix_prefix_boardindex'] = '';
$txt['PostPrefix_prefix_boardindex_desc'] = '';
$txt['PostPrefix_prefix_all_msgs'] = '';
$txt['PostPrefix_prefix_all_msgs_desc'] = '';
$txt['PostPrefix_prefix_recent_page'] = '';
$txt['PostPrefix_prefix_recent_page_desc'] = '';

// Error
$txt['PostPrefix_error_noprefix'] = 'Нужно указать название префикса.';
$txt['PostPrefix_error_unable_tofind'] = 'Невозможно найти префикс';
$txt['error_no_prefix'] = 'Префикс не был выбран.';
$txt['PostPrefix_empty_groups'] = 'Нет групп с этим префиксом.';
$txt['PostPrefix_empty_boards'] = 'Нет разделов с этим префиксом.';

// Permissions
$txt['PostPrefix_permissions'] = '';
$txt['permissionname_postprefix_manage'] = 'Управление префиксами';
$txt['permissionhelp_postprefix_manage'] = 'Кто может управлять префиксами.';
$txt['cannot_postprefix_manage'] = 'Вам не разрешено управлять префиксами.';
$txt['permissionname_postprefix_set'] = 'Использование префиксов';
$txt['permissionhelp_postprefix_set'] = 'Кто может устанавливать префиксы для тем.';
$txt['cannot_postprefix_set'] = '';

// Post
$txt['PostPrefix_select_prefix'] = 'Выбрать префикс';
$txt['PostPrefix_prefix'] = 'Префикс';
$txt['PostPrefix_prefix_none'] = '[Нет префикса]';

// Filter by prefix
$txt['PostPrefix_filter'] = 'Фильтр по префиксам';
$txt['PostPrefix_filter_noprefix'] = 'Без префикса';
$txt['PostPrefix_filter_all'] = 'Показать все темы';