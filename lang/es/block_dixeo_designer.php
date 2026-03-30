<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_dixeo_designer'
 *
 * @package    block_dixeo_designer
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2025 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['attachfile'] = 'Adjuntar un documento fuente';
$string['blocktitle'] = 'Diseñador de Cursos Dixeo';
$string['toggle_tooltip_hide'] = 'Ocultar el bloque de generación';
$string['toggle_tooltip_show'] = 'Mostrar el bloque de generación';
$string['designacourse'] = 'Diseñar un curso';
$string['categoryname'] = 'Categoría para cursos creados';
$string['categoryname_desc'] = 'Introduce el nombre de la categoría de curso de nivel superior donde se colocarán los cursos creados con el Diseñador de Cursos Dixeo. Se creará la categoría si no existe.';
$string['coursetemplate'] = 'Plantilla pedagógica predeterminada';
$string['coursetemplate_desc'] = 'Seleccione la plantilla pedagógica predeterminada utilizada por el Diseñador de Cursos Dixeo.';
$string['coursetemplate_none'] = 'Ninguna';
$string['course_generated'] = '¡Tu curso «<b> {$a} </b>» se ha generado correctamente!';
$string['default_categoryname'] = 'Cursos Dixeo';

// Certificado (finalización) — alineado con local_edai.
$string['certificate_settings'] = 'Generación de certificados';
$string['certificate_settings_help'] = '';
$string['certificate_unavailable'] = 'La generación de certificados no está disponible. Por favor, instala los plugins Course Certificate (mod_coursecertificate) y Tool Certificate (tool_certificate).';
$string['certificate_generation'] = 'Habilitar generación de certificados';
$string['certificate_generation_description'] = 'Habilita o deshabilita la generación de certificados de finalización de curso.';
$string['certificate_template'] = 'Plantilla de certificado';
$string['certificate_template_description'] = 'Selecciona la plantilla a utilizar al generar el certificado del curso.';
$string['certificate_location'] = 'Ubicación del certificado';
$string['certificate_location_description'] = 'Selecciona dónde se mostrará el certificado.';
$string['summarysection'] = 'En el resumen del curso';
$string['lastsection'] = 'Después de la última sección';
$string['certificate_section'] = 'Certificado de logro';
$string['certificate_section_intro'] = 'Recupera tu certificado de logro una vez completado el curso.';
$string['certificate_name'] = 'Certificado de logro';

// Publicación LTI (finalización).
$string['lti_publication'] = 'Publicación LTI';
$string['lti_publication_desc'] = 'Si está activada, se añade un método de matriculación «Publicar como herramienta LTI» a los cursos nuevos. Requiere el plugin enrol_lti activado.';
$string['lti_publication_enabled'] = 'Añadir matriculación LTI';
$string['lti_publication_enabled_desc'] = 'Si está activada, se añadirá una instancia de matriculación LTI 1.3 a los cursos nuevos.';
$string['lti_maxenrolled'] = 'Máximo de usuarios matriculados';
$string['lti_maxenrolled_desc'] = 'Número máximo de usuarios que pueden acceder mediante esta herramienta LTI. 0 = sin límite.';
$string['lti_maildisplay'] = 'Visibilidad del correo electrónico';
$string['lti_maildisplay_desc'] = 'Preferencia de visualización del correo para las cuentas creadas en los lanzamientos LTI (mismas opciones que el formulario de matriculación LTI estándar).';
$string['lti_preferred_language'] = 'Idioma preferido';
$string['lti_preferred_language_desc'] = 'Idioma de los usuarios aprovisionados vía LTI. «Igual que el idioma del curso» usa el idioma del curso, o el predeterminado del sitio si el curso no tiene idioma.';
$string['lti_lang_same_as_course'] = 'Igual que el idioma del curso';
$string['lti_city'] = 'Ciudad';
$string['lti_city_desc'] = 'Ciudad predeterminada para usuarios aprovisionados (opcional). Igual que el campo de ciudad por defecto del formulario de matriculación LTI estándar.';
$string['lti_country'] = 'País';
$string['lti_country_desc'] = 'País predeterminado para usuarios aprovisionados (opcional). Misma lista de países que el formulario de matriculación LTI estándar.';

$string['dixeo_designer:addinstance'] = 'Agregar un bloque Diseñador de Cursos Dixeo';
$string['dixeo_designer:myaddinstance'] = 'Agregar un nuevo bloque Diseñador de Cursos Dixeo a mi panel';
$string['dixeo_designer:create'] = 'Crear cursos con el Diseñador de Cursos Dixeo';
$string['dixeo_designer:manage'] = 'Gestionar el Diseñador de Cursos Dixeo';
$string['manage'] = 'Gestionar el Diseñador de Cursos Dixeo';
$string['draganddrop'] = 'Arrastra y suelta tus archivos para subirlos';
$string['designer_unknown_error'] = 'Error desconocido';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['error_title'] = '¡Vaya!';
$string['filetoolarge'] = 'El archivo es demasiado grande. Por favor, sube un archivo menor de 20MB.';
$string['filetypeinvalid'] = 'El tipo de archivo {$a} no es compatible. Extensiones soportadas: .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Generar un nuevo curso';
$string['generate_course'] = 'Generar';
$string['generate_course_tooltip'] = 'Generar curso ahora';
$string['generate_structure_btn'] = 'Generar';
$string['generate_structure_tooltip'] = 'Generar estructura del curso';
$string['regenerate_structure_tooltip'] = 'Regenerar la estructura del curso';
$string['generating_course'] = 'Por favor, espera mientras preparamos tu curso. Este proceso puede tardar unos minutos...';
$string['heading'] = '¿Qué quieres enseñar hoy?';
$string['heading2'] = '¡Estamos construyendo tu curso!';
$string['invalidinput'] = 'Información requerida.';
$string['myaddinstance'] = 'Agregar un nuevo bloque Diseñador de Cursos Dixeo a mi panel';
$string['pluginname'] = 'Diseñador de Cursos Dixeo';
$string['privacy:metadata:email'] = 'La dirección de correo electrónico del usuario que accede al Consumidor LTI';
$string['privacy:metadata:externalpurpose'] = 'El Consumidor LTI proporciona información de usuario y contexto al Proveedor de Herramientas LTI.';
$string['privacy:metadata:firstname'] = 'El nombre del usuario que accede al Consumidor LTI';
$string['privacy:metadata:lastname'] = 'El apellido del usuario que accede al Consumidor LTI';
$string['privacy:metadata:userid'] = 'El ID del usuario que accede al Consumidor LTI';
$string['prompt_placeholder'] = 'Introduce el curso que deseas generar: tema, número de secciones y cuestionario si es necesario.';
$string['removefile'] = 'Eliminar archivo';
$string['step_uploading_files'] = 'Procesando archivos';
$string['step_generating_structure'] = 'Generando estructura';
$string['step_generating_content'] = 'Generando contenido';
$string['step_finalizing_details'] = 'Finalizando detalles';
$string['totalsize'] = '<b>Tamaño total:</b> {$a}';
$string['totaltoolarge'] = 'El tamaño total de los archivos supera el límite de 50MB. Sube archivos más pequeños o elimina uno para continuar.';
$string['uploaderror'] = 'Error al subir el archivo.';
$string['uploading_files'] = 'Subiendo…';
$string['step_uploading_files_count'] = 'Procesando archivos ({$a->current}/{$a->total})';
$string['step_generating_content_count'] = 'Generando contenido ({$a->current}/{$a->total})';
$string['step_processing_prompt'] = 'Procesando la consigna';
$string['step_preparing_files'] = 'Preparando archivos';
$string['view_course'] = 'Ver tu curso';
$string['create_course'] = 'Crear curso';
$string['resources'] = 'Recursos';
$string['designer_draft_course_name'] = '[Borrador] Nuevo curso';
$string['task_cleanup_draft_courses'] = 'Eliminar borradores de curso de más de 1 hora';
$string['designer_default_file_prompt'] = 'Generar una estructura de curso basada en los archivos subidos.';
$string['designer_default_module_prompt'] = 'Generar el contenido de aprendizaje completo para este módulo.';
$string['designer_filesyncfailed'] = 'Los archivos subidos no pudieron sincronizarse antes de la generación del módulo: {$a}';
$string['designer_filesynctimeout'] = 'Los archivos subidos no terminaron de sincronizarse a tiempo para la generación del módulo.';

// Designer strings
$string['designer_loading'] = 'Cargando estructura del curso...';
$string['designer_regenerate'] = 'Regenerar';
$string['designer_invalid_data'] = 'Datos de estructura no válidos';
$string['structurenotfound'] = 'No se encontró la estructura del curso. Genera una estructura primero o inténtalo de nuevo más tarde.';
$string['designer_save'] = 'Guardar';
$string['designer_cancel'] = 'Cancelar';
$string['designer_cancelling'] = 'Cancelando…';
$string['designer_edit'] = 'Editar';
$string['designer_duplicate'] = 'Duplicar';
$string['designer_delete'] = 'Eliminar';
$string['designer_confirm_delete'] = 'Confirmar eliminación';
$string['designer_delete_module_confirm'] = '¿Está seguro de que desea eliminar este módulo?';
$string['designer_delete_section_confirm'] = '¿Está seguro de que desea eliminar esta sección y todos sus módulos?';
$string['designer_unsaved_changes'] = 'Tiene cambios sin guardar. ¿Está seguro de que desea salir?';
$string['designer_saving'] = 'Guardando...';
$string['designer_saved'] = '¡Guardado!';
$string['designer_add_section'] = 'Añadir nueva sección';
$string['designer_add_activity'] = 'Añadir nueva actividad';
$string['designer_undo'] = 'Deshacer';
$string['designer_redo'] = 'Rehacer';
$string['designer_new_section_title'] = 'Nueva sección';
$string['designer_new_section_summary'] = 'Describa de qué trata esta sección';
$string['designer_new_module_type'] = 'Página';
$string['designer_new_module_title'] = 'Nueva página';
$string['designer_new_module_summary'] = 'Describa de qué trata esta actividad';
$string['designer_new_module_instructions'] = 'Añada instrucciones para el alumnado (opcional)';
$string['designer_copy_suffix'] = ' (Copia)';
$string['designer_change_activity_type'] = 'Cambiar tipo de actividad';
$string['designer_expand_all'] = 'Expandir todo';
$string['designer_collapse_all'] = 'Contraer todo';
$string['designer_module_summary_label'] = 'Resumen';
$string['designer_module_instructions_label'] = 'Instrucciones';
$string['designer_error_cancel_failed'] = 'No se pudo cancelar';
$string['designer_error_upload_failed'] = 'Error al subir el archivo';
$string['designer_error_delete_failed'] = 'Error al eliminar';
$string['designer_error_status_check_failed'] = 'Error al comprobar el estado';
$string['designer_error_structure_start_failed'] = 'No se pudo iniciar la generación de la estructura';
$string['designer_error_generation_failed_inline'] = 'Error en la generación';
$string['designer_error_finalize_failed'] = 'Error al finalizar';
$string['designer_error_save_structure_failed'] = 'No se pudo guardar la estructura';
