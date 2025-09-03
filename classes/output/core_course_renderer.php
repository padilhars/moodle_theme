<?php
// Override do renderer de curso para o tema UFPel.
// Local: theme/ufpel/classes/output/core_course_renderer.php

namespace theme_ufpel\output;

defined('MOODLE_INTERNAL') || die();

use context_course;
use moodle_url;
use stdClass;
use core_completion\progress;
use completion_info;

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
    public function course_header(stdClass $course): string {
        global $USER, $DB;

        // Carrega configurações do tema UFPel
        $theme = \theme_config::load('ufpel');

        // Se a opção estiver desabilitada, delega ao cabeçalho padrão do core
        if (empty($theme->settings->usecustomcourseheader)) {
            return parent::course_header($course);
        }

        // Contexto do curso
        $context = context_course::instance($course->id);

        // Dados que vamos passar para o template Mustache
        $data = [];

        // Nome do curso formatado
        $data['fullname'] = format_string($course->fullname, true, ['context' => $context]);

        // Imagem do curso
        $data['imageurl'] = $this->get_course_image($course, $context);

        // Professor responsável
        $teacherdata = $this->get_course_teacher($course, $context);
        $data['teachername'] = $teacherdata['name'];
        $data['teacherpicture'] = $teacherdata['picture'];

        // Total de participantes inscritos
        $data['participants'] = $this->get_course_participants_count($context);

        // Datas de início e término
        $data['startdate'] = !empty($course->startdate) ? userdate($course->startdate, get_string('strftimedatefullshort')) : '';
        $data['enddate'] = !empty($course->enddate) ? userdate($course->enddate, get_string('strftimedatefullshort')) : '';

        // Progresso do usuário atual no curso
        $data['progress'] = $this->get_course_progress($course);

        // Renderiza o template
        return $this->render_from_template('theme_ufpel/core/course_header', $data);
    }

    /**
     * Override do full_header para aplicar o cabeçalho customizado nas páginas de curso.
     *
     * @return string
     */
    public function full_header(): string {
        global $COURSE, $PAGE;

        // Verifica se estamos em uma página de curso
        if ($PAGE->context && 
            $PAGE->context->contextlevel == CONTEXT_COURSE && 
            !empty($COURSE->id) && 
            $COURSE->id != SITEID) {
            return $this->course_header($COURSE);
        }

        return parent::full_header();
    }

    /**
     * Obtém a imagem do curso.
     *
     * @param stdClass $course
     * @param \context_course $context
     * @return string|null
     */
    private function get_course_image(stdClass $course, \context_course $context): ?string {
        $fs = get_file_storage();
        
        // Áreas onde o Moodle armazena imagens de curso
        $fileareas = [
            ['component' => 'course', 'filearea' => 'overviewfiles'],
            ['component' => 'course', 'filearea' => 'summary'],
            ['component' => 'course', 'filearea' => 'coursefiles']
        ];

        foreach ($fileareas as $area) {
            $files = $fs->get_area_files(
                $context->id, 
                $area['component'], 
                $area['filearea'], 
                0, 
                'sortorder,filename', 
                false
            );
            
            if (!empty($files)) {
                $file = reset($files);
                // Verifica se é uma imagem
                if ($file->is_valid_image()) {
                    return moodle_url::make_pluginfile_url(
                        $context->id,
                        $area['component'],
                        $area['filearea'],
                        0,
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out(false);
                }
            }
        }

        return null;
    }

    /**
     * Obtém dados do professor responsável pelo curso.
     *
     * @param stdClass $course
     * @param \context_course $context
     * @return array
     */
    private function get_course_teacher(stdClass $course, \context_course $context): array {
        global $DB;

        $teachername = '';
        $teacherpicture = '';

        // Busca roles de professor (editingteacher primeiro, depois teacher)
        $roles = ['editingteacher', 'teacher'];
        
        foreach ($roles as $roleshortname) {
            $role = $DB->get_record('role', ['shortname' => $roleshortname]);
            if ($role) {
                $teachers = get_role_users(
                    $role->id, 
                    $context, 
                    'u.*', 
                    'u.lastname ASC, u.firstname ASC', 
                    '', 
                    '', 
                    false
                );
                
                if (!empty($teachers)) {
                    $teacher = reset($teachers);
                    $teachername = fullname($teacher);
                    
                    // Gera HTML da foto do professor
                    try {
                        $teacherpicture = $this->output->user_picture($teacher, [
                            'size' => 100, 
                            'link' => false,
                            'courseid' => $course->id
                        ]);
                    } catch (\Throwable $e) {
                        $teacherpicture = '';
                    }
                    break;
                }
            }
        }

        return [
            'name' => $teachername,
            'picture' => $teacherpicture
        ];
    }

    /**
     * Obtém o número de participantes do curso.
     *
     * @param \context_course $context
     * @return int
     */
    private function get_course_participants_count(\context_course $context): int {
        return count_enrolled_users($context);
    }

    /**
     * Obtém o progresso do usuário atual no curso.
     *
     * @param stdClass $course
     * @return int|null
     */
    private function get_course_progress(stdClass $course): ?int {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return null;
        }

        try {
            $completion = new completion_info($course);
            if (!$completion->is_enabled()) {
                return null;
            }

            // Usa a API moderna do Moodle 5.x
            if (class_exists('\core_completion\progress')) {
                $percentage = progress::get_course_progress_percentage($course, $USER->id);
                return $percentage !== null ? (int) round($percentage) : null;
            }

            // Fallback para métodos anteriores se necessário
            $modules = $completion->get_activities();
            if (empty($modules)) {
                return null;
            }

            $completed = 0;
            $total = 0;

            foreach ($modules as $module) {
                $data = $completion->get_data($module, false, $USER->id);
                $total++;
                if ($data->completionstate != COMPLETION_INCOMPLETE) {
                    $completed++;
                }
            }

            return $total > 0 ? (int) round(($completed / $total) * 100) : null;

        } catch (\Throwable $e) {
            // Log do erro para depuração se necessário
            debugging('Error calculating course progress: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }
}