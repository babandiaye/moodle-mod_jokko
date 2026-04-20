<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2026, UNCHK
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\task;

use mod_matrix\Container;
use mod_matrix\Plugin;

\defined('MOODLE_INTERNAL') || exit();

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Tâche planifiée qui parcourt les utilisateurs Moodle et remplit
 * automatiquement leur champ profil `matrix_user_id` à partir de
 * l'email et du homeserver configuré dans l'admin du plugin.
 *
 * Ne touche jamais un champ déjà rempli avec une valeur valide.
 */
final class populate_matrix_user_id_task extends \core\task\scheduled_task
{
    public function get_name(): string
    {
        return get_string(
            Plugin\Infrastructure\Internationalization::TASK_POPULATE_MATRIX_USER_ID_NAME,
            Plugin\Application\Plugin::NAME,
        );
    }

    public function execute(): void
    {
        global $DB, $CFG;

        require_once $CFG->dirroot . '/user/profile/lib.php';

        $fieldId = $DB->get_field(
            'user_info_field',
            'id',
            ['shortname' => Plugin\Infrastructure\MoodleFunctionBasedMatrixUserIdLoader::USER_PROFILE_FIELD_NAME],
        );

        if (!$fieldId) {
            mtrace('[Jokko] Profile field "matrix_user_id" not found. Skipping task.');

            return;
        }

        $loader = Container::instance()->matrixUserIdLoader();

        if (!$loader instanceof Plugin\Infrastructure\MoodleFunctionBasedMatrixUserIdLoader) {
            mtrace('[Jokko] Unexpected loader implementation. Skipping task.');

            return;
        }

        $users = $DB->get_records_select(
            'user',
            "deleted = 0 AND suspended = 0 AND id > 1 AND email <> '' AND email IS NOT NULL",
            [],
            'id ASC',
            'id, auth, email, username',
        );

        $stats = [
            'updated' => 0,
            'already_set' => 0,
            'skipped' => 0,
        ];

        foreach ($users as $user) {
            $existing = $DB->get_field(
                'user_info_data',
                'data',
                [
                    'userid' => $user->id,
                    'fieldid' => $fieldId,
                ],
            );

            if (\is_string($existing) && \str_starts_with($existing, '@')) {
                ++$stats['already_set'];

                continue;
            }

            $generated = $loader->generateForUser($user);

            if (null === $generated) {
                ++$stats['skipped'];

                continue;
            }

            profile_save_custom_fields($user->id, [
                Plugin\Infrastructure\MoodleFunctionBasedMatrixUserIdLoader::USER_PROFILE_FIELD_NAME => $generated->toString(),
            ]);

            ++$stats['updated'];

            mtrace(\sprintf(
                '[Jokko] Saved %s for user #%d (%s)',
                $generated->toString(),
                $user->id,
                $user->username,
            ));
        }

        mtrace(\sprintf(
            '[Jokko] populate_matrix_user_id: updated=%d, already_set=%d, skipped=%d',
            $stats['updated'],
            $stats['already_set'],
            $stats['skipped'],
        ));
    }
}
