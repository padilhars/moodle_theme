<?php
// Funções de integração do tema com o core do Moodle (SCSS e pluginfile).
defined('MOODLE_INTERNAL') || die();

/**
 * Retorna o conteúdo SCSS principal do tema UFPel.
 * Concatena: pre.scss + preset selecionado (do Boost ou enviado pelo admin) + post.scss.
 *
 * @param theme_config $theme Config do tema atual.
 * @return string SCSS final para compilação.
 */
function theme_ufpel_get_main_scss_content($theme): string {
    global $CFG;

    // 1) Carrega pre.scss do tema.
    $pre = theme_ufpel_get_pre_scss();

    // 2) Obtém o SCSS do preset selecionado nas configurações do tema.
    //    Respeita presets padrão do Boost e presets enviados via admin (filearea 'preset').
    $preset = theme_ufpel_get_selected_preset_scss($theme);

    // 3) Carrega post.scss do tema.
    $post = theme_ufpel_get_post_scss();

    // Concatena e retorna.
    return $pre . "\n" . $preset . "\n" . $post;
}

/**
 * Lê o SCSS do arquivo pre.scss deste tema.
 * @return string
 */
function theme_ufpel_get_pre_scss(): string {
    $path = __DIR__ . '/scss/pre.scss';
    return file_exists($path) ? file_get_contents($path) : '';
}

/**
 * Lê o SCSS do arquivo post.scss deste tema.
 * @return string
 */
function theme_ufpel_get_post_scss(): string {
    $path = __DIR__ . '/scss/post.scss';
    return file_exists($path) ? file_get_contents($path) : '';
}

/**
 * Obtém o SCSS do preset selecionado. Suporta:
 *  - Presets padrão do Boost (default.scss e plain.scss);
 *  - Presets enviados pelo admin para este tema no filearea 'preset';
 *  - Preset custom do próprio tema (scss/preset/ufpel.scss), se selecionado.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_ufpel_get_selected_preset_scss(\theme_config $theme): string {
    global $CFG;
    $scss = '';

    // Nome do preset selecionado (defined em settings.php).
    $presetname = isset($theme->settings->preset) ? $theme->settings->preset : null;

    // 1) Se for um dos presets internos do Boost.
    if ($presetname === 'default.scss' || $presetname === 'plain.scss') {
        // Caminhos do Boost (pai).
        $boostdir = $CFG->dirroot . '/theme/boost/scss/preset/';
        $candidate = $boostdir . $presetname;
        if (is_readable($candidate)) {
            $scss = file_get_contents($candidate);
        }
    }

    // 2) Se for o preset interno do próprio tema.
    if (!$scss && $presetname === 'ufpel.scss') {
        $candidate = __DIR__ . '/scss/preset/ufpel.scss';
        if (is_readable($candidate)) {
            $scss = file_get_contents($candidate);
        }
    }

    // 3) Se for um preset enviado via admin (stored_file na área 'preset').
    if (!$scss) {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'theme_ufpel', 'preset', 0, 'itemid, filepath, filename', false);
        foreach ($files as $file) {
            if ($file->get_filename() === $presetname) {
                $scss = $file->get_content();
                break;
            }
        }
    }

    // 4) Fallback para o default.scss do Boost, garantindo compat total.
    if (!$scss) {
        $candidate = $CFG->dirroot . '/theme/boost/scss/preset/default.scss';
        if (is_readable($candidate)) {
            $scss = file_get_contents($candidate);
        }
    }

    return $scss ?: '';
}

/**
 * Serve arquivos do plugin (por exemplo, presets enviados pelo admin).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea Deve ser 'preset'
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 */
function theme_ufpel_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    if ($filearea !== 'preset') {
        send_file_not_found();
    }

    require_login();

    $itemid = 0;
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'theme_ufpel', 'preset', $itemid, $filepath, $filename);
    if (!$file) {
        send_file_not_found();
    }

    // Envia o arquivo respeitando headers padrão do Moodle.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
