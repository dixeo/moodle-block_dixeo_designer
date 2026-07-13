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

$string['attachfile'] = 'Anexe documentos de origem que serão usados para gerar o curso. Os ficheiros de origem estão limitados a 50 MB no total e 20 MB por ficheiro.';
$string['blocktitle'] = 'Designer de Cursos Dixeo';
$string['categoryname'] = 'Categoria para os cursos criados';
$string['categoryname_desc'] = 'Introduza o nome da categoria de curso de nível superior onde serão colocados os cursos criados pelo Designer de Cursos Dixeo. A categoria será criada se não existir.';
$string['certificate_generation'] = 'Ativar geração de certificados';
$string['certificate_generation_description'] = 'Ativar ou desativar a geração de certificados de conclusão de curso.';
$string['certificate_location'] = 'Localização do certificado';
$string['certificate_location_description'] = 'Selecione onde o certificado será exibido.';
$string['certificate_name'] = 'Certificado de Conclusão';
$string['certificate_section'] = 'Certificado de Conclusão';
$string['certificate_section_intro'] = 'Obtenha o seu certificado de conclusão após terminar o curso.';
$string['certificate_settings'] = 'Geração de certificados';
$string['certificate_settings_help'] = '';
$string['certificate_template'] = 'Modelo de certificado';
$string['certificate_template_description'] = 'Selecione o modelo a utilizar ao gerar o certificado do curso.';
$string['certificate_unavailable'] = 'A geração de certificados não está disponível. Instale os plugins Course Certificate (mod_coursecertificate) e Tool Certificate (tool_certificate).';
$string['course_generated'] = 'O seu curso «<b> {$a} </b>» foi gerado com sucesso!';
$string['coursetemplate'] = 'Modelo pedagógico predefinido';
$string['coursetemplate_desc'] = 'Selecione o modelo pedagógico predefinido utilizado pelo Designer de Cursos Dixeo.';
$string['coursetemplate_none'] = 'Nenhum';
$string['create_course'] = 'Criar curso';
$string['default_categoryname'] = 'Cursos Dixeo';
$string['designacourse'] = 'Conceber um curso';
$string['designer_add_activity'] = 'Adicionar nova atividade';
$string['designer_add_section'] = 'Adicionar nova secção';
$string['designer_cancel'] = 'Cancelar';
$string['designer_cancelling'] = 'A cancelar…';
$string['designer_change_activity_type'] = 'Alterar tipo de atividade';
$string['designer_collapse_all'] = 'Recolher tudo';
$string['designer_confirm_delete'] = 'Confirmar eliminação';
$string['designer_copy_suffix'] = ' (Cópia)';
$string['designer_default_file_prompt'] = 'Gerar uma estrutura de curso baseada nos ficheiros carregados.';
$string['designer_default_module_prompt'] = 'Gerar o conteúdo de aprendizagem completo para este módulo.';
$string['designer_delete'] = 'Eliminar';
$string['designer_delete_module_confirm'] = 'Tem a certeza de que deseja eliminar este módulo?';
$string['designer_delete_section_confirm'] = 'Tem a certeza de que deseja eliminar esta secção e todos os seus módulos?';
$string['designer_draft_course_name'] = '[Rascunho] Novo curso';
$string['designer_duplicate'] = 'Duplicar';
$string['designer_edit'] = 'Editar';
$string['designer_error_cancel_failed'] = 'Cancelamento falhou';
$string['designer_error_delete_failed'] = 'Eliminação falhou';
$string['designer_error_finalize_failed'] = 'Finalização falhou';
$string['designer_error_generation_failed_inline'] = 'Geração falhou';
$string['designer_error_save_structure_failed'] = 'Não foi possível guardar a estrutura';
$string['designer_error_status_check_failed'] = 'Falha na verificação do estado';
$string['designer_error_structure_start_failed'] = 'Não foi possível iniciar a geração da estrutura';
$string['designer_error_upload_failed'] = 'Envio falhou';
$string['designer_expand_all'] = 'Expandir tudo';
$string['designer_filesyncfailed'] = 'Os ficheiros carregados não puderam ser sincronizados antes da geração do módulo: {$a}';
$string['designer_filesynctimeout'] = 'Os ficheiros carregados não terminaram de sincronizar a tempo para a geração do módulo.';
$string['designer_image_close_dialog'] = 'Fechar caixa de diálogo da imagem';
$string['designer_image_finalize_notice_background'] = 'Criar curso';
$string['designer_image_finalize_notice_body'] = 'A imagem do curso ainda não está pronta. Se criar o curso agora, a geração continuará em segundo plano e a imagem será adicionada ao curso quando estiver pronta.';
$string['designer_image_finalize_notice_title'] = 'A imagem do curso ainda está a ser gerada';
$string['designer_image_finalize_notice_wait'] = 'Aguardar';
$string['designer_image_generate'] = 'Editar';
$string['designer_image_generate_prompt_required'] = 'Descreva as alterações a fazer à imagem antes de continuar.';
$string['designer_image_generate_unavailable'] = 'O gerador de imagens ainda não está ligado.';
$string['designer_image_generating_status'] = 'A gerar imagem...';
$string['designer_image_regenerate'] = 'Editar imagem';
$string['designer_image_regenerate_dialog_label'] = 'Descreva as alterações a fazer à imagem';
$string['designer_image_regenerate_dialog_placeholder'] =
    'p. ex. Retirar o portátil da secretária, aproximar ligeiramente o enquadramento e manter a mesma iluminação.';
$string['designer_image_regenerate_dialog_title'] = 'Editar imagem';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['designer_invalid_data'] = 'Dados de estrutura inválidos';
$string['designer_loading'] = 'A carregar estrutura do curso...';
$string['designer_module_instructions_label'] = 'Instruções';
$string['designer_module_summary_label'] = 'Resumo';
$string['designer_new_module_instructions'] = 'Adicione instruções para os alunos (opcional)';
$string['designer_new_module_summary'] = 'Descreva do que trata esta atividade';
$string['designer_new_module_title'] = 'Nova página';
$string['designer_new_module_type'] = 'Página';
$string['designer_new_section_summary'] = 'Descreva do que trata esta secção';
$string['designer_new_section_title'] = 'Nova secção';
$string['designer_placeholder_course_summary'] = 'Resumo do curso (opcional)';
$string['designer_placeholder_course_title'] = 'Título do curso';
$string['designer_placeholder_module_instructions'] = 'Adicione instruções para a IA que descrevam o conteúdo desta atividade';
$string['designer_placeholder_module_summary'] = 'Resumo da atividade (opcional)';
$string['designer_placeholder_module_title'] = 'Título da atividade';
$string['designer_placeholder_section_summary'] = 'Resumo da secção (opcional)';
$string['designer_placeholder_section_title'] = 'Título da secção';
$string['designer_redo'] = 'Refazer';
$string['designer_regenerate'] = 'Regenerar';
$string['designer_save'] = 'Guardar';
$string['designer_saved'] = 'Guardado!';
$string['designer_saving'] = 'A guardar...';
$string['designer_structure_validation_failed_title'] = 'O curso ainda não pode ser criado';
$string['designer_undo'] = 'Desfazer';
$string['designer_unknown_error'] = 'Erro desconhecido';
$string['designer_unsaved_changes'] = 'Tem alterações não guardadas. Tem a certeza de que deseja sair?';
$string['dixeo_designer:addinstance'] = 'Adicionar um bloco Designer de Cursos Dixeo';
$string['dixeo_designer:manage'] = 'Gerir o Designer de Cursos Dixeo';
$string['dixeo_designer:myaddinstance'] = 'Adicionar um novo bloco Designer de Cursos Dixeo ao meu painel';
$string['draganddrop'] = 'Arraste e largue os seus ficheiros para carregar';
$string['error_title'] = 'Ups!';
$string['filetoolarge'] = 'O ficheiro é demasiado grande. Carregue um ficheiro com menos de 20 MB.';
$string['filetypeinvalid'] = 'O tipo de ficheiro {$a} não é suportado. Extensões suportadas: .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Gerar um novo curso';
$string['generate_course'] = 'Gerar';
$string['generate_course_tooltip'] = 'Gerar curso agora';
$string['generate_structure_btn'] = 'Gerar';
$string['generate_structure_tooltip'] = 'Gerar estrutura do curso';
$string['generating_course'] = 'Aguarde enquanto preparamos o seu curso. Este processo pode demorar alguns minutos...';
$string['heading'] = 'O que quer ensinar hoje?';
$string['heading2'] = 'Estamos a construir o seu curso!';
$string['invalidinput'] = 'Informação necessária.';
$string['invalidjson'] = 'JSON inválido';
$string['lastsection'] = 'Após a última secção';
$string['lti_maxenrolled'] = 'Máximo de utilizadores inscritos';
$string['lti_maxenrolled_desc'] = 'Número máximo de utilizadores que podem aceder através desta ferramenta LTI. 0 = sem limite.';
$string['lti_membersync'] = 'Sincronização de inscrições';
$string['lti_membersync_desc'] = 'Sincronizar as inscrições dos utilizadores a partir da plataforma.';
$string['lti_membersyncmode'] = 'Modo de sincronização de inscrições';
$string['lti_membersyncmode_desc'] = 'Escolha como as inscrições são sincronizadas quando a sincronização está ativa.';
$string['lti_publication'] = 'Publicação LTI';
$string['lti_publication_desc'] = 'Quando ativada, é adicionado um método de inscrição «Publicar como ferramenta LTI» aos cursos novos. Requer o plugin enrol_lti ativo.';
$string['lti_publication_enabled'] = 'Adicionar inscrição LTI';
$string['lti_publication_enabled_desc'] = 'Se ativada, será adicionada uma instância de inscrição LTI 1.3 aos cursos novos.';
$string['manage'] = 'Gerir o Designer de Cursos Dixeo';
$string['myaddinstance'] = 'Adicionar um novo bloco Designer de Cursos Dixeo ao meu painel';
$string['pluginname'] = 'Designer de Cursos Dixeo';
$string['privacy:metadata:email'] = 'O endereço de e-mail do utilizador que acede ao consumidor LTI';
$string['privacy:metadata:externalpurpose'] = 'O consumidor LTI fornece informações do utilizador e contexto ao fornecedor de ferramentas LTI.';
$string['privacy:metadata:firstname'] = 'O primeiro nome do utilizador que acede ao consumidor LTI';
$string['privacy:metadata:lastname'] = 'O apelido do utilizador que acede ao consumidor LTI';
$string['privacy:metadata:userid'] = 'O ID do utilizador que acede ao consumidor LTI';
$string['prompt_placeholder'] = 'Introduza o curso que pretende gerar: tema, número de secções e questionário se necessário.';
$string['regenerate_structure_tooltip'] = 'Regenerar a estrutura do curso';
$string['removefile'] = 'Remover ficheiro';
$string['resources'] = 'Recursos';
$string['self_enrol_configure'] = 'Configurar a autoinscrição';
$string['self_enrol_configure_desc'] = 'Se ativada, a autoinscrição é ativada para cursos novos. Se não existir uma instância, é criada uma.';
$string['self_enrol_generate_key'] = 'Gerar uma chave de inscrição';
$string['self_enrol_generate_key_desc'] = 'Se ativada, é definida uma chave de inscrição única. Se desativada, não é usada chave (inscrição aberta), exceto se a configuração do enrol_self ao nível do site exigir chave; nesse caso, é gerada uma chave mesmo assim.';
$string['self_enrol_heading'] = 'Autoinscrição';
$string['self_enrol_heading_desc'] = 'Opções para a autoinscrição quando um curso é criado. Requer o plugin enrol_self ativo.';
$string['step_finalizing_details'] = 'A finalizar detalhes';
$string['step_generating_content'] = 'A gerar conteúdo';
$string['step_generating_content_count'] = 'A gerar conteúdo ({$a->current}/{$a->total})';
$string['step_generating_structure'] = 'A gerar estrutura';
$string['step_preparing_files'] = 'A preparar ficheiros';
$string['step_processing_prompt'] = 'A processar a consigna';
$string['step_uploading_files'] = 'A processar ficheiros';
$string['step_uploading_files_count'] = 'A processar ficheiros ({$a->current}/{$a->total})';
$string['structurenotfound'] = 'Estrutura do curso não encontrada. Gere uma estrutura primeiro ou tente novamente mais tarde.';
$string['summarysection'] = 'No resumo do curso';
$string['task_cleanup_draft_courses'] = 'Eliminar rascunhos de cursos com mais de 1 hora';
$string['toggle_tooltip_hide'] = 'Ocultar bloco de geração';
$string['toggle_tooltip_show'] = 'Exibir bloco de geração';
$string['totalsize'] = '<b>Tamanho total:</b> {$a}';
$string['totaltoolarge'] = 'O tamanho total dos ficheiros excede o limite de 50 MB. Carregue ficheiros mais pequenos ou remova um para continuar.';
$string['uploaderror'] = 'Erro ao carregar o ficheiro.';
$string['uploading_files'] = 'A carregar…';
$string['view_course'] = 'Ver o seu curso';
