<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2026, UNCHK
 * @license   SPDX-License-Identifier: Apache-2.0
 */

\defined('MOODLE_INTERNAL') || exit();

$tasks = [
    [
        'classname' => \mod_matrix\task\populate_matrix_user_id_task::class,
        'blocking' => 0,
        // Toutes les heures, à la minute 15 (pour éviter les pics à :00).
        'minute' => '15',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];
