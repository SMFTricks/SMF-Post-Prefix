<?php

/**
 * @package SMF Post Prefix
 * @version 3.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2020, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

// Admin Tabs
$txt['PostPrefix_main'] = 'Konu Önekleri';
$txt['PostPrefix_tab_general'] = 'Genel';
$txt['PostPrefix_tab_general_desc'] = 'Konu Önekleri modu hakkında genel bilgiler';
$txt['PostPrefix_tab_prefixes'] = 'Önekler';
$txt['PostPrefix_tab_prefixes_desc'] = 'Kullanılabilir öneklerin listesi';
$txt['PostPrefix_tab_prefixes_add'] = 'Önek Ekle';
$txt['PostPrefix_tab_prefixes_add_desc'] = 'Yeni bir önek ekleyin, adı, rengi ayarlayabilir ve izin verilen panoları ve grupları seçebilirsiniz.';
$txt['PostPrefix_tab_prefixes_edit_desc'] = 'Geçerli öneki düzenleyin, adı, rengi ayarlayabilir ve izin verilen panoları ve grupları seçebilirsiniz.';
$txt['PostPrefix_tab_options'] = 'Ayarlar';
$txt['PostPrefix_tab_options_desc'] = 'Önek Sonrası modu için ayarlar.';
$txt['PostPrefix_tab_prefixes_edit'] = 'Öneki Düzenle';
$txt['PostPrefix_tab_require'] = 'Gerekli';
$txt['PostPrefix_tab_require_desc'] = 'Buradan önekin hangi panolarda gerekli olacağını seçebilirsiniz.';
$txt['PostPrefix_tab_permissions'] = 'Yazı önek izinleri';

// Prefixes main
$txt['PostPrefix_no_prefixes'] = 'Henüz önek eklenmedi!';
$txt['PostPrefix_prefix_name'] = 'Ad';
$txt['PostPrefix_prefix_modify'] = 'Değiştir';
$txt['PostPrefix_prefix_status'] = 'Durum';
$txt['PostPrefix_prefix_groups'] = 'Gruplar';
$txt['PostPrefix_prefix_boards'] = 'Panolar';
$txt['PostPrefix_prefix_id'] = 'Kimlik';
$txt['prefix'] = 'Önek';

// Add/Edit
$txt['PostPrefix_prefix_enable'] = 'Önek etkinleştirilsin mi?';
$txt['PostPrefix_prefix_color'] = 'Renk kullan';
$txt['PostPrefix_add_prefix'] = 'Önek ekle';
$txt['PostPrefix_save_prefix'] = 'Öneki kaydet';
$txt['PostPrefix_select_visible_groups'] = 'Grupları göster';
$txt['PostPrefix_prefix_groups_desc'] = 'Grupların öneki kullanmasına izin verilir';
$txt['PostPrefix_select_visible_boards'] = 'Panoları göster';
$txt['PostPrefix_prefix_boards_desc'] = 'Önekin kullanılabileceği panolar';
$txt['PostPrefix_use_bgcolor'] = 'Arka plan rengi olarak renk kullanılsın mı?';
$txt['PostPrefix_invert_color'] = 'Rengi ters çevir';
$txt['PostPrefix_invert_color_desc'] = 'Arka planlı varsayılan renk beyaz, ters çevrilmiş renk siyahtır.';
$txt['PostPrefix_use_icon'] = 'Bunun yerine simge kullanılsın mı?';
$txt['PostPrefix_icon_url'] = 'Simge URLsi';
$txt['PostPrefix_prefix_added'] = 'Önek başarıyla eklendi.';
$txt['PostPrefix_prefix_updated'] = 'Önek başarıyla güncellendi.';
$txt['PostPrefix_prefix_delete_sure'] = 'Seçilen önekleri silmek istediğinizden emin misiniz?';
$txt['PostPrefix_prefix_deleted'] = 'Seçilen önekler başarıyla silindi.';

// Settings
$txt['PostPrefix_enable_filter'] = 'Post Öneki Filtrele';
$txt['PostPrefix_enable_filter_desc'] = 'Konuları önek ile filtrelemek için bir kutu görüntülemek için bu seçeneği etkinleştirin.';
$txt['PostPrefix_filter_boards'] = 'Filtreyi görüntülemek için panolar';
$txt['PostPrefix_filter_boards_desc'] = 'Filtre kutusunu görüntülemek istediğiniz panoları seçin';
$txt['PostPrefix_select_order'] = 'Sıralama ölçütü';
$txt['PostPrefix_select_order_desc'] = 'Her yerde varsayılan ön ek sırasını seçin';
$txt['PostPrefix_prefix_boards_require'] = 'Önek gerektir';
$txt['PostPrefix_prefix_boards_require_desc'] = 'Konu gönderirken/düzenlerken bir ön ekin gerekli olduğu panolar';

// Error
$txt['PostPrefix_error_noprefix'] = 'Önek için bir ad belirtmeniz gerekiyor.';
$txt['PostPrefix_error_unable_tofind'] = 'Önek bulunamadı';
$txt['error_no_prefix'] = 'Önek seçilmedi.';
$txt['PostPrefix_empty_groups'] = 'Bu önek için grup yok.';
$txt['PostPrefix_empty_boards'] = 'Bu önek için pano yok.';

// Permissions
$txt['PostPrefix_permissions'] = 'Konu Önekleri için İzinler';
$txt['permissiongroup_postprefix_manage'] = 'Önekleri Yönet';
$txt['permissionname_postprefix_manage'] = 'Önekleri Yönet';
$txt['groups_postprefix_manage'] = 'Önekleri Yönet';
$txt['permissionhelp_postprefix_manage'] = 'Kullanıcı önekleri yönetebiliyorsa.';
$txt['permissionhelp_groups_postprefix_manage'] = 'Kullanıcı önekleri yönetebiliyorsa.';
$txt['cannot_postprefix_manage'] = 'Önekleri yönetme yetkiniz yok.';
$txt['permissiongroup_postprefix_set'] = 'Önek Kullan';
$txt['permissionname_postprefix_set'] = 'Önek Kullan';
$txt['groups_postprefix_set'] = 'Önek Kullan';
$txt['permissionhelp_postprefix_set'] = 'Kullanıcı önekleri ayarlayabilirse.';
$txt['permissionhelp_groups_postprefix_set'] = 'Kullanıcı önekleri ayarlayabilirse.';
$txt['cannot_spostprefix_set'] = 'Önek kullanmanıza izin verilmez.';

// Post
$txt['PostPrefix_select_prefix'] = 'Önek Seç';
$txt['PostPrefix_prefix'] = 'Önek';
$txt['PostPrefix_prefix_none'] = '[Önek Yok]';

// Filter by prefix
$txt['PostPrefix_filter'] = 'Önekle göre filtrele';
$txt['PostPrefix_filter_noprefix'] = 'Önek yok';
$txt['PostPrefix_filter_all'] = 'Tüm konu öneklerini göster';