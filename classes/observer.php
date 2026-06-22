<?php

declare(strict_types=1);

/**
 * @package   mod_matrix
 * @copyright 2022, New Vector Ltd (Trading as Element)
 * @license   SPDX-License-Identifier: Apache-2.0
 */

namespace mod_matrix;

use core\event;

\defined('MOODLE_INTERNAL') || exit();

/**
 * This hack is required because moodle caches observers, and when they are, our autoloader is not required.
 *
 * This class needs to stay in this location so moodle's own autoloader can kick in.
 *
 * @see https://github.com/moodle/moodle/blob/v3.9.5/lib/classes/event/manager.php#L192-L240
 */
final class observer
{
    /**
     * Depuis l'invitation paresseuse (l'utilisateur est invité au clic sur
     * « Entrer dans le salon »), on n'écoute plus les événements d'inscription,
     * de rôle ni d'adhésion à un groupe : aucune synchronisation de membres en
     * masse n'est nécessaire.
     *
     * On conserve uniquement les événements qui agissent sur l'EXISTENCE et les
     * MÉTADONNÉES des salles : création/suppression d'un groupe (1 salle par
     * groupe), renommage du cours / du module / du groupe.
     *
     * @see https://github.com/moodle/moodle/blob/02a2e649e92d570c7fa735bf05f69b588036f761/lib/classes/event/manager.php#L222-L230
     */
    public static function observers(): array
    {
        $map = [
            event\course_module_updated::class => [
                self::class,
                'onCourseModuleUpdated',
            ],
            event\course_updated::class => [
                self::class,
                'onCourseUpdated',
            ],
            event\group_created::class => [
                self::class,
                'onGroupCreated',
            ],
            event\group_deleted::class => [
                self::class,
                'onGroupDeleted',
            ],
            event\group_updated::class => [
                self::class,
                'onGroupUpdated',
            ],
        ];

        return \array_map(static function (string $event, array $callback): array {
            return [
                'callback' => $callback,
                'eventname' => $event,
                'internal' => false,
            ];
        }, \array_keys($map), \array_values($map));
    }

    public static function onCourseModuleUpdated(event\course_module_updated $event): void
    {
        self::requireAutoloader();

        $other = $event->other;

        if (!\array_key_exists('instanceid', $other)) {
            return;
        }

        $instanceid = $other['instanceid'];

        if (!\is_string($instanceid)) {
            return;
        }

        if (!\array_key_exists('name', $other)) {
            return;
        }

        $name = $other['name'];

        if (!\is_string($name)) {
            return;
        }

        $courseId = Moodle\Domain\CourseId::fromString((string) $event->courseid);
        $moduleId = Plugin\Domain\ModuleId::fromString($instanceid);
        $moduleName = Plugin\Domain\ModuleName::fromString($name);

        self::updateRoomsForModuleFollowingUpdateOfModuleName(
            $courseId,
            $moduleId,
            $moduleName,
        );
    }

    public static function onCourseUpdated(event\course_updated $event): void
    {
        self::requireAutoloader();

        $other = $event->other;

        if (!\array_key_exists('updatedfields', $other)) {
            return;
        }

        $updatedFields = $other['updatedfields'];

        if (!\is_array($updatedFields)) {
            return;
        }

        if (!\array_key_exists('shortname', $updatedFields)) {
            return;
        }

        $shortname = $updatedFields['shortname'];

        if (!\is_string($shortname)) {
            return;
        }

        $courseId = Moodle\Domain\CourseId::fromString((string) $event->courseid);
        $courseShortName = Moodle\Domain\CourseShortName::fromString($shortname);

        self::updateRoomsForCourseFollowingUpdateOfCourseShortName(
            $courseId,
            $courseShortName,
        );
    }

    public static function onGroupCreated(event\group_created $event): void
    {
        self::requireAutoloader();

        $courseId = Moodle\Domain\CourseId::fromString((string) $event->courseid);
        $groupId = Moodle\Domain\GroupId::fromString((string) $event->objectid);

        self::createRoomsForCourseAndGroup(
            $courseId,
            $groupId,
        );
    }

    public static function onGroupDeleted(event\group_deleted $event): void
    {
        self::requireAutoloader();

        $groupId = Moodle\Domain\GroupId::fromString((string) $event->objectid);

        self::removeRoomsForGroup($groupId);
    }

    public static function onGroupUpdated(event\group_updated $event): void
    {
        self::requireAutoloader();

        $groupId = Moodle\Domain\GroupId::fromString((string) $event->objectid);

        self::updateRoomsForGroup($groupId);
    }

    /**
     * Crée la salle d'un groupe pour chaque module Jokko du cours, si elle
     * n'existe pas encore. La salle est créée VIDE (seul le bot y est) : les
     * membres seront invités au clic sur « Entrer dans le salon ».
     *
     * @throws Moodle\Domain\CourseNotFound
     * @throws Moodle\Domain\GroupNotFound
     */
    private static function createRoomsForCourseAndGroup(
        Moodle\Domain\CourseId $courseId,
        Moodle\Domain\GroupId $groupId
    ): void {
        $container = Container::instance();

        $course = $container->moodleCourseRepository()->find($courseId);

        if (!$course instanceof Moodle\Domain\Course) {
            throw Moodle\Domain\CourseNotFound::for($courseId);
        }

        $group = $container->moodleGroupRepository()->find($groupId);

        if (!$group instanceof Moodle\Domain\Group) {
            throw Moodle\Domain\GroupNotFound::for($groupId);
        }

        $modules = $container->moduleRepository()->findAllBy([
            'course' => $courseId->toInt(),
        ]);

        $roomRepository = $container->roomRepository();
        $roomService = $container->roomService();

        foreach ($modules as $module) {
            $room = $roomRepository->findOneBy([
                'module_id' => $module->id()->toInt(),
                'group_id' => $group->id()->toInt(),
            ]);

            if ($room instanceof Plugin\Domain\Room) {
                continue;
            }

            $roomService->createRoomForCourseAndGroup(
                $course,
                $group,
                $module,
            );
        }
    }

    /**
     * @throws Moodle\Domain\CourseNotFound
     */
    private static function updateRoomsForCourseFollowingUpdateOfCourseShortName(
        Moodle\Domain\CourseId $courseId,
        Moodle\Domain\CourseShortName $courseShortName
    ): void {
        $container = Container::instance();

        $course = $container->moodleCourseRepository()->find($courseId);

        if (!$course instanceof Moodle\Domain\Course) {
            throw Moodle\Domain\CourseNotFound::for($courseId);
        }

        $modules = $container->moduleRepository()->findAllBy([
            'course' => $courseId->toInt(),
        ]);

        $roomRepository = $container->roomRepository();
        $moodleGroupRepository = $container->moodleGroupRepository();
        $nameService = $container->nameService();
        $matrixRoomService = $container->matrixRoomService();

        foreach ($modules as $module) {
            $room = $roomRepository->findOneBy([
                'module_id' => $module->id()->toInt(),
            ]);

            if (!$room instanceof Plugin\Domain\Room) {
                continue;
            }

            $name = $nameService->forCourseAndModule(
                $courseShortName,
                $module->name(),
            );

            $groupId = $room->groupId();

            if ($groupId instanceof Moodle\Domain\GroupId) {
                $group = $moodleGroupRepository->find($groupId);

                if (!$group instanceof Moodle\Domain\Group) {
                    continue;
                }

                $name = $nameService->forGroupCourseAndModule(
                    $group->name(),
                    $courseShortName,
                    $module->name(),
                );
            }

            $matrixRoomService->updateRoom(
                $room->matrixRoomId(),
                $name,
                Matrix\Domain\RoomTopic::fromString($module->topic()->toString()),
            );
        }
    }

    /**
     * @throws Moodle\Domain\CourseNotFound
     * @throws Plugin\Domain\ModuleNotFound
     */
    private static function updateRoomsForModuleFollowingUpdateOfModuleName(
        Moodle\Domain\CourseId $courseId,
        Plugin\Domain\ModuleId $moduleId,
        Plugin\Domain\ModuleName $moduleName
    ): void {
        $container = Container::instance();

        $course = $container->moodleCourseRepository()->find($courseId);

        if (!$course instanceof Moodle\Domain\Course) {
            throw Moodle\Domain\CourseNotFound::for($courseId);
        }

        $module = $container->moduleRepository()->findOneBy([
            'id' => $moduleId->toInt(),
        ]);

        if (!$module instanceof Plugin\Domain\Module) {
            throw Plugin\Domain\ModuleNotFound::for($moduleId);
        }

        $rooms = $container->roomRepository()->findAllBy([
            'module_id' => $moduleId->toInt(),
        ]);

        $nameService = $container->nameService();
        $moodleGroupRepository = $container->moodleGroupRepository();
        $matrixRoomService = $container->matrixRoomService();

        foreach ($rooms as $room) {
            $name = $nameService->forCourseAndModule(
                $course->shortName(),
                $moduleName,
            );

            $groupId = $room->groupId();

            if ($groupId instanceof Moodle\Domain\GroupId) {
                $group = $moodleGroupRepository->find($groupId);

                if (!$group instanceof Moodle\Domain\Group) {
                    continue;
                }

                $name = $nameService->forGroupCourseAndModule(
                    $group->name(),
                    $course->shortName(),
                    $moduleName,
                );
            }

            $matrixRoomService->updateRoom(
                $room->matrixRoomId(),
                $name,
                Matrix\Domain\RoomTopic::fromString($module->topic()->toString()),
            );
        }
    }

    /**
     * @throws Moodle\Domain\GroupNotFound
     */
    private static function updateRoomsForGroup(Moodle\Domain\GroupId $groupId): void
    {
        $container = Container::instance();

        $group = $container->moodleGroupRepository()->find($groupId);

        if (!$group instanceof Moodle\Domain\Group) {
            throw Moodle\Domain\GroupNotFound::for($groupId);
        }

        $moduleRepository = $container->moduleRepository();
        $moodleCourseRepository = $container->moodleCourseRepository();
        $nameService = $container->nameService();
        $matrixRoomService = $container->matrixRoomService();

        $rooms = $container->roomRepository()->findAllBy([
            'group_id' => $groupId->toInt(),
        ]);

        foreach ($rooms as $room) {
            $module = $moduleRepository->findOneBy([
                'id' => $room->moduleId()->toInt(),
            ]);

            if (!$module instanceof Plugin\Domain\Module) {
                continue;
            }

            $course = $moodleCourseRepository->find($module->courseId());

            if (!$course instanceof Moodle\Domain\Course) {
                continue;
            }

            $name = $nameService->forGroupCourseAndModule(
                $group->name(),
                $course->shortName(),
                $module->name(),
            );

            $matrixRoomService->updateRoom(
                $room->matrixRoomId(),
                $name,
                Matrix\Domain\RoomTopic::fromString($module->topic()->toString()),
            );
        }
    }

    /**
     * @throws Moodle\Domain\GroupNotFound
     */
    private static function removeRoomsForGroup(Moodle\Domain\GroupId $groupId): void
    {
        $container = Container::instance();

        $roomRepository = $container->roomRepository();

        $rooms = $roomRepository->findAllBy([
            'group_id' => $groupId->toInt(),
        ]);

        $matrixRoomService = $container->matrixRoomService();

        foreach ($rooms as $room) {
            $matrixRoomService->removeRoom($room->matrixRoomId());

            $roomRepository->remove($room);
        }
    }

    private static function requireAutoloader(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
}
