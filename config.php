<?php
// Configuração principal do tema UFPel para Moodle 5.x
// Herda completamente do Boost mantendo compatibilidade total
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'ufpel';

// Herança total do tema Boost
$THEME->parents = ['boost'];

// Não adicionamos layouts próprios para manter compatibilidade com Boost
$THEME->layouts = [];

// Configurações de otimização e recursos modernos do Moodle 5.x
$THEME->supportscssoptimisation = true;
$THEME->usescourseindex = true;
$THEME->usescourseindextooltips = true;  // Suporte a tooltips no índice do curso
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

// Callback principal para composição do SCSS (pre + preset + post)
$THEME->scss = function($theme) {
    return theme_ufpel_get_main_scss_content($theme);
};

// Configurações de recursos avançados
$THEME->editor_scss = null;
$THEME->extrascsscallback = null;
$THEME->prescsscallback = null;

// Arrays vazios para manter herança completa do Boost
$THEME->javascripts = [];
$THEME->sheets = [];

// Factory de renderers com suporte a override seletivo
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Configurações de cache e performance para Moodle 5.x
$THEME->enable_dock = false;
$THEME->yuicssmodules = [];
$THEME->blockrtlmanipulations = [];

// Suporte completo a recursos modernos
$THEME->haseditswitch = true;
$THEME->usefallback = true;