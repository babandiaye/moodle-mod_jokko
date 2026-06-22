<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2026, UNCHK
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\Plugin\Infrastructure\Action;

use core\output;
use mod_matrix\Matrix;
use mod_matrix\Moodle;
use mod_matrix\Plugin;

/**
 * Action déclenchée au clic sur « Entrer dans le salon ».
 *
 * Au lieu d'inviter en masse tous les membres du cours/groupe à la création,
 * on invite UNIQUEMENT l'utilisateur courant, au moment où il demande à
 * rejoindre le salon, puis on le redirige vers le salon Matrix.
 */
final class JoinRoomAction
{
    private $roomRepository;
    private $matrixUserIdLoader;
    private $roomService;
    private $matrixRoomService;

    public function __construct(
        Plugin\Domain\RoomRepository $roomRepository,
        Plugin\Domain\MatrixUserIdLoader $matrixUserIdLoader,
        Plugin\Application\RoomService $roomService,
        Matrix\Application\RoomService $matrixRoomService
    ) {
        $this->roomRepository = $roomRepository;
        $this->matrixUserIdLoader = $matrixUserIdLoader;
        $this->roomService = $roomService;
        $this->matrixRoomService = $matrixRoomService;
    }

    public function handle(
        \stdClass $user,
        Plugin\Domain\Module $module,
        \cm_info $cm,
        int $roomId
    ): void {
        $viewUrl = new \moodle_url('/mod/matrix/view.php', [
            'id' => $cm->id,
        ]);

        $room = $this->roomRepository->findOneBy([
            'id' => $roomId,
        ]);

        // Le salon doit exister ET appartenir à ce module (anti-falsification d'ID).
        if (
            !$room instanceof Plugin\Domain\Room
            || $room->moduleId()->toInt() !== $module->id()->toInt()
        ) {
            redirect(
                $viewUrl,
                get_string(
                    Plugin\Infrastructure\Internationalization::ACTION_JOIN_ROOM_ERROR_NOT_ALLOWED,
                    Plugin\Application\Plugin::NAME,
                ),
                null,
                output\notification::NOTIFY_ERROR,
            );
        }

        // L'utilisateur doit avoir accès au groupe de ce salon.
        if (!self::userMayAccessRoom($user, $room, $module, $cm)) {
            redirect(
                $viewUrl,
                get_string(
                    Plugin\Infrastructure\Internationalization::ACTION_JOIN_ROOM_ERROR_NOT_ALLOWED,
                    Plugin\Application\Plugin::NAME,
                ),
                null,
                output\notification::NOTIFY_ERROR,
            );
        }

        $matrixUserId = $this->matrixUserIdLoader->load($user);

        // Pas d'identifiant Matrix : on renvoie vers la vue qui affichera le formulaire.
        if (!$matrixUserId instanceof Matrix\Domain\UserId) {
            redirect($viewUrl);
        }

        $isStaff = has_capability(
            'mod/matrix:staff',
            \context_course::instance($module->courseId()->toInt()),
            $user,
        );

        try {
            $this->matrixRoomService->inviteUserToRoom(
                $room->matrixRoomId(),
                $matrixUserId,
                $isStaff,
            );
        } catch (\RuntimeException $exception) {
            // Même traitement que lib.php : message lisible + log détaillé.
            debugging('Jokko homeserver error: ' . $exception->getMessage(), DEBUG_DEVELOPER);

            throw new \moodle_exception(
                Plugin\Infrastructure\Internationalization::ERROR_HOMESERVER_UNREACHABLE,
                Plugin\Application\Plugin::NAME,
                '',
                null,
                $exception->getMessage(),
            );
        }

        $url = $this->roomService->urlForRoom(
            $room,
            $matrixUserId,
        );

        redirect($url->toString());
    }

    private static function userMayAccessRoom(
        \stdClass $user,
        Plugin\Domain\Room $room,
        Plugin\Domain\Module $module,
        \cm_info $cm
    ): bool {
        $groupId = $room->groupId();

        // Salon « cours entier » : accessible à tout utilisateur ayant la capacité view.
        if (!$groupId instanceof Moodle\Domain\GroupId) {
            return true;
        }

        $courseContext = \context_course::instance($module->courseId()->toInt());

        // Le personnel accède à toutes les salles du cours.
        if (has_capability('mod/matrix:staff', $courseContext, $user)) {
            return true;
        }

        $allowedGroups = groups_get_activity_allowed_groups($cm, $user->id);

        foreach ($allowedGroups as $allowedGroup) {
            if ($groupId->equals(Moodle\Domain\GroupId::fromString((string) $allowedGroup->id))) {
                return true;
            }
        }

        return false;
    }
}
