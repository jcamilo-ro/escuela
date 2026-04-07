<?php

declare(strict_types=1);

// phpMyAdmin usa esta base auxiliar para guardar favoritos, recientes
// y otras preferencias sin mezclar esa informacion con escuela_db.
$serverIndex = 1;

$controlDb = getenv('PMA_CONTROL_DB') ?: 'phpmyadmin';
$controlUser = getenv('PMA_CONTROL_USER') ?: '';
$controlPassword = getenv('PMA_CONTROL_PASSWORD') ?: '';

$cfg['Servers'][$serverIndex]['controlhost'] = 'db';
$cfg['Servers'][$serverIndex]['controlport'] = '3306';
$cfg['Servers'][$serverIndex]['controluser'] = $controlUser;
$cfg['Servers'][$serverIndex]['controlpass'] = $controlPassword;
$cfg['Servers'][$serverIndex]['pmadb'] = $controlDb;
$cfg['Servers'][$serverIndex]['bookmarktable'] = 'pma__bookmark';
$cfg['Servers'][$serverIndex]['relation'] = 'pma__relation';
$cfg['Servers'][$serverIndex]['table_info'] = 'pma__table_info';
$cfg['Servers'][$serverIndex]['table_coords'] = 'pma__table_coords';
$cfg['Servers'][$serverIndex]['pdf_pages'] = 'pma__pdf_pages';
$cfg['Servers'][$serverIndex]['column_info'] = 'pma__column_info';
$cfg['Servers'][$serverIndex]['history'] = 'pma__history';
$cfg['Servers'][$serverIndex]['table_uiprefs'] = 'pma__table_uiprefs';
$cfg['Servers'][$serverIndex]['tracking'] = 'pma__tracking';
$cfg['Servers'][$serverIndex]['userconfig'] = 'pma__userconfig';
$cfg['Servers'][$serverIndex]['recent'] = 'pma__recent';
$cfg['Servers'][$serverIndex]['favorite'] = 'pma__favorite';
$cfg['Servers'][$serverIndex]['users'] = 'pma__users';
$cfg['Servers'][$serverIndex]['usergroups'] = 'pma__usergroups';
$cfg['Servers'][$serverIndex]['navigationhiding'] = 'pma__navigationhiding';
$cfg['Servers'][$serverIndex]['savedsearches'] = 'pma__savedsearches';
$cfg['Servers'][$serverIndex]['central_columns'] = 'pma__central_columns';
$cfg['Servers'][$serverIndex]['designer_settings'] = 'pma__designer_settings';
$cfg['Servers'][$serverIndex]['export_templates'] = 'pma__export_templates';
