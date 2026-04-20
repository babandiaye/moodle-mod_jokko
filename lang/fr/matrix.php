<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2022, New Vector Ltd (Trading as Element)
 * @license   SPDX-License-Identifier: Apache-2.0
 */

use mod_matrix\Moodle;
use mod_matrix\Plugin;

\defined('MOODLE_INTERNAL') || exit();

require_once __DIR__ . '/../../vendor/autoload.php';

/** @var array<string, string> $string */
$string = \array_merge($string, [
    // Identité du plugin
    'modulename' => 'Jokko',
    'modulenameplural' => 'Jokko',
    'pluginadministration' => 'Administration de Jokko',
    'pluginname' => 'Jokko',
    // classes/privacy/provider.php
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_DATA => 'L’identifiant utilisateur Jokko tel que fourni par l’utilisateur',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_DATAFORMAT => 'Le format dans lequel l’identifiant utilisateur Jokko est stocké dans la base de données',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_EXPLANATION => 'L’identifiant utilisateur Jokko fourni par l’utilisateur afin qu’il puisse être invité dans un salon de discussion Jokko',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_FIELDID => 'L’identifiant du champ de profil',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_USERID => 'L’identifiant de l’utilisateur pour lequel l’identifiant utilisateur Jokko est stocké dans la base de données',
    // mod_form.php
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_HEADER => 'Paramètres de base du module',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME => 'Nom',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_DEFAULT => 'Discussion Jokko',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_ERROR_MAXLENGTH => \sprintf(
        'Le nom ne peut pas dépasser %d caractères. Un nom plus court sera probablement plus lisible.',
        Plugin\Domain\ModuleName::LENGTH_MAX,
    ),
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_ERROR_REQUIRED => 'Un nom est requis.',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_HELP => 'Un bon nom permettra aux utilisateurs de distinguer plus facilement les salons Jokko.',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_NAME => 'Nom',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_ERROR_REQUIRED => 'Une cible est requise. Où la discussion doit-elle s’ouvrir ?',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_LABEL_ELEMENT_URL => 'via l’URL Element web configurée',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_LABEL_MATRIX_TO => 'via https://matrix.to',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_NAME => 'Ouvrir la discussion dans le navigateur',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TOPIC_HELP => 'Un sujet s’affichera dans le salon Jokko et pourra rappeler aux membres son objet.',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TOPIC_NAME => 'Sujet',
    // settings.php
    Plugin\Infrastructure\Internationalization::SETTINGS_ACCESS_TOKEN_DESCRIPTION => 'Le jeton d’accès que le bot Jokko doit utiliser pour s’authentifier auprès de votre homeserver',
    Plugin\Infrastructure\Internationalization::SETTINGS_ACCESS_TOKEN_NAME => 'Jeton d’accès',
    Plugin\Infrastructure\Internationalization::SETTINGS_ELEMENT_URL_DESCRIPTION => 'L’URL de votre instance Element web. Si laissée vide, la discussion Jokko s’ouvrira via https://matrix.to. Si renseignée, les enseignants pourront choisir par module si la discussion Jokko s’ouvre via l’instance Element web configurée ou via https://matrix.to.',
    Plugin\Infrastructure\Internationalization::SETTINGS_ELEMENT_URL_NAME => 'URL d’Element web',
    Plugin\Infrastructure\Internationalization::SETTINGS_HOMESERVER_HEADING => 'Paramètres du homeserver',
    Plugin\Infrastructure\Internationalization::SETTINGS_HOMESERVER_URL_DESCRIPTION => 'L’URL à laquelle le bot Jokko doit se connecter à votre homeserver',
    Plugin\Infrastructure\Internationalization::SETTINGS_HOMESERVER_URL_NAME => 'URL du homeserver',
    // view.php (actions)
    Plugin\Infrastructure\Internationalization::ACTION_EDIT_MATRIX_USER_ID_HEADER => 'Identifiant utilisateur Jokko manquant',
    Plugin\Infrastructure\Internationalization::ACTION_EDIT_MATRIX_USER_ID_INFO_SUGGESTION => 'Peut-être que l’un des identifiants suivants est le vôtre ? (Ceci est une suggestion automatique)',
    Plugin\Infrastructure\Internationalization::ACTION_EDIT_MATRIX_USER_ID_WARNING_NO_MATRIX_USER_ID => 'Il semble que vous n’ayez pas encore fourni d’identifiant utilisateur Jokko valide. Sans celui-ci, vous ne pouvez rejoindre aucun salon de discussion Jokko. Pouvez-vous en fournir un maintenant ?',
    Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_HEADER => 'Salons',
    Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_WARNING_NO_ROOMS => 'Aucun salon à afficher.',
    // Formulaire d’édition
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_ERROR_MATRIX_USER_ID_INVALID => 'L’identifiant utilisateur Jokko que vous avez fourni semble invalide.',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_ERROR_MATRIX_USER_ID_REQUIRED => 'Un identifiant utilisateur Jokko est requis, sinon vous ne pourrez pas rejoindre les salons de discussion Jokko.',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_HEADER => 'Identifiant utilisateur Jokko',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME => 'Identifiant utilisateur Jokko',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_HELP => 'Un identifiant utilisateur Jokko ressemble à @utilisateur:domaine, par exemple @jane:example.org.',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_SUGGESTION => 'Suggestions',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_SUGGESTION_DEFAULT => 'Veuillez sélectionner',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_SUGGESTION_HELP => 'Pas envie de taper ? Peut-être que l’une de ces suggestions est votre identifiant utilisateur Jokko ?',
    // Capabilities (db/access.php)
    'matrix:addinstance' => 'Ajouter ou modifier des liens vers des salons Jokko',
    'matrix:staff' => 'Considérer l’utilisateur comme un membre du personnel dans les salons Jokko',
    'matrix:view' => 'Voir les liens des salons Jokko',
]);
