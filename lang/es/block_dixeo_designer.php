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

$string['attachfile'] = 'Adjunte documentos fuente que se utilizarán para generar el curso. Los archivos fuente están limitados a 50 MB en total y 20 MB por archivo.';
$string['blocktitle'] = 'Diseñador de Cursos Dixeo';
$string['categoryname'] = 'Categoría para cursos creados';
$string['categoryname_desc'] = 'Introduce el nombre de la categoría de curso de nivel superior donde se colocarán los cursos creados con el Diseñador de Cursos Dixeo. Se creará la categoría si no existe.';
$string['certificate_generation'] = 'Habilitar generación de certificados';
$string['certificate_generation_description'] = 'Habilita o deshabilita la generación de certificados de finalización de curso.';
$string['certificate_location'] = 'Ubicación del certificado';
$string['certificate_location_description'] = 'Selecciona dónde se mostrará el certificado.';
$string['certificate_name'] = 'Certificado de logro';
$string['certificate_section'] = 'Certificado de logro';
$string['certificate_section_intro'] = 'Recupera tu certificado de logro una vez completado el curso.';
$string['certificate_settings'] = 'Generación de certificados';
$string['certificate_settings_help'] = '';
$string['certificate_template'] = 'Plantilla de certificado';
$string['certificate_template_description'] = 'Selecciona la plantilla a utilizar al generar el certificado del curso.';
$string['certificate_unavailable'] = 'La generación de certificados no está disponible. Por favor, instala los plugins Course Certificate (mod_coursecertificate) y Tool Certificate (tool_certificate).';
$string['course_generated'] = '¡Tu curso «<b> {$a} </b>» se ha generado correctamente!';
$string['coursetemplate'] = 'Plantilla pedagógica predeterminada';
$string['coursetemplate_desc'] = 'Seleccione la plantilla pedagógica predeterminada utilizada por el Diseñador de Cursos Dixeo.';
$string['coursetemplate_none'] = 'Ninguna';
$string['create_course'] = 'Crear curso';
$string['default_categoryname'] = 'Cursos Dixeo';
$string['designacourse'] = 'Diseñar un curso';
$string['designer_add_activity'] = 'Añadir nueva actividad';
$string['designer_add_section'] = 'Añadir nueva sección';
$string['designer_cancel'] = 'Cancelar';
$string['designer_cancelling'] = 'Cancelando…';
$string['designer_change_activity_type'] = 'Cambiar tipo de actividad';
$string['designer_collapse_all'] = 'Contraer todo';
$string['designer_confirm_delete'] = 'Confirmar eliminación';
$string['designer_copy_suffix'] = ' (Copia)';
$string['designer_default_file_prompt'] = 'Generar una estructura de curso basada en los archivos subidos.';
$string['designer_default_module_prompt'] = 'Generar el contenido de aprendizaje completo para este módulo.';
$string['designer_delete'] = 'Eliminar';
$string['designer_delete_module_confirm'] = '¿Está seguro de que desea eliminar este módulo?';
$string['designer_delete_section_confirm'] = '¿Está seguro de que desea eliminar esta sección y todos sus módulos?';
$string['designer_draft_course_name'] = '[Borrador] Nuevo curso';
$string['designer_duplicate'] = 'Duplicar';
$string['designer_edit'] = 'Editar';
$string['designer_error_cancel_failed'] = 'No se pudo cancelar';
$string['designer_error_delete_failed'] = 'Error al eliminar';
$string['designer_error_finalize_failed'] = 'Error al finalizar';
$string['designer_error_generation_failed_inline'] = 'Error en la generación';
$string['designer_error_save_structure_failed'] = 'No se pudo guardar la estructura';
$string['designer_error_status_check_failed'] = 'Error al comprobar el estado';
$string['designer_error_structure_start_failed'] = 'No se pudo iniciar la generación de la estructura';
$string['designer_error_upload_failed'] = 'Error al subir el archivo';
$string['designer_expand_all'] = 'Expandir todo';
$string['designer_filesyncfailed'] = 'Los archivos subidos no pudieron sincronizarse antes de la generación del módulo: {$a}';
$string['designer_filesynctimeout'] = 'Los archivos subidos no terminaron de sincronizarse a tiempo para la generación del módulo.';
$string['designer_image_close_dialog'] = 'Cerrar el cuadro de diálogo de imagen';
$string['designer_image_finalize_notice_background'] = 'Crear curso';
$string['designer_image_finalize_notice_body'] = 'La imagen del curso aún no está lista. Si crea el curso ahora, la generación continuará en segundo plano y la imagen se añadirá al curso cuando esté lista.';
$string['designer_image_finalize_notice_title'] = 'La imagen del curso sigue generándose';
$string['designer_image_finalize_notice_wait'] = 'Esperar';
$string['designer_image_generate'] = 'Editar';
$string['designer_image_generate_prompt_required'] = 'Describe los cambios que quieres aplicar a la imagen antes de continuar.';
$string['designer_image_generate_unavailable'] = 'El generador de imágenes aún no está conectado.';
$string['designer_image_generating_status'] = 'Generando imagen...';
$string['designer_image_regenerate'] = 'Editar imagen';
$string['designer_image_regenerate_dialog_label'] = 'Describe los cambios que quieres aplicar a la imagen';
$string['designer_image_regenerate_dialog_placeholder'] =
    'p. ej. Quitar el portátil del escritorio, acercar un poco el encuadre y mantener la misma iluminación.';
$string['designer_image_regenerate_dialog_title'] = 'Editar imagen';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['designer_invalid_data'] = 'Datos de estructura no válidos';
$string['designer_loading'] = 'Cargando estructura del curso...';
$string['designer_module_instructions_label'] = 'Instrucciones';
$string['designer_module_summary_label'] = 'Resumen';
$string['designer_new_module_instructions'] = 'Añada instrucciones para el alumnado (opcional)';
$string['designer_new_module_summary'] = 'Describa de qué trata esta actividad';
$string['designer_new_module_title'] = 'Nueva página';
$string['designer_new_module_type'] = 'Página';
$string['designer_new_section_summary'] = 'Describa de qué trata esta sección';
$string['designer_new_section_title'] = 'Nueva sección';
$string['designer_placeholder_course_summary'] = 'Resumen del curso (opcional)';
$string['designer_placeholder_course_title'] = 'Título del curso';
$string['designer_placeholder_module_instructions'] = 'Añada instrucciones para la IA que describan el contenido de esta actividad';
$string['designer_placeholder_module_summary'] = 'Resumen de la actividad (opcional)';
$string['designer_placeholder_module_title'] = 'Título de la actividad';
$string['designer_placeholder_section_summary'] = 'Resumen de la sección (opcional)';
$string['designer_placeholder_section_title'] = 'Título de la sección';
$string['designer_redo'] = 'Rehacer';
$string['designer_regenerate'] = 'Regenerar';
$string['designer_save'] = 'Guardar';
$string['designer_saved'] = '¡Guardado!';
$string['designer_saving'] = 'Guardando...';
$string['designer_structure_validation_failed_title'] = 'El curso aún no puede crearse';
$string['designer_undo'] = 'Deshacer';
$string['designer_unknown_error'] = 'Error desconocido';
$string['designer_unsaved_changes'] = 'Tiene cambios sin guardar. ¿Está seguro de que desea salir?';
$string['dixeo_designer:addinstance'] = 'Agregar un bloque Diseñador de Cursos Dixeo';
$string['dixeo_designer:manage'] = 'Gestionar el Diseñador de Cursos Dixeo';
$string['dixeo_designer:myaddinstance'] = 'Agregar un nuevo bloque Diseñador de Cursos Dixeo a mi panel';
$string['draganddrop'] = 'Arrastra y suelta tus archivos para subirlos';
$string['error_title'] = '¡Vaya!';
$string['filetoolarge'] = 'El archivo es demasiado grande. Por favor, sube un archivo menor de 20MB.';
$string['filetypeinvalid'] = 'El tipo de archivo {$a} no es compatible. Extensiones soportadas: .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Generar un nuevo curso';
$string['generate_course'] = 'Generar';
$string['generate_course_tooltip'] = 'Generar curso ahora';
$string['generate_structure_btn'] = 'Generar';
$string['generate_structure_tooltip'] = 'Generar estructura del curso';
$string['generating_course'] = 'Por favor, espera mientras preparamos tu curso. Este proceso puede tardar unos minutos...';
$string['heading'] = '¿Qué quieres enseñar hoy?';
$string['heading2'] = '¡Estamos construyendo tu curso!';
$string['invalidinput'] = 'Información requerida.';
$string['invalidjson'] = 'JSON no válido';
$string['lastsection'] = 'Después de la última sección';
$string['lti_maxenrolled'] = 'Máximo de usuarios matriculados';
$string['lti_maxenrolled_desc'] = 'Número máximo de usuarios que pueden acceder mediante esta herramienta LTI. 0 = sin límite.';
$string['lti_membersync'] = 'Sincronización de matrículas';
$string['lti_membersync_desc'] = 'Sincronizar las matrículas de los usuarios desde la plataforma.';
$string['lti_membersyncmode'] = 'Modo de sincronización de matrículas';
$string['lti_membersyncmode_desc'] = 'Elige cómo se sincronizan las matrículas cuando la sincronización está activada.';
$string['lti_publication'] = 'Publicación LTI';
$string['lti_publication_desc'] = 'Si está activada, se añade un método de matriculación «Publicar como herramienta LTI» a los cursos nuevos. Requiere el plugin enrol_lti activado.';
$string['lti_publication_enabled'] = 'Añadir matriculación LTI';
$string['lti_publication_enabled_desc'] = 'Si está activada, se añadirá una instancia de matriculación LTI 1.3 a los cursos nuevos.';
$string['manage'] = 'Gestionar el Diseñador de Cursos Dixeo';
$string['myaddinstance'] = 'Agregar un nuevo bloque Diseñador de Cursos Dixeo a mi panel';
$string['pluginname'] = 'Diseñador de Cursos Dixeo';
$string['privacy:metadata:email'] = 'La dirección de correo electrónico del usuario que usa el diseñador de cursos.';
$string['privacy:metadata:external:files'] = 'Archivos fuente subidos para la generación del curso que pueden enviarse a Dixeo.';
$string['privacy:metadata:externalpurpose'] = 'Las consignas del diseñador, los campos de identidad del usuario, el contexto de la estructura del curso y los archivos fuente subidos se envían a los servicios de Dixeo para generar y perfeccionar cursos.';
$string['privacy:metadata:files'] = 'Archivos fuente subidos e imágenes de curso generadas almacenadas por el diseñador.';
$string['privacy:metadata:firstname'] = 'El nombre del usuario que usa el diseñador de cursos.';
$string['privacy:metadata:lastname'] = 'El apellido del usuario que usa el diseñador de cursos.';
$string['privacy:metadata:structure'] = 'JSON de estructura de curso persistido y metadatos relacionados de generación de imágenes.';
$string['privacy:metadata:structure:description'] = 'Una descripción breve almacenada con la estructura del curso.';
$string['privacy:metadata:structure:imageerror'] = 'Detalles de error de la generación o edición de la imagen del curso.';
$string['privacy:metadata:structure:imagejobid'] = 'El id de trabajo remoto para la generación o edición de la imagen del curso.';
$string['privacy:metadata:structure:imagestatus'] = 'El estado de la generación o edición de la imagen del curso.';
$string['privacy:metadata:structure:jobid'] = 'El identificador local del trabajo del diseñador para la estructura.';
$string['privacy:metadata:structure:structure'] = 'El JSON de estructura del curso editado en el diseñador.';
$string['privacy:metadata:submission'] = 'Registros de envío del diseñador (prompt, plantilla, estado del trabajo, curso vinculado).';
$string['privacy:metadata:submission:courseid'] = 'El curso Moodle creado desde el envío del diseñador, cuando está disponible.';
$string['privacy:metadata:submission:jobid'] = 'El identificador local del trabajo del diseñador.';
$string['privacy:metadata:submission:prompt'] = 'La descripción del curso o prompt introducido por el usuario.';
$string['privacy:metadata:submission:remotejobid'] = 'El identificador remoto de trabajo de Dixeo para operaciones asíncronas.';
$string['privacy:metadata:submission:status'] = 'El estado del ciclo de vida del envío del diseñador.';
$string['privacy:metadata:submission:templateid'] = 'El identificador de la plantilla pedagógica seleccionada.';
$string['privacy:metadata:timecreated'] = 'La hora en que se creó el registro.';
$string['privacy:metadata:timemodified'] = 'La hora en que se modificó por última vez el registro.';
$string['privacy:metadata:userid'] = 'El ID del usuario que posee los datos del diseñador.';
$string['privacy:path:generatedimages'] = 'Imágenes de curso generadas';
$string['privacy:path:structures'] = 'Estructuras de curso';
$string['privacy:path:submissions'] = 'Envíos';
$string['prompt_placeholder'] = 'Introduce el curso que deseas generar: tema, número de secciones y cuestionario si es necesario.';
$string['regenerate_structure_tooltip'] = 'Regenerar la estructura del curso';
$string['removefile'] = 'Eliminar archivo';
$string['resources'] = 'Recursos';
$string['self_enrol_configure'] = 'Configurar la autoinscripción';
$string['self_enrol_configure_desc'] = 'Si está activada, la autoinscripción se habilita en los cursos nuevos. Si no existe una instancia, se crea una.';
$string['self_enrol_generate_key'] = 'Generar una clave de matriculación';
$string['self_enrol_generate_key_desc'] = 'Si está activada, se define una clave de matriculación única. Si está desactivada, no se usa clave (matrícula abierta), salvo que la configuración de enrol_self a nivel del sitio exija clave; en ese caso, se genera una clave igualmente.';
$string['self_enrol_heading'] = 'Autoinscripción';
$string['self_enrol_heading_desc'] = 'Opciones para la autoinscripción cuando se crea un curso. Requiere el plugin enrol_self activado.';
$string['step_finalizing_details'] = 'Finalizando detalles';
$string['step_generating_content'] = 'Generando contenido';
$string['step_generating_content_count'] = 'Generando contenido ({$a->current}/{$a->total})';
$string['step_generating_structure'] = 'Generando estructura';
$string['step_preparing_files'] = 'Preparando archivos';
$string['step_processing_prompt'] = 'Procesando la consigna';
$string['step_uploading_files'] = 'Procesando archivos';
$string['step_uploading_files_count'] = 'Procesando archivos ({$a->current}/{$a->total})';
$string['structurenotfound'] = 'No se encontró la estructura del curso. Genera una estructura primero o inténtalo de nuevo más tarde.';
$string['summarysection'] = 'En el resumen del curso';
$string['task_cleanup_draft_courses'] = 'Eliminar borradores de curso de más de 1 hora';
$string['toggle_tooltip_hide'] = 'Ocultar el bloque de generación';
$string['toggle_tooltip_show'] = 'Mostrar el bloque de generación';
$string['totalsize'] = '<b>Tamaño total:</b> {$a}';
$string['totaltoolarge'] = 'El tamaño total de los archivos supera el límite de 50MB. Sube archivos más pequeños o elimina uno para continuar.';
$string['uploaderror'] = 'Error al subir el archivo.';
$string['uploading_files'] = 'Subiendo…';
$string['view_course'] = 'Ver tu curso';
