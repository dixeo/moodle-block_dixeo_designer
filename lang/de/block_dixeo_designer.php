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

// General.
$string['pluginname'] = 'Dixeo-Kursdesigner';
$string['blocktitle'] = 'Dixeo-Kursdesigner';
$string['toggle_tooltip_hide'] = 'Generierungsblock ausblenden';
$string['toggle_tooltip_show'] = 'Generierungsblock anzeigen';
$string['designacourse'] = 'Einen Kurs gestalten';

// Capabilities.
$string['dixeo_designer:addinstance'] = 'Einen Dixeo-Kursdesigner-Block hinzufügen';
$string['dixeo_designer:myaddinstance'] = 'Einen neuen Dixeo-Kursdesigner-Block zu meiner Übersicht hinzufügen';
$string['dixeo_designer:create'] = 'Kurse mit dem Dixeo-Kursdesigner erstellen';
$string['dixeo_designer:manage'] = 'Dixeo-Kursdesigner verwalten';
$string['manage'] = 'Dixeo-Kursdesigner verwalten';
$string['myaddinstance'] = 'Einen neuen Dixeo-Kursdesigner-Block zu meiner Übersicht hinzufügen';

// Platform settings.
$string['categoryname'] = 'Kategorie für erstellte Kurse';
$string['categoryname_desc'] = 'Geben Sie den Namen der übergeordneten Kurskategorie ein, in der vom Dixeo-Kursdesigner erstellte Kurse abgelegt werden. Die Kategorie wird angelegt, falls sie noch nicht existiert.';
$string['coursetemplate'] = 'Vorlage für die pädagogische Struktur';
$string['coursetemplate_desc'] = 'Wählen Sie die vom Dixeo-Kursdesigner verwendete Vorlage für die pädagogische Struktur.';
$string['coursetemplate_none'] = 'Keine';
$string['default_categoryname'] = 'Dixeo-Kurse';

// Zertifikat (Abschluss) — wie local_edai.
$string['certificate_settings'] = 'Zertifikatsgenerierung';
$string['certificate_settings_help'] = '';
$string['certificate_unavailable'] = 'Die Zertifikatsgenerierung ist nicht verfügbar. Bitte installieren Sie die Plugins Course Certificate (mod_coursecertificate) und Tool Certificate (tool_certificate).';
$string['certificate_generation'] = 'Zertifikatsgenerierung aktivieren';
$string['certificate_generation_description'] = 'Aktivieren oder deaktivieren Sie die Generierung von Kursabschlusszertifikaten.';
$string['certificate_template'] = 'Zertifikatsvorlage';
$string['certificate_template_description'] = 'Wählen Sie die Vorlage für die Kurszertifikatsgenerierung.';
$string['certificate_location'] = 'Zertifikatsspeicherort';
$string['certificate_location_description'] = 'Wählen Sie, wo das Zertifikat angezeigt wird.';
$string['summarysection'] = 'In der Kursübersicht';
$string['lastsection'] = 'Nach dem letzten Abschnitt';
$string['certificate_section'] = 'Teilnahmezertifikat';
$string['certificate_section_intro'] = 'Rufen Sie Ihr Teilnahmezertifikat nach Abschluss des Kurses ab.';
$string['certificate_name'] = 'Teilnahmezertifikat';

// Course design flow.
$string['heading'] = 'Was möchten Sie heute unterrichten?';
$string['heading2'] = 'Wir erstellen Ihren Kurs!';
$string['prompt_placeholder'] = 'Geben Sie den gewünschten Kurs ein: Thema, Anzahl der Abschnitte und ggf. Quiz.';
$string['generate_course'] = 'Generieren';
$string['generate_course_tooltip'] = 'Kurs jetzt generieren';
$string['generate_structure_btn'] = 'Generieren';
$string['generate_structure_tooltip'] = 'Kursstruktur generieren';
$string['regenerate_structure_tooltip'] = 'Kursstruktur neu generieren';
$string['generate_another'] = 'Neuen Kurs generieren';
$string['generating_course'] = 'Bitte warten Sie, wir bereiten Ihren Kurs vor. Dies kann einige Minuten dauern...';
$string['course_generated'] = 'Ihr Kurs «<b> {$a} </b>» wurde erfolgreich erstellt!';
$string['view_course'] = 'Kurs anzeigen';
$string['create_course'] = 'Kurs erstellen';
$string['resources'] = 'Ressourcen';
$string['designer_draft_course_name'] = '[Entwurf] Neuer Kurs';
$string['task_cleanup_draft_courses'] = 'Kursentwürfe älter als 1 Stunde löschen';
$string['designer_default_file_prompt'] = 'Eine Kursstruktur auf Basis der hochgeladenen Dateien generieren.';
$string['designer_default_module_prompt'] = 'Den vollständigen Lerninhalt für dieses Modul generieren.';
$string['designer_filesyncfailed'] = 'Die hochgeladenen Dateien konnten vor der Modulgenerierung nicht synchronisiert werden: {$a}';
$string['designer_filesynctimeout'] = 'Die hochgeladenen Dateien wurden nicht rechtzeitig für die Modulgenerierung synchronisiert.';
$string['step_uploading_files'] = 'Dateien werden verarbeitet';
$string['step_generating_structure'] = 'Struktur wird generiert';
$string['step_generating_content'] = 'Inhalt wird generiert';
$string['step_finalizing_details'] = 'Details werden fertiggestellt';
$string['invalidinput'] = 'Angaben erforderlich.';
$string['error_title'] = 'Hoppla!';
$string['designer_unknown_error'] = 'Unbekannter Fehler';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';

// File uploads.
$string['attachfile'] = 'Quelldokument anhängen';
$string['draganddrop'] = 'Dateien zum Hochladen hierher ziehen';
$string['removefile'] = 'Datei entfernen';
$string['totalsize'] = '<b>Gesamtgröße:</b> {$a}';
$string['filetoolarge'] = 'Datei ist zu groß. Bitte laden Sie eine Datei unter 20 MB hoch.';
$string['filetypeinvalid'] = 'Der Dateityp {$a} wird nicht unterstützt. Unterstützte Formate: .pptx, .docx, .pdf, .txt.';
$string['totaltoolarge'] = 'Die Gesamtgröße der Dateien überschreitet das Limit von 50 MB. Laden Sie kleinere Dateien hoch oder entfernen Sie eine Datei.';
$string['uploaderror'] = 'Fehler beim Hochladen der Datei.';
$string['uploading_files'] = 'Wird hochgeladen…';
$string['step_uploading_files_count'] = 'Dateien werden verarbeitet ({$a->current}/{$a->total})';
$string['step_generating_content_count'] = 'Inhalt wird generiert ({$a->current}/{$a->total})';
$string['step_processing_prompt'] = 'Aufgabenstellung wird verarbeitet';
$string['step_preparing_files'] = 'Dateien werden vorbereitet';

// Designer interface.
$string['designer_loading'] = 'Kursstruktur wird geladen...';
$string['designer_regenerate'] = 'Neu generieren';
$string['designer_invalid_data'] = 'Ungültige Strukturdaten';
$string['structurenotfound'] = 'Kursstruktur nicht gefunden. Erstellen Sie zuerst eine Struktur oder versuchen Sie es später erneut.';
$string['designer_save'] = 'Speichern';
$string['designer_cancel'] = 'Abbrechen';
$string['designer_cancelling'] = 'Wird abgebrochen…';
$string['designer_edit'] = 'Bearbeiten';
$string['designer_duplicate'] = 'Duplizieren';
$string['designer_delete'] = 'Löschen';
$string['designer_confirm_delete'] = 'Löschen bestätigen';
$string['designer_delete_module_confirm'] = 'Möchten Sie dieses Modul wirklich löschen?';
$string['designer_delete_section_confirm'] = 'Möchten Sie diesen Abschnitt und alle zugehörigen Module wirklich löschen?';
$string['designer_unsaved_changes'] = 'Sie haben ungespeicherte Änderungen. Möchten Sie wirklich fortfahren?';
$string['designer_saving'] = 'Wird gespeichert...';
$string['designer_saved'] = 'Gespeichert!';
$string['designer_add_section'] = 'Neuen Abschnitt hinzufügen';
$string['designer_add_activity'] = 'Neue Aktivität hinzufügen';
$string['designer_undo'] = 'Rückgängig';
$string['designer_redo'] = 'Wiederholen';
$string['designer_new_section_title'] = 'Neuer Abschnitt';
$string['designer_new_section_summary'] = 'Beschreiben Sie, worum es in diesem Abschnitt geht';
$string['designer_new_module_type'] = 'Seite';
$string['designer_new_module_title'] = 'Neue Seite';
$string['designer_new_module_summary'] = 'Beschreiben Sie, worum es in dieser Aktivität geht';
$string['designer_new_module_instructions'] = 'Fügen Sie Anweisungen für Lernende hinzu (optional)';
$string['designer_copy_suffix'] = ' (Kopie)';
$string['designer_change_activity_type'] = 'Aktivitätstyp ändern';
$string['designer_expand_all'] = 'Alle aufklappen';
$string['designer_collapse_all'] = 'Alle zuklappen';
$string['designer_module_summary_label'] = 'Zusammenfassung';
$string['designer_module_instructions_label'] = 'Anweisungen';
$string['designer_error_cancel_failed'] = 'Abbrechen fehlgeschlagen';
$string['designer_error_upload_failed'] = 'Hochladen fehlgeschlagen';
$string['designer_error_delete_failed'] = 'Löschen fehlgeschlagen';
$string['designer_error_status_check_failed'] = 'Statusabfrage fehlgeschlagen';
$string['designer_error_structure_start_failed'] = 'Strukturgenerierung konnte nicht gestartet werden';
$string['designer_error_generation_failed_inline'] = 'Generierung fehlgeschlagen';
$string['designer_error_finalize_failed'] = 'Finalisierung fehlgeschlagen';
$string['designer_error_save_structure_failed'] = 'Struktur konnte nicht gespeichert werden';

// Privacy.
$string['privacy:metadata:userid'] = 'Die ID der Nutzerin/des Nutzers beim Zugriff auf den LTI Consumer';
$string['privacy:metadata:email'] = 'E-Mail-Adresse der Nutzerin/des Nutzers beim Zugriff auf den LTI Consumer';
$string['privacy:metadata:firstname'] = 'Vorname der Nutzerin/des Nutzers beim Zugriff auf den LTI Consumer';
$string['privacy:metadata:lastname'] = 'Nachname der Nutzerin/des Nutzers beim Zugriff auf den LTI Consumer';
$string['privacy:metadata:externalpurpose'] = 'Der LTI Consumer übermittelt Nutzerinformationen und Kontext an den LTI Tool Provider.';
