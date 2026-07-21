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

$string['attachfile'] = 'Allega documenti sorgente che verranno utilizzati per generare il corso. I file sorgente sono limitati a 50 MB in totale e 20 MB per file.';
$string['blocktitle'] = 'Progettatore di Corsi Dixeo';
$string['categoryname'] = 'Categoria per i corsi creati';
$string['categoryname_desc'] = 'Inserisci il nome della categoria di corso di primo livello in cui verranno inseriti i corsi creati dal Progettatore di Corsi Dixeo. La categoria verrà creata se non esiste.';
$string['certificate_generation'] = 'Abilita generazione certificato';
$string['certificate_generation_description'] = 'Abilita o disabilita la generazione dei certificati di completamento del corso.';
$string['certificate_location'] = 'Posizione certificato';
$string['certificate_location_description'] = 'Seleziona dove verrà visualizzato il certificato.';
$string['certificate_name'] = 'Certificato di conseguimento';
$string['certificate_section'] = 'Certificato di conseguimento';
$string['certificate_section_intro'] = 'Recupera il tuo certificato di conseguimento una volta completato il corso.';
$string['certificate_settings'] = 'Generazione certificato';
$string['certificate_settings_help'] = '';
$string['certificate_template'] = 'Template certificato';
$string['certificate_template_description'] = 'Seleziona il template da utilizzare per la generazione del certificato del corso.';
$string['certificate_unavailable'] = 'La generazione del certificato non è disponibile. Si prega di installare i plugin Course Certificate (mod_coursecertificate) e Tool Certificate (tool_certificate).';
$string['course_generated'] = 'Il tuo corso «<b> {$a} </b>» è stato generato con successo!';
$string['coursetemplate'] = 'Modello pedagogico predefinito';
$string['coursetemplate_desc'] = 'Seleziona il modello pedagogico predefinito utilizzato dal Progettatore di Corsi Dixeo.';
$string['coursetemplate_none'] = 'Nessuno';
$string['create_course'] = 'Crea corso';
$string['default_categoryname'] = 'Corsi Dixeo';
$string['designacourse'] = 'Progetta un corso';
$string['designer_add_activity'] = 'Aggiungi nuova attività';
$string['designer_add_section'] = 'Aggiungi nuova sezione';
$string['designer_cancel'] = 'Annulla';
$string['designer_cancelling'] = 'Annullamento in corso…';
$string['designer_change_activity_type'] = 'Cambia tipo di attività';
$string['designer_collapse_all'] = 'Comprimi tutto';
$string['designer_confirm_delete'] = 'Conferma eliminazione';
$string['designer_copy_suffix'] = ' (Copia)';
$string['designer_default_file_prompt'] = 'Genera una struttura di corso basata sui file caricati.';
$string['designer_default_module_prompt'] = 'Genera il contenuto di apprendimento completo per questo modulo.';
$string['designer_delete'] = 'Elimina';
$string['designer_delete_module_confirm'] = 'Sei sicuro di voler eliminare questo modulo?';
$string['designer_delete_section_confirm'] = 'Sei sicuro di voler eliminare questa sezione e tutti i suoi moduli?';
$string['designer_draft_course_name'] = '[Bozza] Nuovo corso';
$string['designer_duplicate'] = 'Duplica';
$string['designer_edit'] = 'Modifica';
$string['designer_error_cancel_failed'] = 'Annullamento non riuscito';
$string['designer_error_delete_failed'] = 'Eliminazione non riuscita';
$string['designer_error_finalize_failed'] = 'Finalizzazione non riuscita';
$string['designer_error_generation_failed_inline'] = 'Generazione non riuscita';
$string['designer_error_save_structure_failed'] = 'Impossibile salvare la struttura';
$string['designer_error_status_check_failed'] = 'Verifica dello stato non riuscita';
$string['designer_error_structure_start_failed'] = 'Impossibile avviare la generazione della struttura';
$string['designer_error_upload_failed'] = 'Caricamento non riuscito';
$string['designer_expand_all'] = 'Espandi tutto';
$string['designer_filesyncfailed'] = 'I file caricati non hanno potuto essere sincronizzati prima della generazione del modulo: {$a}';
$string['designer_filesynctimeout'] = 'I file caricati non hanno finito di sincronizzarsi in tempo per la generazione del modulo.';
$string['designer_image_close_dialog'] = 'Chiudi la finestra dell\'immagine';
$string['designer_image_finalize_notice_background'] = 'Crea corso';
$string['designer_image_finalize_notice_body'] = 'L\'immagine del corso non è ancora pronta. Se crei il corso ora, la generazione continuerà in background e l\'immagine verrà aggiunta al corso quando sarà pronta.';
$string['designer_image_finalize_notice_title'] = 'L\'immagine del corso è ancora in generazione';
$string['designer_image_finalize_notice_wait'] = 'Attendi';
$string['designer_image_generate'] = 'Modifica';
$string['designer_image_generate_prompt_required'] = 'Descrivi le modifiche da apportare all\'immagine prima di continuare.';
$string['designer_image_generate_unavailable'] = 'Il generatore di immagini non è ancora collegato.';
$string['designer_image_generating_status'] = 'Generazione dell\'immagine...';
$string['designer_image_regenerate'] = 'Modifica immagine';
$string['designer_image_regenerate_dialog_label'] = 'Descrivi le modifiche da apportare all\'immagine';
$string['designer_image_regenerate_dialog_placeholder'] =
    'es. Rimuovere il laptop dalla scrivania, zoomare leggermente e mantenere la stessa illuminazione.';
$string['designer_image_regenerate_dialog_title'] = 'Modifica immagine';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['designer_invalid_data'] = 'Dati di struttura non validi';
$string['designer_loading'] = 'Caricamento struttura del corso...';
$string['designer_module_instructions_label'] = 'Istruzioni';
$string['designer_module_summary_label'] = 'Sintesi';
$string['designer_new_module_instructions'] = 'Aggiungi istruzioni per gli studenti (facoltativo)';
$string['designer_new_module_summary'] = 'Descrivi di cosa tratta questa attività';
$string['designer_new_module_title'] = 'Nuova pagina';
$string['designer_new_module_type'] = 'Pagina';
$string['designer_new_section_summary'] = 'Descrivi di cosa tratta questa sezione';
$string['designer_new_section_title'] = 'Nuova sezione';
$string['designer_placeholder_course_summary'] = 'Riepilogo del corso (facoltativo)';
$string['designer_placeholder_course_title'] = 'Titolo del corso';
$string['designer_placeholder_module_instructions'] = 'Aggiungi istruzioni per l\'IA che descrivano il contenuto di questa attività';
$string['designer_placeholder_module_summary'] = 'Riepilogo dell\'attività (facoltativo)';
$string['designer_placeholder_module_title'] = 'Titolo dell\'attività';
$string['designer_placeholder_section_summary'] = 'Riepilogo della sezione (facoltativo)';
$string['designer_placeholder_section_title'] = 'Titolo della sezione';
$string['designer_redo'] = 'Ripeti';
$string['designer_regenerate'] = 'Rigenera';
$string['designer_save'] = 'Salva';
$string['designer_saved'] = 'Salvato!';
$string['designer_saving'] = 'Salvataggio...';
$string['designer_structure_validation_failed_title'] = 'Impossibile creare il corso per ora';
$string['designer_undo'] = 'Annulla';
$string['designer_unknown_error'] = 'Errore sconosciuto';
$string['designer_unsaved_changes'] = 'Hai modifiche non salvate. Sei sicuro di voler uscire?';
$string['dixeo_designer:addinstance'] = 'Aggiungi un blocco Progettatore di Corsi Dixeo';
$string['dixeo_designer:manage'] = 'Gestire il Progettatore di Corsi Dixeo';
$string['dixeo_designer:myaddinstance'] = 'Aggiungi un nuovo blocco Progettatore di Corsi Dixeo alla mia dashboard';
$string['draganddrop'] = 'Trascina e rilascia i tuoi file per caricarli';
$string['error_title'] = 'Ops!';
$string['eventcoursefinalized'] = 'Corso del designer Dixeo finalizzato';
$string['eventcoursefinalizeddesc'] = 'L\'utente con id \'{$a->userid}\' ha finalizzato l\'invio del designer \'{$a->submissionid}\' (jobid=\'{$a->jobid}\') nel corso \'{$a->courseid}\'.';
$string['eventdraftcancelled'] = 'Bozza del designer Dixeo annullata';
$string['eventdraftcancelleddesc'] = 'L\'utente con id \'{$a->userid}\' ha annullato l\'invio del designer \'{$a->submissionid}\' (jobid=\'{$a->jobid}\', deletestructure={$a->deletestructure}).';
$string['eventgenerationstarted'] = 'Generazione del designer Dixeo avviata';
$string['eventgenerationstarteddesc'] = 'L\'utente con id \'{$a->userid}\' ha avviato la generazione per l\'invio del designer \'{$a->submissionid}\' (jobid=\'{$a->jobid}\', draftcourseid={$a->courseid}).';
$string['eventsubmissionfiledeleted'] = 'File sorgente del designer Dixeo eliminato';
$string['eventsubmissionfiledeleteddesc'] = 'L\'utente con id \'{$a->userid}\' ha eliminato il file \'{$a->fileid}\' dall\'invio del designer \'{$a->submissionid}\' (jobid=\'{$a->jobid}\').';
$string['eventsubmissionfileuploaded'] = 'File sorgente del designer Dixeo caricato';
$string['eventsubmissionfileuploadeddesc'] = 'L\'utente con id \'{$a->userid}\' ha caricato {$a->filecount} file nell\'invio del designer \'{$a->submissionid}\' (jobid=\'{$a->jobid}\').';
$string['filetoolarge'] = 'Il file è troppo grande. Carica un file inferiore a 20MB.';
$string['filetypeinvalid'] = 'Il tipo di file {$a} non è supportato. Estensioni supportate: .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Genera un nuovo corso';
$string['generate_course'] = 'Genera';
$string['generate_course_tooltip'] = 'Genera corso ora';
$string['generate_structure_btn'] = 'Genera';
$string['generate_structure_tooltip'] = 'Genera struttura del corso';
$string['generating_course'] = 'Attendere mentre prepariamo il tuo corso. Questo processo potrebbe richiedere alcuni minuti...';
$string['heading'] = 'Cosa vuoi insegnare oggi?';
$string['heading2'] = 'Stiamo creando il tuo corso!';
$string['invalidinput'] = 'Informazioni richieste.';
$string['invalidjson'] = 'JSON non valido';
$string['lastsection'] = 'Dopo l\'ultima sezione';
$string['lti_maxenrolled'] = 'Numero massimo di utenti iscritti';
$string['lti_maxenrolled_desc'] = 'Numero massimo di utenti che possono accedere tramite questo strumento LTI. 0 = nessun limite.';
$string['lti_membersync'] = 'Sincronizzazione iscrizioni';
$string['lti_membersync_desc'] = 'Sincronizza le iscrizioni degli utenti dalla piattaforma.';
$string['lti_membersyncmode'] = 'Modalità di sincronizzazione iscrizioni';
$string['lti_membersyncmode_desc'] = 'Scegli come sincronizzare le iscrizioni quando la sincronizzazione è attiva.';
$string['lti_publication'] = 'Pubblicazione LTI';
$string['lti_publication_desc'] = 'Se attivata, ai nuovi corsi viene aggiunto un metodo di iscrizione «Pubblica come strumento LTI». Richiede il plugin enrol_lti abilitato.';
$string['lti_publication_enabled'] = 'Aggiungi iscrizione LTI';
$string['lti_publication_enabled_desc'] = 'Se attivata, ai nuovi corsi verrà aggiunta un\'istanza di iscrizione LTI 1.3.';
$string['manage'] = 'Gestire il Progettatore di Corsi Dixeo';
$string['myaddinstance'] = 'Aggiungi un nuovo blocco Progettatore di Corsi Dixeo alla mia dashboard';
$string['pluginname'] = 'Progettatore di Corsi Dixeo';
$string['privacy:metadata:email'] = 'L\'indirizzo email dell\'utente che utilizza il progettista dei corsi.';
$string['privacy:metadata:external:files'] = 'File sorgente caricati per la generazione del corso che possono essere inviati a Dixeo.';
$string['privacy:metadata:externalpurpose'] = 'I prompt del progettista, i campi di identità dell\'utente, il contesto della struttura del corso e i file sorgente caricati vengono inviati ai servizi Dixeo per generare e affinare i corsi.';
$string['privacy:metadata:files'] = 'File sorgente caricati e immagini del corso generate memorizzati dal progettista.';
$string['privacy:metadata:firstname'] = 'Il nome dell\'utente che utilizza il progettista dei corsi.';
$string['privacy:metadata:lastname'] = 'Il cognome dell\'utente che utilizza il progettista dei corsi.';
$string['privacy:metadata:structure'] = 'JSON della struttura del corso memorizzato e metadati relativi alla generazione di immagini.';
$string['privacy:metadata:structure:description'] = 'Una breve descrizione memorizzata con la struttura del corso.';
$string['privacy:metadata:structure:imageerror'] = 'Dettagli di errore della generazione o modifica dell\'immagine del corso.';
$string['privacy:metadata:structure:imagejobid'] = 'L\'id del lavoro remoto per la generazione o modifica dell\'immagine del corso.';
$string['privacy:metadata:structure:imagestatus'] = 'Lo stato della generazione o modifica dell\'immagine del corso.';
$string['privacy:metadata:structure:jobid'] = 'L\'identificatore locale del lavoro del progettista per la struttura.';
$string['privacy:metadata:structure:structure'] = 'Il JSON della struttura del corso modificato nel progettista.';
$string['privacy:metadata:submission'] = 'Record di invio del progettista (prompt, modello, stato del lavoro, corso collegato).';
$string['privacy:metadata:submission:courseid'] = 'Il corso Moodle creato dall\'invio del progettista, quando disponibile.';
$string['privacy:metadata:submission:jobid'] = 'L\'identificatore locale del lavoro del progettista.';
$string['privacy:metadata:submission:prompt'] = 'La descrizione del corso o il prompt inserito dall\'utente.';
$string['privacy:metadata:submission:remotejobid'] = 'L\'identificatore remoto del lavoro Dixeo per operazioni asincrone.';
$string['privacy:metadata:submission:status'] = 'Lo stato del ciclo di vita dell\'invio del progettista.';
$string['privacy:metadata:submission:templateid'] = 'L\'identificatore del modello pedagogico selezionato.';
$string['privacy:metadata:timecreated'] = 'L\'ora in cui il record è stato creato.';
$string['privacy:metadata:timemodified'] = 'L\'ora in cui il record è stato modificato l\'ultima volta.';
$string['privacy:metadata:userid'] = 'L\'ID dell\'utente proprietario dei dati del progettista.';
$string['privacy:path:generatedimages'] = 'Immagini del corso generate';
$string['privacy:path:structures'] = 'Strutture del corso';
$string['privacy:path:submissions'] = 'Invii';
$string['prompt_placeholder'] = 'Inserisci il corso che vuoi generare: argomento, numero di sezioni e quiz se necessario.';
$string['regenerate_structure_tooltip'] = 'Rigenera la struttura del corso';
$string['removefile'] = 'Rimuovi file';
$string['resources'] = 'Risorse';
$string['self_enrol_configure'] = 'Configura l’autoiscrizione';
$string['self_enrol_configure_desc'] = 'Se attivata, l’autoiscrizione viene attivata per i nuovi corsi. Se non esiste un’istanza, ne viene creata una.';
$string['self_enrol_generate_key'] = 'Genera una chiave di iscrizione';
$string['self_enrol_generate_key_desc'] = 'Se attivata, viene impostata una chiave di iscrizione univoca. Se disattivata, non viene usata alcuna chiave (iscrizione aperta), salvo se l’impostazione a livello del sito del plugin enrol_self richiede una chiave; in tal caso viene comunque generata una chiave.';
$string['self_enrol_heading'] = 'Autoiscrizione';
$string['self_enrol_heading_desc'] = 'Opzioni per l’autoiscrizione quando viene creato un corso. Richiede il plugin enrol_self abilitato.';
$string['step_finalizing_details'] = 'Finalizzazione dettagli';
$string['step_generating_content'] = 'Generazione contenuti';
$string['step_generating_content_count'] = 'Generazione contenuti ({$a->current}/{$a->total})';
$string['step_generating_structure'] = 'Generazione struttura';
$string['step_preparing_files'] = 'Preparazione dei file';
$string['step_processing_prompt'] = 'Elaborazione della consegna';
$string['step_uploading_files'] = 'Elaborazione file';
$string['step_uploading_files_count'] = 'Elaborazione file ({$a->current}/{$a->total})';
$string['structurenotfound'] = 'Struttura del corso non trovata. Genera prima una struttura o riprova più tardi.';
$string['summarysection'] = 'Nel sommario del corso';
$string['task_cleanup_draft_courses'] = 'Elimina bozze di corso più vecchie di 1 ora';
$string['toggle_tooltip_hide'] = 'Nascondi il blocco di generazione';
$string['toggle_tooltip_show'] = 'Mostra il blocco di generazione';
$string['totalsize'] = '<b>Dimensione totale:</b> {$a}';
$string['totaltoolarge'] = 'La dimensione totale dei file supera il limite di 50MB. Carica file più piccoli o rimuovine uno per continuare.';
$string['uploaderror'] = 'Errore nel caricamento del file.';
$string['uploading_files'] = 'Caricamento…';
$string['view_course'] = 'Visualizza il tuo corso';
