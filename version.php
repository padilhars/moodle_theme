<?php
// Este arquivo define metadados do plugin de tema UFPel para Moodle 5.x.
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_ufpel';           // Nome do componente do plugin
$plugin->version   = 2025090200;              // Versão do plugin (AAAAMMDDHH)
$plugin->requires  = 2024042200;              // Requer pelo menos Moodle 5.0.0 
$plugin->supported = [500, 500];              // Suporta Moodle 5.0.x
$plugin->maturity  = MATURITY_STABLE;         // Maturidade do plugin
$plugin->release   = '1.0.0';                 // Release do plugin

// Dependências (tema pai)
$plugin->dependencies = [
    'theme_boost' => 2024042200,              // Requer tema Boost do Moodle 5.0+
];