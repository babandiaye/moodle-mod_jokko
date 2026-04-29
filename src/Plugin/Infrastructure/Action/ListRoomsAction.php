<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2022, New Vector Ltd
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix\Plugin\Infrastructure\Action;

use core\output;
use mod_matrix\Moodle;
use mod_matrix\Plugin;

final class ListRoomsAction
{
    private $roomRepository;
    private $moodleGroupRepository;
    private $matrixUserIdLoader;
    private $roomService;
    private $nameService;
    private $renderer;

    public function __construct(
        Plugin\Domain\RoomRepository $roomRepository,
        Moodle\Domain\GroupRepository $moodleGroupRepository,
        Plugin\Domain\MatrixUserIdLoader $matrixUserIdLoader,
        Plugin\Application\RoomService $roomService,
        Plugin\Application\NameService $nameService,
        \core_renderer $renderer
    ) {
        $this->roomRepository = $roomRepository;
        $this->moodleGroupRepository = $moodleGroupRepository;
        $this->matrixUserIdLoader = $matrixUserIdLoader;
        $this->roomService = $roomService;
        $this->nameService = $nameService;
        $this->renderer = $renderer;
    }

    public function handle(
        \stdClass $user,
        Plugin\Domain\Module $module,
        \cm_info $cm
    ): void {
        $isStaff = self::isStaffUserInCourseContext(
            $user,
            $module->courseId(),
        );

        $rooms = $this->rooms(
            $module,
            $isStaff,
            $cm,
            $user,
        );

        if ([] === $rooms) {
            echo $this->renderer->heading(get_string(
                Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_HEADER,
                Plugin\Application\Plugin::NAME,
            ));

            echo $this->renderer->notification(
                get_string(
                    Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_WARNING_NO_ROOMS,
                    Plugin\Application\Plugin::NAME,
                ),
                output\notification::NOTIFY_WARNING,
            );

            echo $this->renderer->footer();
            return;
        }

        $matrixUserId = $this->matrixUserIdLoader->load($user);
        $courseShortName = Moodle\Domain\CourseShortName::fromString($cm->get_course()->shortname);

        $roomLinks = \array_map(function (Plugin\Domain\Room $room) use ($courseShortName, $module, $matrixUserId): Plugin\Domain\RoomLink {
            $url = $this->roomService->urlForRoom(
                $room,
                $matrixUserId,
            );

            $groupId = $room->groupId();

            if (!$groupId instanceof Moodle\Domain\GroupId) {
                return Plugin\Domain\RoomLink::create(
                    $url,
                    $this->nameService->forCourseAndModule(
                        $courseShortName,
                        $module->name(),
                    ),
                );
            }

            $group = $this->moodleGroupRepository->find($groupId);

            if (!$group instanceof Moodle\Domain\Group) {
                throw Moodle\Domain\GroupNotFound::for($groupId);
            }

            return Plugin\Domain\RoomLink::create(
                $url,
                $this->nameService->forGroupCourseAndModule(
                    $group->name(),
                    $courseShortName,
                    $module->name(),
                ),
            );
        }, $rooms);

        if (!$isStaff && \count($roomLinks) === 1) {
            $roomLink = \reset($roomLinks);

            echo $this->renderer->heading(get_string(
                Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_HEADER,
                Plugin\Application\Plugin::NAME,
            ));

            echo <<<HTML
<script type="text/javascript">
    window.location.href = '{$roomLink->url()->toString()}';
</script>
HTML;

            echo $this->renderer->footer();
            return;
        }

        \usort($roomLinks, static function (Plugin\Domain\RoomLink $a, Plugin\Domain\RoomLink $b): int {
            return \strcmp(
                $a->roomName()->toString(),
                $b->roomName()->toString(),
            );
        });

        $cards = \implode(\PHP_EOL, \array_map(static function (Plugin\Domain\RoomLink $link): string {
            $name = htmlspecialchars($link->roomName()->toString(), ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars($link->url()->toString(), ENT_QUOTES, 'UTF-8');

            return <<<HTML
<a href="{$url}" target="_blank" rel="noopener" class="jokko-room-card" title="{$name}">
    <div class="jokko-room-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </div>
    <div class="jokko-room-body">
        <div class="jokko-room-name">{$name}</div>
        <div class="jokko-room-cta">Rejoindre le salon &rarr;</div>
    </div>
</a>
HTML;
        }, $roomLinks));

        echo $this->renderer->heading(get_string(
            Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_HEADER,
            Plugin\Application\Plugin::NAME,
        ));

        echo <<<HTML
<style>
.jokko-rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}
.jokko-room-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #1d3557 0%, #2a9d8f 100%);
    border-radius: 12px;
    color: #fff;
    text-decoration: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.jokko-room-card:hover,
.jokko-room-card:focus {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    color: #fff;
    text-decoration: none;
}
.jokko-room-icon {
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.jokko-room-body {
    flex: 1;
    min-width: 0;
}
.jokko-room-name {
    font-weight: 600;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.jokko-room-cta {
    font-size: 0.85rem;
    opacity: 0.85;
    margin-top: 0.25rem;
}
</style>
<div class="jokko-rooms-grid">
    {$cards}
</div>
HTML;

        echo $this->renderer->footer();
    }

    private static function isStaffUserInCourseContext(
        \stdClass $user,
        Moodle\Domain\CourseId $courseId
    ): bool {
        $context = \context_course::instance($courseId->toInt());
        $staffUsersInCourseContext = get_users_by_capability($context, 'mod/matrix:staff');

        foreach ($staffUsersInCourseContext as $staffUserInCourseContext) {
            if ($user->id === $staffUserInCourseContext->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, Plugin\Domain\Room>
     */
    private function rooms(
        Plugin\Domain\Module $module,
        bool $isStaff,
        \cm_info $cm,
        \stdClass $user
    ): array {
        $rooms = $this->roomRepository->findAllBy([
            'module_id' => $module->id()->toInt(),
        ]);

        if ($isStaff) {
            return $rooms;
        }

        // ✅ Correction : passer l'ID de l'utilisateur (entier) au lieu de l'objet complet
        $groupsVisibleToUser = groups_get_activity_allowed_groups(
            $cm,
            $user->id,
        );

        return \array_filter($rooms, static function (Plugin\Domain\Room $room) use ($groupsVisibleToUser): bool {
            if (!$room->groupId() instanceof Moodle\Domain\GroupId) {
                return true;
            }

            foreach ($groupsVisibleToUser as $groupVisibleToUser) {
                if ($room->groupId()->equals(Moodle\Domain\GroupId::fromString($groupVisibleToUser->id))) {
                    return true;
                }
            }

            return false;
        });
    }
}
