<?php
// Funções de integração do tema com o core do Moodle 5.x (SCSS e pluginfile).
defined('MOODLE_INTERNAL') || die();

/**
 * Retorna o conteúdo SCSS principal do tema UFPel.
 * Concatena: pre.scss + preset selecionado + post.scss.
 *
 * @param theme_config $theme Configuração do tema atual
 * @return string SCSS final para compilação
 */
function theme_ufpel_get_main_scss_content(\theme_config $theme): string {
    // Carrega arquivos SCSS na ordem correta
    $pre = theme_ufpel_get_pre_scss();
    $preset = theme_ufpel_get_selected_preset_scss($theme);
    $post = theme_ufpel_get_post_scss();

    // Concatena com separadores apropriados
    return $pre . "\n" . $preset . "\n" . $post;
}

/**
 * Lê o SCSS do arquivo pre.scss do tema.
 *
 * @return string Conteúdo do pre.scss ou string vazia se não existir
 */
function theme_ufpel_get_pre_scss(): string {
    $path = __DIR__ . '/scss/pre.scss';
    return file_exists($path) ? file_get_contents($path) : '';
}

/**
 * Lê o SCSS do arquivo post.scss do tema.
 *
 * @return string Conteúdo do post.scss ou string vazia se não existir
 */
function theme_ufpel_get_post_scss(): string {
    $path = __DIR__ . '/scss/post.scss';
    return file_exists($path) ? file_get_contents($path) : '';
}

/**
 * Obtém o SCSS do preset selecionado. Suporta:
 * - Presets padrão do Boost (default.scss e plain.scss)
 * - Presets enviados pelo admin via filearea 'preset'
 * - Preset customizado do tema (scss/preset/ufpel.scss)
 *
 * @param theme_config $theme Configuração do tema
 * @return string Conteúdo SCSS do preset selecionado
 */
function theme_ufpel_get_selected_preset_scss(\theme_config $theme): string {
    global $CFG;
    
    $scss = '';
    $presetname = $theme->settings->preset ?? 'default.scss';

    // 1. Presets internos do Boost
    if (in_array($presetname, ['default.scss', 'plain.scss'])) {
        $boostdir = $CFG->dirroot . '/theme/boost/scss/preset/';
        $presetpath = $boostdir . $presetname;
        
        if (is_readable($presetpath)) {
            $scss = file_get_contents($presetpath);
        }
    }

    // 2. Preset interno do tema UFPel
    if (empty($scss) && $presetname === 'ufpel.scss') {
        $presetpath = __DIR__ . '/scss/preset/ufpel.scss';
        
        if (is_readable($presetpath)) {
            $scss = file_get_contents($presetpath);
        }
    }

    // 3. Presets enviados via admin (stored_file)
    if (empty($scss)) {
        $scss = theme_ufpel_get_uploaded_preset_scss($presetname);
    }

    // 4. Fallback seguro para default.scss do Boost
    if (empty($scss)) {
        $fallbackpath = $CFG->dirroot . '/theme/boost/scss/preset/default.scss';
        
        if (is_readable($fallbackpath)) {
            $scss = file_get_contents($fallbackpath);
        }
    }

    return $scss;
}

/**
 * Busca preset SCSS enviado via admin na filearea 'preset'.
 *
 * @param string $filename Nome do arquivo preset
 * @return string Conteúdo do arquivo ou string vazia
 */
function theme_ufpel_get_uploaded_preset_scss(string $filename): string {
    if (empty($filename)) {
        return '';
    }

    $context = \context_system::instance();
    $fs = get_file_storage();
    
    $files = $fs->get_area_files(
        $context->id, 
        'theme_ufpel', 
        'preset', 
        0, 
        'itemid, filepath, filename', 
        false
    );

    foreach ($files as $file) {
        if ($file->get_filename() === $filename) {
            return $file->get_content();
        }
    }

    return '';
}

/**
 * Serve arquivos do plugin (presets enviados pelo admin).
 *
 * @param stdClass $course Objeto do curso
 * @param stdClass $cm Módulo do curso
 * @param context $context Contexto
 * @param string $filearea Área do arquivo (deve ser 'preset')
 * @param array $args Argumentos do arquivo
 * @param bool $forcedownload Forçar download
 * @param array $options Opções adicionais
 * @return void
 */
function theme_ufpel_pluginfile(
    $course, 
    $cm, 
    $context, 
    string $filearea, 
    array $args, 
    bool $forcedownload, 
    array $options = []
): void {
    // Validações de segurança
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea !== 'preset') {
        send_file_not_found();
    }

    // Requer login para acessar arquivos
    require_login();

    // Validação de capacidades
    if (!has_capability('theme/ufpel:configure', $context)) {
        send_file_not_found();
    }

    // Extrai informações do arquivo dos argumentos
    $itemid = 0;
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    // Busca o arquivo no sistema de arquivos do Moodle
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'theme_ufpel', 'preset', $itemid, $filepath, $filename);

    if (!$file) {
        send_file_not_found();
    }

    // Envia o arquivo com headers apropriados
    send_stored_file($file, 0, 0, $forcedownload, $options);
}