<?php
// Configurações do tema no administrativo (sem adicionar novas features além do Boost).
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $page = new admin_settingpage('themesettingufpel', get_string('pluginname', 'theme_ufpel'),
        'theme/ufpel:configure');

    // Título/descrição padrão
    $page->add(new admin_setting_heading('theme_ufpel_general', 
        get_string('generalsettings', 'theme_ufpel'), ''));

    // Upload de presets SCSS (filearea 'preset')
    $name = 'theme_ufpel/presetfiles';
    $title = get_string('presetfiles', 'theme_ufpel');
    $description = get_string('presetfiles_desc', 'theme_ufpel');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        ['maxfiles' => -1, 'accepted_types' => ['.scss']]);
    $page->add($setting);

    // Seleção de preset: inclui defaults do Boost + custom do tema + enviados
    $name = 'theme_ufpel/preset';
    $title = get_string('preset', 'theme_ufpel');
    $description = get_string('preset_desc', 'theme_ufpel');

    $choices = [];
    // Defaults do Boost
    $choices['default.scss'] = 'default.scss';
    $choices['plain.scss'] = 'plain.scss';
    // Preset do próprio tema
    $choices['ufpel.scss'] = 'ufpel.scss';

    // Arquivos enviados
    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_ufpel', 'preset', 0, 'filename', false);
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }

    $setting = new admin_setting_configselect($name, $title, $description, 'default.scss', $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Configuração do cabeçalho customizado de curso
    $name = 'theme_ufpel/usecustomcourseheader';
    $title = get_string('usecustomcourseheader', 'theme_ufpel');
    $description = get_string('usecustomcourseheader_desc', 'theme_ufpel');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Registra a página nas configurações do admin
    $ADMIN->add('themes', $page);
}