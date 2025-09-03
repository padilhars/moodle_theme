<?php
// Override do renderer de curso para o tema UFPel.
// Local: theme/ufpel/classes/output/core_course_renderer.php

namespace theme_ufpel\output;
defined('MOODLE_INTERNAL') || die();

use context_course;
use moodle_url;
use stdClass;

/**
 * Extende o renderer core_course_renderer do Moodle.
 * Quando a configuração 'usecustomcourseheader' do tema estiver ativa,
 * renderiza o template do tema com dados do curso.
 */
class core_course_renderer extends \core_course_renderer {

    /**
     * Sobrescreve o cabeçalho do curso.
     *
     * @param stdClass $course Objeto do curso
     * @return string HTML do cabeçalho
     */
    public function course_header(stdClass $course) {
        global $USER, $DB;

        // Carrega configurações do tema UFPel.
        $theme = \theme_config::load('ufpel');

        // Se a opção estiver desabilitada, delega ao cabeçalho padrão do core.
        if (empty($theme->settings->usecustomcourseheader)) {
            return parent::course_header($course);
        }

        // Contexto do curso (necessário para recuperar arquivos e usuários).
        $context = context_course::instance($course->id);

        // Dados que vamos passar para o template Mustache.
        $data = [];

        // --- Nome do curso (formatado corretamente de acordo com o contexto) ---
        $data['fullname'] = format_string($course->fullname, true, ['context' => $context]);

        // --- Imagem do curso ---
        // Tentamos as áreas mais comuns onde o Moodle armazena imagens de overview do curso.
        $fs = get_file_storage();
        $imageurl = null;

        // 1) Área 'overviewfiles' do componente 'course'
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'sortorder,filename', false);
        if (!empty($files)) {
            $file = reset($files);
            $imageurl = moodle_url::make_pluginfile_url(
                $context->id, 'course', 'overviewfiles', 0,
                $file->get_filepath(), $file->get_filename()
            )->out(false);
        }

        // 2) Fallback possível: algumas instâncias usam filearea 'summary' ou 'coursefiles'
        if (!$imageurl) {
            $fallbackareas = [
                ['component' => 'course', 'filearea' => 'summary'],
                ['component' => 'course', 'filearea' => 'coursefiles'],
            ];
            foreach ($fallbackareas as $fa) {
                $files = $fs->get_area_files($context->id, $fa['component'], $fa['filearea'], 0, 'sortorder,filename', false);
                if (!empty($files)) {
                    $file = reset($files);
                    $imageurl = moodle_url::make_pluginfile_url(
                        $context->id, $fa['component'], $fa['filearea'], 0,
                        $file->get_filepath(), $file->get_filename()
                    )->out(false);
                    break;
                }
            }
        }

        $data['imageurl'] = $imageurl ?: null;

        // --- Professor responsável (nome e foto) ---
        // Tentamos localizar os usuários com papel 'editingteacher' e depois 'teacher' como fallback.
        $teachername = '';
        $teacherpicturehtml = '';

        // busca role editingteacher -> teacher (shortname)
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        if (!$role) {
            $role = $DB->get_record('role', ['shortname' => 'teacher']);
        }
        if ($role) {
            // Obtem usuários com este papel nesse curso (sem paginação).
            $teachers = get_role_users($role->id, $context, 'u.*', 'u.lastname ASC', '', '', false);
            if (!empty($teachers)) {
                // Escolhe o primeiro como responsável principal.
                $teacher = reset($teachers);
                $teachername = fullname($teacher);

                // Gera HTML da imagem do perfil do professor usando o helper do renderer.
                // Note: user_picture retorna HTML com <img> (permitido) — inserimos no Mustache como HTML seguro (triple braces).
                // Evitamos construir URLs manualmente para respeitar controle de acesso e rendereização padrão.
                try {
                    $teacherpicturehtml = $this->output->user_picture($teacher, ['size' => 100, 'link' => false]);
                } catch (\Throwable $e) {
                    // Se algo falhar, mantemos vazio.
                    $teacherpicturehtml = '';
                }
            }
        }

        $data['teachername'] = $teachername;
        $data['teacherpicture'] = $teacherpicturehtml;

        // --- Total de participantes inscritos ---
        $data['participants'] = (int) count_enrolled_users($context);

        // --- Datas de início e término (formatadas se existirem) ---
        $data['startdate'] = !empty($course->startdate) ? userdate($course->startdate) : '';
        $data['enddate']   = !empty($course->enddate)   ? userdate($course->enddate)   : '';

        // --- Progresso do usuário atual no curso (percentual inteiro 0..100) ---
        $data['progress'] = null;
        if (isloggedin() && !isguestuser() && class_exists('\completion_info')) {
            $completion = new \completion_info($course);
            if ($completion->is_enabled()) {
                // Tenta API moderna \core_completion\progress
                try {
                    if (class_exists('\core_completion\progress')) {
                        // Alguns métodos aceitam course e userid
                        $data['progress'] = (int) \core_completion\progress::get_course_progress_percentage($course, $USER->id ?? null);
                    } else {
                        // Tenta método do objeto completion (se existir)
                        if (method_exists($completion, 'get_course_progress_percentage')) {
                            $data['progress'] = (int) $completion->get_course_progress_percentage($USER->id ?? null);
                        }
                    }
                } catch (\Throwable $e) {
                    $data['progress'] = null;
                }
            }
        }

        // Renderiza a partir do template Mustache do tema.
        // O template irá usar {{{teacherpicture}}} (HTML seguro) e demais campos.
        return $this->render_from_template('theme_ufpel/core/course_header', $data);
    }

    public function full_header() {
        global $COURSE, $PAGE;

        if ($PAGE->context && $PAGE->context->contextlevel == CONTEXT_COURSE && !empty($COURSE->id)) {
            return $this->course_header($COURSE);
        }

        return parent::full_header();
    }
}
