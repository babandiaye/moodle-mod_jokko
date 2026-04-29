<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2026, UNCHK
 * @license   SPDX-License-Identifier: Apache-2.0
 *
 * @see https://docs.moodle.org/dev/Adding_a_web_service_to_a_plugin
 */

\defined('MOODLE_INTERNAL') || exit();

$functions = [
    'mod_matrix_get_matrices_by_courses' => [
        'classname' => \mod_matrix\external\get_matrices_by_courses::class,
        'methodname' => 'execute',
        'description' => 'Returns a list of Jokko (Matrix) activities for the given courses. ' .
                         'If no course IDs are provided, returns activities from all courses ' .
                         'the calling user can view.',
        'classpath' => '',
        'type' => 'read',
        'capabilities' => 'mod/matrix:view',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
    ],
];
