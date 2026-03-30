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

$string['attachfile'] = 'Allega un documento sorgente';
$string['blocktitle'] = 'Progettatore di Corsi Dixeo';
$string['toggle_tooltip_hide'] = 'Nascondi il blocco di generazione';
$string['toggle_tooltip_show'] = 'Mostra il blocco di generazione';
$string['designacourse'] = 'Progetta un corso';
$string['categoryname'] = 'Categoria per i corsi creati';
$string['categoryname_desc'] = 'Inserisci il nome della categoria di corso di primo livello in cui verranno inseriti i corsi creati dal Progettatore di Corsi Dixeo. La categoria verrà creata se non esiste.';
$string['coursetemplate'] = 'Modello di struttura pedagogica';
$string['coursetemplate_desc'] = 'Seleziona il modello di struttura pedagogica utilizzato dal Progettatore di Corsi Dixeo.';
$string['coursetemplate_none'] = 'Nessuno';
$string['course_generated'] = 'Il tuo corso «<b> {$a} </b>» è stato generato con successo!';
$string['default_categoryname'] = 'Corsi Dixeo';

// Certificato (finalizzazione) — allineato a local_edai.
$string['certificate_settings'] = 'Generazione certificato';
$string['certificate_settings_help'] = '';
$string['certificate_unavailable'] = 'La generazione del certificato non è disponibile. Si prega di installare i plugin Course Certificate (mod_coursecertificate) e Tool Certificate (tool_certificate).';
$string['certificate_generation'] = 'Abilita generazione certificato';
$string['certificate_generation_description'] = 'Abilita o disabilita la generazione dei certificati di completamento del corso.';
$string['certificate_template'] = 'Template certificato';
$string['certificate_template_description'] = 'Seleziona il template da utilizzare per la generazione del certificato del corso.';
$string['certificate_location'] = 'Posizione certificato';
$string['certificate_location_description'] = 'Seleziona dove verrà visualizzato il certificato.';
$string['summarysection'] = 'Nel sommario del corso';
$string['lastsection'] = 'Dopo l\'ultima sezione';
$string['certificate_section'] = 'Certificato di conseguimento';
$string['certificate_section_intro'] = 'Recupera il tuo certificato di conseguimento una volta completato il corso.';
$string['certificate_name'] = 'Certificato di conseguimento';

$string['dixeo_designer:addinstance'] = 'Aggiungi un blocco Progettatore di Corsi Dixeo';
$string['dixeo_designer:myaddinstance'] = 'Aggiungi un nuovo blocco Progettatore di Corsi Dixeo alla mia dashboard';
$string['dixeo_designer:create'] = 'Creare corsi con il Progettatore di Corsi Dixeo';
$string['dixeo_designer:manage'] = 'Gestire il Progettatore di Corsi Dixeo';
$string['manage'] = 'Gestire il Progettatore di Corsi Dixeo';
$string['draganddrop'] = 'Trascina e rilascia i tuoi file per caricarli';
$string['designer_unknown_error'] = 'Errore sconosciuto';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['error_title'] = 'Ops!';
$string['filetoolarge'] = 'Il file è troppo grande. Carica un file inferiore a 20MB.';
$string['filetypeinvalid'] = 'Il tipo di file {$a} non è supportato. Estensioni supportate: .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Genera un nuovo corso';
$string['generate_course'] = 'Genera';
$string['generate_course_tooltip'] = 'Genera corso ora';
$string['generate_structure_btn'] = 'Genera';
$string['generate_structure_tooltip'] = 'Genera struttura del corso';
$string['regenerate_structure_tooltip'] = 'Rigenera la struttura del corso';
$string['generating_course'] = 'Attendere mentre prepariamo il tuo corso. Questo processo potrebbe richiedere alcuni minuti...';
$string['heading'] = 'Cosa vuoi insegnare oggi?';
$string['heading2'] = 'Stiamo creando il tuo corso!';
$string['invalidinput'] = 'Informazioni richieste.';
$string['myaddinstance'] = 'Aggiungi un nuovo blocco Progettatore di Corsi Dixeo alla mia dashboard';
$string['pluginname'] = 'Progettatore di Corsi Dixeo';
$string['privacy:metadata:email'] = 'L\'indirizzo email dell\'utente che accede al Consumer LTI';
$string['privacy:metadata:externalpurpose'] = 'Il Consumer LTI fornisce informazioni sull\'utente e il contesto al Tool Provider LTI.';
$string['privacy:metadata:firstname'] = 'Il nome dell\'utente che accede al Consumer LTI';
$string['privacy:metadata:lastname'] = 'Il cognome dell\'utente che accede al Consumer LTI';
$string['privacy:metadata:userid'] = 'L\'ID dell\'utente che accede al Consumer LTI';
$string['prompt_placeholder'] = 'Inserisci il corso che vuoi generare: argomento, numero di sezioni e quiz se necessario.';
$string['removefile'] = 'Rimuovi file';
$string['step_uploading_files'] = 'Elaborazione file';
$string['step_generating_structure'] = 'Generazione struttura';
$string['step_generating_content'] = 'Generazione contenuti';
$string['step_finalizing_details'] = 'Finalizzazione dettagli';
$string['totalsize'] = '<b>Dimensione totale:</b> {$a}';
$string['totaltoolarge'] = 'La dimensione totale dei file supera il limite di 50MB. Carica file più piccoli o rimuovine uno per continuare.';
$string['uploaderror'] = 'Errore nel caricamento del file.';
$string['uploading_files'] = 'Caricamento…';
$string['step_uploading_files_count'] = 'Elaborazione file ({$a->current}/{$a->total})';
$string['step_generating_content_count'] = 'Generazione contenuti ({$a->current}/{$a->total})';
$string['step_processing_prompt'] = 'Elaborazione della consegna';
$string['step_preparing_files'] = 'Preparazione dei file';
$string['view_course'] = 'Visualizza il tuo corso';
$string['create_course'] = 'Crea corso';
$string['resources'] = 'Risorse';
$string['designer_draft_course_name'] = '[Bozza] Nuovo corso';
$string['task_cleanup_draft_courses'] = 'Elimina bozze di corso più vecchie di 1 ora';
$string['designer_default_file_prompt'] = 'Genera una struttura di corso basata sui file caricati.';
$string['designer_default_module_prompt'] = 'Genera il contenuto di apprendimento completo per questo modulo.';
$string['designer_filesyncfailed'] = 'I file caricati non hanno potuto essere sincronizzati prima della generazione del modulo: {$a}';
$string['designer_filesynctimeout'] = 'I file caricati non hanno finito di sincronizzarsi in tempo per la generazione del modulo.';

// Designer strings
$string['designer_loading'] = 'Caricamento struttura del corso...';
$string['designer_regenerate'] = 'Rigenera';
$string['designer_invalid_data'] = 'Dati di struttura non validi';
$string['structurenotfound'] = 'Struttura del corso non trovata. Genera prima una struttura o riprova più tardi.';
$string['designer_save'] = 'Salva';
$string['designer_cancel'] = 'Annulla';
$string['designer_cancelling'] = 'Annullamento in corso…';
$string['designer_edit'] = 'Modifica';
$string['designer_duplicate'] = 'Duplica';
$string['designer_delete'] = 'Elimina';
$string['designer_confirm_delete'] = 'Conferma eliminazione';
$string['designer_delete_module_confirm'] = 'Sei sicuro di voler eliminare questo modulo?';
$string['designer_delete_section_confirm'] = 'Sei sicuro di voler eliminare questa sezione e tutti i suoi moduli?';
$string['designer_unsaved_changes'] = 'Hai modifiche non salvate. Sei sicuro di voler uscire?';
$string['designer_saving'] = 'Salvataggio...';
$string['designer_saved'] = 'Salvato!';
$string['designer_add_section'] = 'Aggiungi nuova sezione';
$string['designer_add_activity'] = 'Aggiungi nuova attività';
$string['designer_undo'] = 'Annulla';
$string['designer_redo'] = 'Ripeti';
$string['designer_new_section_title'] = 'Nuova sezione';
$string['designer_new_section_summary'] = 'Descrivi di cosa tratta questa sezione';
$string['designer_new_module_type'] = 'Pagina';
$string['designer_new_module_title'] = 'Nuova pagina';
$string['designer_new_module_summary'] = 'Descrivi di cosa tratta questa attività';
$string['designer_new_module_instructions'] = 'Aggiungi istruzioni per gli studenti (facoltativo)';
$string['designer_copy_suffix'] = ' (Copia)';
$string['designer_change_activity_type'] = 'Cambia tipo di attività';
$string['designer_expand_all'] = 'Espandi tutto';
$string['designer_collapse_all'] = 'Comprimi tutto';
$string['designer_module_summary_label'] = 'Sintesi';
$string['designer_module_instructions_label'] = 'Istruzioni';
$string['designer_error_cancel_failed'] = 'Annullamento non riuscito';
$string['designer_error_upload_failed'] = 'Caricamento non riuscito';
$string['designer_error_delete_failed'] = 'Eliminazione non riuscita';
$string['designer_error_status_check_failed'] = 'Verifica dello stato non riuscita';
$string['designer_error_structure_start_failed'] = 'Impossibile avviare la generazione della struttura';
$string['designer_error_generation_failed_inline'] = 'Generazione non riuscita';
$string['designer_error_finalize_failed'] = 'Finalizzazione non riuscita';
$string['designer_error_save_structure_failed'] = 'Impossibile salvare la struttura';
