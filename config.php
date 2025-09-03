<?php
// Configuração principal do tema. Herda completamente do Boost sem alterar layouts/recursos.
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'ufpel';

// Herdar todo comportamento do Boost.
$THEME->parents = ['boost'];

// Não adicionamos layouts próprios para não alterar comportamento do Boost.
$THEME->layouts = []; // Vazio: usa layouts do pai (boost).

// Suporte a Mustache/JS/SCSS conforme padrão do Moodle.
$THEME->supportscssoptimisation = true;

// Função de callback (em lib.php) que compõe o SCSS principal (pre + preset + post).
$THEME->scss = function($theme) {
    return theme_ufpel_get_main_scss_content($theme);
};

// Áreas de arquivos do plugin (para upload de presets via admin).
$THEME->editor_scss = null;
$THEME->usescourseindex = true;
$THEME->extrascsscallback = null;

// Definições de folhas e libs.
$THEME->javascripts = [];      // Não adicionamos JS próprio (sem funcionalidades extras).
$THEME->sheets      = [];      // SCSS será compilado para CSS final via pipeline padrão.

// Fallback de renderers para o Boost (nenhum override).
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
