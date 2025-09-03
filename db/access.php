<?php
// Define capabilities do tema. Incluímos uma capability mínima para configuração do tema.
// Isso NÃO adiciona recursos novos visíveis ao usuário; apenas permite controle de acesso.
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'theme/ufpel:configure' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW, // Gestores podem configurar o tema por padrão.
        ],
        // Permite clonagem pelo criador de funções.
        'clonepermissionsfrom' => 'moodle/site:config',
    ],
];
