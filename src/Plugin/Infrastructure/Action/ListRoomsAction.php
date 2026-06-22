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

        $entries = \array_map(function (Plugin\Domain\Room $room) use ($courseShortName, $module, $matrixUserId): array {
            $url = $this->roomService->urlForRoom(
                $room,
                $matrixUserId,
            );

            $groupId = $room->groupId();

            if (!$groupId instanceof Moodle\Domain\GroupId) {
                $name = $this->nameService->forCourseAndModule(
                    $courseShortName,
                    $module->name(),
                );
                $audience = 'Tous les participants';
            } else {
                $group = $this->moodleGroupRepository->find($groupId);

                if (!$group instanceof Moodle\Domain\Group) {
                    throw Moodle\Domain\GroupNotFound::for($groupId);
                }

                $name = $this->nameService->forGroupCourseAndModule(
                    $group->name(),
                    $courseShortName,
                    $module->name(),
                );
                $audience = $group->name()->toString();
            }

            return [
                'name' => $name->toString(),
                'url' => $url->toString(),
                'audience' => $audience,
                'timecreated' => $room->timecreated()->toInt(),
            ];
        }, $rooms);

        \usort($entries, static function (array $a, array $b): int {
            return \strcmp($a['name'], $b['name']);
        });

        $cards = \implode(\PHP_EOL, \array_map(static function (array $entry): string {
            $name = htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars($entry['url'], ENT_QUOTES, 'UTF-8');
            $audience = htmlspecialchars($entry['audience'], ENT_QUOTES, 'UTF-8');
            $created = $entry['timecreated'] > 0
                ? htmlspecialchars(userdate($entry['timecreated'], '%d %B %Y, %H:%M'), ENT_QUOTES, 'UTF-8')
                : '';

            $createdRow = '' === $created ? '' : <<<HTML
            <li>
                <svg class="jokko-meta-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span>Créé le {$created}</span>
            </li>
HTML;

            return <<<HTML
<div class="jokko-room-card">
    <div class="jokko-room-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path><line x1="8.5" y1="11.5" x2="8.5" y2="11.5"></line><line x1="12" y1="11.5" x2="12" y2="11.5"></line><line x1="15.5" y1="11.5" x2="15.5" y2="11.5"></line></svg>
    </div>
    <div class="jokko-room-body">
        <div class="jokko-room-title">{$name}</div>
        <ul class="jokko-room-meta">
            <li class="jokko-meta-ok">
                <svg class="jokko-meta-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>Ce salon est prêt. Vous pouvez le rejoindre maintenant.</span>
            </li>
            <li>
                <svg class="jokko-meta-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Participants autorisés : {$audience}</span>
            </li>
            <li>
                <svg class="jokko-meta-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 9.9-1"></path></svg>
                <span>Accès : Ouvert</span>
            </li>
{$createdRow}
        </ul>
    </div>
    <div class="jokko-room-action">
        <a href="{$url}" target="_blank" rel="noopener" class="jokko-room-btn" title="{$name}">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            <span>Entrer dans le salon</span>
        </a>
    </div>
</div>
HTML;
        }, $entries));

        echo $this->renderer->heading(get_string(
            Plugin\Infrastructure\Internationalization::ACTION_LIST_ROOMS_HEADER,
            Plugin\Application\Plugin::NAME,
        ));

        echo <<<HTML
<style>
.jokko-rooms-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin: 1rem 0;
}
.jokko-room-card {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem 1.75rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.jokko-room-card:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    border-color: #d1d5db;
}
.jokko-room-icon {
    flex: 0 0 72px;
    width: 72px;
    height: 72px;
    background: #eef2ff;
    color: #1d3557;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.jokko-room-body {
    flex: 1;
    min-width: 0;
}
.jokko-room-title {
    font-weight: 700;
    font-size: 1.4rem;
    color: #111827;
    margin-bottom: 0.75rem;
}
.jokko-room-meta {
    list-style: none;
    margin: 0;
    padding: 0;
}
.jokko-room-meta li {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: #374151;
    font-size: 0.95rem;
    padding: 0.18rem 0;
}
.jokko-meta-icon {
    flex: 0 0 18px;
    color: #6b7280;
}
.jokko-room-meta li.jokko-meta-ok .jokko-meta-icon {
    color: #16a34a;
}
.jokko-room-action {
    flex: 0 0 auto;
    border-left: 1px solid #e5e7eb;
    padding-left: 1.5rem;
}
.jokko-room-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1.5rem;
    background: #1d3557;
    color: #fff;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.15s ease, transform 0.15s ease;
}
.jokko-room-btn:hover,
.jokko-room-btn:focus {
    background: #16263f;
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}
@media (max-width: 720px) {
    .jokko-room-card {
        flex-direction: column;
        align-items: stretch;
        text-align: left;
    }
    .jokko-room-icon {
        align-self: flex-start;
    }
    .jokko-room-action {
        border-left: none;
        border-top: 1px solid #e5e7eb;
        padding-left: 0;
        padding-top: 1rem;
    }
    .jokko-room-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
<div class="jokko-rooms-list">
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
