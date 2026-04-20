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

/**
 * @see https://github.com/moodle/moodle/blob/v3.9.5/lib/classes/string_manager_standard.php#L171-L177
 */

/** @var array<string, string> $string */
$string = \array_merge($string, [
    // Plugin identity
    'modulename' => 'Jokko',
    'modulenameplural' => 'Jokko',
    'pluginadministration' => 'Jokko administration',
    'pluginname' => 'Jokko',
    // classes/privacy/provider.php
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_DATA => 'The Jokko user identifier as provided by the user',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_DATAFORMAT => 'The format in which the Jokko user identifier is stored in the database',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_EXPLANATION => 'The Jokko user identifier as provided by the user so they can be invited to a Jokko chat',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_FIELDID => 'The ID of the profile field',
    Plugin\Infrastructure\Internationalization::PRIVACY_METADATA_MATRIX_USER_ID_USERID => 'The ID of the user for which the Jokko user identifier is stored in the database',
    // mod_form.php
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_HEADER => 'Basic module settings',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME => 'Name',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_DEFAULT => 'Jokko Chat',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_ERROR_MAXLENGTH => \sprintf(
        'A name can not be longer than %d characters. Fewer characters than that will probably be better.',
        Plugin\Domain\ModuleName::LENGTH_MAX,
    ),
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_ERROR_REQUIRED => 'A name is required.',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_HELP => 'A good name will make it easier for users to tell Jokko rooms apart.',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_NAME_NAME => 'Name',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_ERROR_REQUIRED => 'A target is required. Where should the chat be opened?',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_LABEL_ELEMENT_URL => 'via the configured Element web URL',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_LABEL_MATRIX_TO => 'via https://matrix.to',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TARGET_NAME => 'Open chat in browser',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TOPIC_HELP => 'A topic will be displayed in the Jokko room, and could remind members of its purpose.',
    Plugin\Infrastructure\Internationalization::MOD_FORM_BASIC_SETTINGS_TOPIC_NAME => 'Topic',
    // settings.php
    Plugin\Infrastructure\Internationalization::SETTINGS_ACCESS_TOKEN_DESCRIPTION => 'The access token the Jokko bot should use to authenticate with your homeserver',
    Plugin\Infrastructure\Internationalization::SETTINGS_ACCESS_TOKEN_NAME => 'Access token',
    Plugin\Infrastructure\Internationalization::SETTINGS_ELEMENT_URL_DESCRIPTION => 'The URL to your Element web instance. If left empty, the Jokko chat will open via https://matrix.to. If provided, teachers can choose per module whether the Jokko chat will open via the configured Element web instance or https://matrix.to.',
    Plugin\Infrastructure\Internationalization::SETTINGS_ELEMENT_URL_NAME => 'Element web URL',
    Plugin\Infrastructure\Internationalization::SETTINGS_HOMESERVER_HEADING => 'Homeserver settings',
    Plugin\Infrastructure\Internationalization::SETTINGS_HOMESERVER_URL_DESCRIPTION => 'The URL where the Jokko bot should connect to your homeserver',
    Plugin\Infrastructure\Internationalization::SETTINGS_HOMESERVER_URL_NAME => 'Homeserver URL',
    // Error handling (lib.php)
    Plugin\Infrastructure\Internationalization::ERROR_HOMESERVER_UNREACHABLE => 'Unable to reach the Jokko homeserver',
    Plugin\Infrastructure\Internationalization::ERROR_HOMESERVER_UNREACHABLE_HELP => 'The activity could not be created or updated because the Jokko homeserver is not reachable. Please contact your administrator to verify the homeserver URL, the access token and the network connectivity.',
    // Scheduled task (db/tasks.php)
    Plugin\Infrastructure\Internationalization::TASK_POPULATE_MATRIX_USER_ID_NAME => 'Populate Jokko user identifiers from email',
    // view.php (actions)
    Plugin\Infrastructure\Internationalization::ACTION_EDIT_MATRIX_USER_ID_HEADER => 'Jokko user identifier missing',
    Plugin\Infrastructure\Internationalization::ACTION_EDIT_MATRIX_USER_ID_INFO_SUGGESTION => 'Perhaps one of the following is your Jokko user identifier? Just a guess!',
    Plugin\Infrastructure\Internationalization::ACTION_EDIT_MATRIX_USER_ID_WARNING_NO_MATRIX_USER_ID => 'It appears that you have not yet provided a valid Jokko user identifier. Without it, you cannot join any Jokko chat rooms. Can you provide one now?',
    Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_HEADER => 'Rooms',
    Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_WARNING_NO_ROOMS => 'There are no rooms to show.',
    // Edit form
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_ERROR_MATRIX_USER_ID_INVALID => 'The Jokko user identifier you provided appears to be invalid.',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_ERROR_MATRIX_USER_ID_REQUIRED => 'A Jokko user identifier is required, otherwise you cannot join Jokko chat rooms.',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_HEADER => 'Jokko user identifier',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME => 'Jokko user identifier',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_HELP => 'A Jokko user identifier looks like @localpart:domain, for example, @jane:example.org.',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_SUGGESTION => 'Suggestions',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_SUGGESTION_DEFAULT => 'Please select',
    Plugin\Infrastructure\Internationalization::FORM_EDIT_MATRIX_USER_ID_MATRIX_USER_ID_NAME_SUGGESTION_HELP => 'Too lazy to type? Perhaps one of these suggestions is your Jokko user identifier?',
    // Capabilities (db/access.php)
    'matrix:addinstance' => 'Add/edit Jokko room links',
    'matrix:staff' => 'Treat the user as a staff user in Jokko rooms',
    'matrix:view' => 'View Jokko room links',
]);
