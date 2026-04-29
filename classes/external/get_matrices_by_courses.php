<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2026, UNCHK
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\external;

\defined('MOODLE_INTERNAL') || exit();

global $CFG;

require_once $CFG->libdir . '/externallib.php';
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Web service externe : retourne la liste des activités Jokko (mod_matrix)
 * pour les cours fournis. Si aucun ID de cours n'est passé, l'utilisateur
 * authentifié récupère uniquement les activités des cours auxquels il a accès.
 *
 * Calque le comportement de mod_bigbluebuttonbn_get_bigbluebuttonbns_by_courses.
 */
final class get_matrices_by_courses extends \external_api
{
    public static function execute_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'courseids' => new \external_multiple_structure(
                new \external_value(\PARAM_INT, 'Course identifier'),
                'Array of course IDs (optional, defaults to all the user can see)',
                \VALUE_DEFAULT,
                [],
            ),
        ]);
    }

    public static function execute(array $courseids = []): array
    {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['courseids' => $courseids],
        );

        $warnings = [];
        $matrices = [];

        // Si aucun cours fourni, on prend tous ceux où l'utilisateur est inscrit.
        if (empty($params['courseids'])) {
            $userCourses = enrol_get_users_courses($USER->id, true);
            $params['courseids'] = \array_keys($userCourses);
        }

        if (empty($params['courseids'])) {
            return [
                'matrices' => [],
                'warnings' => [],
            ];
        }

        // Validation Moodle des IDs de cours (existence + droits).
        [$courses, $warnings] = \external_util::validate_courses(
            $params['courseids'],
            [],
            true,
        );

        if (empty($courses)) {
            return [
                'matrices' => [],
                'warnings' => $warnings,
            ];
        }

        // Récupération des activités Jokko pour les cours validés.
        [$insql, $inparams] = $DB->get_in_or_equal(
            \array_keys($courses),
            \SQL_PARAMS_NAMED,
        );

        $records = $DB->get_records_select(
            'matrix',
            "course $insql",
            $inparams,
            'course ASC, name ASC',
        );

        foreach ($records as $record) {
            try {
                $cm = get_coursemodule_from_instance(
                    'matrix',
                    $record->id,
                    $record->course,
                    false,
                    \MUST_EXIST,
                );
            } catch (\Exception $exception) {
                $warnings[] = [
                    'item' => 'matrix',
                    'itemid' => $record->id,
                    'warningcode' => 'cmnotfound',
                    'message' => 'Course module not found for matrix activity.',
                ];

                continue;
            }

            $context = \context_module::instance($cm->id);

            try {
                self::validate_context($context);
            } catch (\Exception $exception) {
                continue;
            }

            if (!has_capability('mod/matrix:view', $context)) {
                continue;
            }

            $rooms = $DB->get_records(
                'matrix_rooms',
                ['module_id' => (int) $record->id],
                'id ASC',
            );

            $course = $courses[$record->course] ?? null;

            $matrices[] = [
                'id' => (int) $record->id,
                'coursemodule' => (int) $cm->id,
                'course' => (int) $record->course,
                'course_shortname' => $course->shortname ?? '',
                'course_fullname' => $course->fullname ?? '',
                'name' => (string) ($record->name ?? ''),
                'topic' => (string) ($record->topic ?? ''),
                'target' => (string) ($record->target ?? ''),
                'section' => (int) ($record->section ?? 0),
                'timecreated' => (int) ($record->timecreated ?? 0),
                'timemodified' => (int) ($record->timemodified ?? 0),
                'rooms' => \array_map(static function ($room): array {
                    return [
                        'matrix_room_id' => (string) $room->matrix_room_id,
                        'group_id' => null === $room->group_id ? null : (int) $room->group_id,
                        'timecreated' => (int) ($room->timecreated ?? 0),
                    ];
                }, \array_values($rooms)),
            ];
        }

        return [
            'matrices' => $matrices,
            'warnings' => $warnings,
        ];
    }

    public static function execute_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'matrices' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(\PARAM_INT, 'Matrix activity id'),
                    'coursemodule' => new \external_value(\PARAM_INT, 'Course module id'),
                    'course' => new \external_value(\PARAM_INT, 'Course id'),
                    'course_shortname' => new \external_value(\PARAM_TEXT, 'Course short name'),
                    'course_fullname' => new \external_value(\PARAM_TEXT, 'Course full name'),
                    'name' => new \external_value(\PARAM_RAW, 'Activity name'),
                    'topic' => new \external_value(\PARAM_RAW, 'Activity topic'),
                    'target' => new \external_value(\PARAM_TEXT, 'Open target (matrix.to or element_url)'),
                    'section' => new \external_value(\PARAM_INT, 'Section number'),
                    'timecreated' => new \external_value(\PARAM_INT, 'Creation timestamp'),
                    'timemodified' => new \external_value(\PARAM_INT, 'Last modification timestamp'),
                    'rooms' => new \external_multiple_structure(
                        new \external_single_structure([
                            'matrix_room_id' => new \external_value(\PARAM_TEXT, 'Matrix room identifier'),
                            'group_id' => new \external_value(\PARAM_INT, 'Moodle group id (or null)', \VALUE_DEFAULT, null, \NULL_ALLOWED),
                            'timecreated' => new \external_value(\PARAM_INT, 'Room creation timestamp'),
                        ]),
                        'Matrix rooms attached to the activity (one per group, or one global)',
                    ),
                ]),
                'List of Jokko (Matrix) activities accessible to the user',
            ),
            'warnings' => new \external_warnings(),
        ]);
    }
}
