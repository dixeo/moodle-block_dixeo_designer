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

$string['attachfile'] = 'Joindre un document source';
$string['blocktitle'] = 'Concepteur de Cours Dixeo';
$string['toggle_tooltip_hide'] = 'Masquer le bloc de génération';
$string['toggle_tooltip_show'] = 'Afficher le bloc de génération';
$string['designacourse'] = 'Concevoir un cours';
$string['categoryname'] = 'Catégorie pour les cours créés';
$string['categoryname_desc'] = 'Saisissez le nom de la catégorie de cours de premier niveau dans laquelle seront placés les cours créés par le Concepteur de Cours Dixeo. La catégorie sera créée si elle n\'existe pas.';
$string['coursetemplate'] = 'Modèle de structure pédagogique';
$string['coursetemplate_desc'] = 'Sélectionnez le modèle de structure pédagogique utilisé par le Concepteur de Cours Dixeo.';
$string['coursetemplate_none'] = 'Aucun';
$string['course_generated'] = 'Votre cours «<b> {$a} </b>» a été généré avec succès !';
$string['default_categoryname'] = 'Cours Dixeo';

// Certificat (finalisation) — aligné sur local_edai.
$string['certificate_settings'] = 'Génération de certificat';
$string['certificate_settings_help'] = '';
$string['certificate_unavailable'] = 'La génération de certificat n\'est pas disponible. Veuillez installer les plugins Certificat de cours (mod_coursecertificate) et Outil Certificat (tool_certificate).';
$string['certificate_generation'] = 'Activer la génération de certificat';
$string['certificate_generation_description'] = 'Activer ou désactiver la génération de certificats de fin de cours.';
$string['certificate_template'] = 'Modèle de certificat';
$string['certificate_template_description'] = 'Sélectionnez le modèle à utiliser lors de la génération du certificat de cours.';
$string['certificate_location'] = 'Emplacement du certificat';
$string['certificate_location_description'] = 'Sélectionnez où le certificat sera affiché.';
$string['summarysection'] = 'Dans le résumé du cours';
$string['lastsection'] = 'Après la dernière section';
$string['certificate_section'] = 'Certificat de réussite';
$string['certificate_section_intro'] = 'Récupérez votre certificat de réussite une fois le cours terminé.';
$string['certificate_name'] = 'Certificat de réussite';

$string['dixeo_designer:addinstance'] = 'Ajouter un bloc Concepteur de Cours Dixeo';
$string['dixeo_designer:myaddinstance'] = 'Ajouter un nouveau bloc Concepteur de Cours Dixeo à mon tableau de bord';
$string['dixeo_designer:create'] = 'Créer des cours avec le Concepteur de Cours Dixeo';
$string['dixeo_designer:manage'] = 'Gérer le Concepteur de Cours Dixeo';
$string['manage'] = 'Gérer le Concepteur de Cours Dixeo';
$string['draganddrop'] = 'Glissez-déposez vos fichiers pour les télécharger';
$string['designer_unknown_error'] = 'Erreur inconnue';
$string['designer_instructions_too_short'] = 'Instructions must be at least {$a->min} characters.';
$string['error_title'] = 'Oups !';
$string['filetoolarge'] = 'Le fichier est trop volumineux. Veuillez télécharger un fichier de moins de 20 Mo.';
$string['filetypeinvalid'] = 'Le type de fichier {$a} n’est pas pris en charge. Extensions supportées : .pptx, .docx, .pdf, .txt.';
$string['generate_another'] = 'Générer un nouveau cours';
$string['generate_course'] = 'Générer';
$string['generate_course_tooltip'] = 'Générer le cours maintenant';
$string['generate_structure_btn'] = 'Générer';
$string['generate_structure_tooltip'] = 'Générer la structure du cours';
$string['regenerate_structure_tooltip'] = 'Régénérer la structure du cours';
$string['generating_course'] = 'Veuillez patienter pendant la préparation de votre cours. Ce processus peut prendre quelques minutes...';
$string['heading'] = 'Que voulez-vous enseigner aujourd’hui ?';
$string['heading2'] = 'Nous construisons votre cours !';
$string['invalidinput'] = 'Information requise.';
$string['myaddinstance'] = 'Ajouter un nouveau bloc Concepteur de Cours Dixeo à mon tableau de bord';
$string['pluginname'] = 'Concepteur de Cours Dixeo';
$string['privacy:metadata:email'] = 'L’adresse e-mail de l’utilisateur accédant au consommateur LTI';
$string['privacy:metadata:externalpurpose'] = 'Le consommateur LTI fournit des informations utilisateur et contexte au fournisseur d’outils LTI.';
$string['privacy:metadata:firstname'] = 'Le prénom de l’utilisateur accédant au consommateur LTI';
$string['privacy:metadata:lastname'] = 'Le nom de famille de l’utilisateur accédant au consommateur LTI';
$string['privacy:metadata:userid'] = 'L’ID de l’utilisateur accédant au consommateur LTI';
$string['prompt_placeholder'] = 'Indiquez le cours à générer : sujet, nombre de sections et quiz si nécessaire.';
$string['removefile'] = 'Supprimer le fichier';
$string['step_uploading_files'] = 'Traitement des fichiers';
$string['step_generating_structure'] = 'Génération de la structure';
$string['step_generating_content'] = 'Génération du contenu';
$string['step_finalizing_details'] = 'Finalisation des détails';
$string['totalsize'] = '<b>Taille totale :</b> {$a}';
$string['totaltoolarge'] = 'La taille totale des fichiers dépasse la limite de 50 Mo. Téléchargez des fichiers plus petits ou supprimez-en un pour continuer.';
$string['uploaderror'] = 'Erreur lors du téléchargement du fichier.';
$string['uploading_files'] = 'Téléchargement en cours…';
$string['step_uploading_files_count'] = 'Traitement des fichiers ({$a->current}/{$a->total})';
$string['step_generating_content_count'] = 'Génération du contenu ({$a->current}/{$a->total})';
$string['step_processing_prompt'] = 'Traitement de la consigne';
$string['step_preparing_files'] = 'Préparation des fichiers';
$string['view_course'] = 'Voir votre cours';
$string['create_course'] = 'Créer le cours';
$string['resources'] = 'Ressources';
$string['designer_draft_course_name'] = '[Brouillon] Nouveau cours';
$string['task_cleanup_draft_courses'] = 'Supprimer les brouillons de cours de plus d\'une heure';
$string['designer_default_file_prompt'] = 'Générer une structure de cours basée sur les fichiers téléchargés.';
$string['designer_default_module_prompt'] = 'Générer le contenu d\'apprentissage complet pour ce module.';
$string['designer_filesyncfailed'] = 'Les fichiers téléchargés n\'ont pas pu être synchronisés avant la génération du module : {$a}';
$string['designer_filesynctimeout'] = 'Les fichiers téléchargés n\'ont pas fini de se synchroniser à temps pour la génération du module.';

// Designer strings
$string['designer_loading'] = 'Chargement de la structure du cours...';
$string['designer_regenerate'] = 'Régénérer';
$string['designer_invalid_data'] = 'Données de structure invalides';
$string['structurenotfound'] = 'Structure du cours introuvable. Générez d’abord une structure ou réessayez plus tard.';
$string['designer_save'] = 'Enregistrer';
$string['designer_cancel'] = 'Annuler';
$string['designer_cancelling'] = 'Annulation en cours…';
$string['designer_edit'] = 'Modifier';
$string['designer_duplicate'] = 'Dupliquer';
$string['designer_delete'] = 'Supprimer';
$string['designer_confirm_delete'] = 'Confirmer la suppression';
$string['designer_delete_module_confirm'] = 'Êtes-vous sûr de vouloir supprimer ce module ?';
$string['designer_delete_section_confirm'] = 'Êtes-vous sûr de vouloir supprimer cette section et tous ses modules ?';
$string['designer_unsaved_changes'] = 'Vous avez des modifications non enregistrées. Êtes-vous sûr de vouloir quitter ?';
$string['designer_saving'] = 'Enregistrement...';
$string['designer_saved'] = 'Enregistré !';
$string['designer_add_section'] = 'Ajouter une nouvelle section';
$string['designer_add_activity'] = 'Ajouter une nouvelle activité';
$string['designer_undo'] = 'Annuler';
$string['designer_redo'] = 'Rétablir';
$string['designer_new_section_title'] = 'Nouvelle section';
$string['designer_new_section_summary'] = 'Décrivez le contenu de cette section';
$string['designer_new_module_type'] = 'Page';
$string['designer_new_module_title'] = 'Nouvelle page';
$string['designer_new_module_summary'] = 'Décrivez le contenu de cette activité';
$string['designer_new_module_instructions'] = 'Ajoutez des consignes pour les apprenants (facultatif)';
$string['designer_copy_suffix'] = ' (Copie)';
$string['designer_change_activity_type'] = 'Changer le type d\'activité';
$string['designer_expand_all'] = 'Tout développer';
$string['designer_collapse_all'] = 'Tout réduire';
$string['designer_module_summary_label'] = 'Résumé';
$string['designer_module_instructions_label'] = 'Consignes';
$string['designer_error_cancel_failed'] = 'Annulation impossible';
$string['designer_error_upload_failed'] = 'Échec du téléversement';
$string['designer_error_delete_failed'] = 'Suppression impossible';
$string['designer_error_status_check_failed'] = 'Échec de la vérification du statut';
$string['designer_error_structure_start_failed'] = 'Impossible de lancer la génération de la structure';
$string['designer_error_generation_failed_inline'] = 'Échec de la génération';
$string['designer_error_finalize_failed'] = 'Échec de la finalisation';
$string['designer_error_save_structure_failed'] = 'Impossible d’enregistrer la structure';
